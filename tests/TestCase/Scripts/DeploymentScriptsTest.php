<?php
declare(strict_types=1);

namespace App\Test\TestCase\Scripts;

use Cake\TestSuite\TestCase;

class DeploymentScriptsTest extends TestCase
{
    private string $repoRoot;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
        $this->tempDir = sys_get_temp_dir() . '/candlecraft-script-tests-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            exec('rm -rf ' . escapeshellarg($this->tempDir));
        }

        parent::tearDown();
    }

    public function testDeployOneclickPackageOnlyGeneratesTemplatesWithoutSecretsOrDatabaseDump(): void
    {
        $outputDir = $this->tempDir . '/deploy-oneclick';
        $scriptPath = $this->repoRoot . '/scripts/deploy-oneclick.sh';

        $this->runCommandExpectSuccess(sprintf(
            'cd %s && bash %s --package-only --skip-composer-install --output-dir %s --user %s --host %s --db-user %s --db-pass %s',
            escapeshellarg($this->repoRoot),
            escapeshellarg($scriptPath),
            escapeshellarg($outputDir),
            escapeshellarg('deployuser'),
            escapeshellarg('package-only.local'),
            escapeshellarg('deployuser'),
            escapeshellarg('shared-db-password'),
        ));

        foreach (['dev', 'production', 'review'] as $environment) {
            $templatePath = $outputDir . '/' . $environment . '_app/config/app_local.template.php';
            $realPath = $outputDir . '/' . $environment . '_app/config/app_local.php';
            $this->assertTemplateConfig($templatePath, $environment === 'production');
            $this->assertFileDoesNotExist($realPath);
        }

        $this->assertFileDoesNotExist($outputDir . '/database.sql');
        $this->assertStringContainsString('Require all denied', (string)file_get_contents($outputDir . '/public_dev/uploads/resources/.htaccess'));
    }

    public function testDeployOneclickEmbedSecretsHandlesSpecialCharacterPasswords(): void
    {
        $passwords = [
            "pa'ssword",
            'slash\\password',
            'cash$money',
            'space password',
        ];
        $scriptPath = $this->repoRoot . '/scripts/deploy-oneclick.sh';

        foreach ($passwords as $index => $password) {
            $outputDir = $this->tempDir . '/deploy-oneclick-embed-' . $index;

            $this->runCommandExpectSuccess(sprintf(
                'cd %s && bash %s --package-only --embed-secrets --keep-artifacts --skip-composer-install --output-dir %s --user %s --host %s --db-user %s --db-pass %s',
                escapeshellarg($this->repoRoot),
                escapeshellarg($scriptPath),
                escapeshellarg($outputDir),
                escapeshellarg('deployuser'),
                escapeshellarg('package-only.local'),
                escapeshellarg('deployuser'),
                escapeshellarg($password),
            ));

            foreach (['dev', 'production', 'review'] as $environment) {
                $configPath = $outputDir . '/' . $environment . '_app/config/app_local.php';
                $this->assertEmbeddedConfig($configPath, $environment === 'production');
            }
        }
    }

    public function testDeployOneclickRejectsCloneToReviewWithoutOverwriteConfirmation(): void
    {
        $outputDir = $this->tempDir . '/deploy-oneclick-confirmation';
        $scriptPath = $this->repoRoot . '/scripts/deploy-oneclick.sh';

        [$exitCode, $output] = $this->runCommand(sprintf(
            'cd %s && bash %s --package-only --skip-composer-install --output-dir %s --user %s --host %s --db-user %s --db-pass %s --clone-local-data --clone-targets=review',
            escapeshellarg($this->repoRoot),
            escapeshellarg($scriptPath),
            escapeshellarg($outputDir),
            escapeshellarg('deployuser'),
            escapeshellarg('package-only.local'),
            escapeshellarg('deployuser'),
            escapeshellarg('shared-db-password'),
        ));

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString('requires an interactive OVERWRITE confirmation', $output);
    }

    public function testCpanelDeployDefaultArtifactsUseTemplatesAndCopyLegacyDatabaseReferences(): void
    {
        $outputDir = $this->tempDir . '/cpanel-default';
        $scriptPath = $this->repoRoot . '/scripts/cpanel-deploy.sh';

        $this->runCommandExpectSuccess(sprintf(
            'cd %s && bash %s %s %s --skip-composer-install --output-dir %s --dev-db-pass %s --production-db-pass %s --review-db-pass %s',
            escapeshellarg($this->repoRoot),
            escapeshellarg($scriptPath),
            escapeshellarg('cpaneluser'),
            escapeshellarg('example.com'),
            escapeshellarg($outputDir),
            escapeshellarg('dev-secret'),
            escapeshellarg('prod-secret'),
            escapeshellarg('review-secret'),
        ));

        foreach (['dev', 'production', 'review'] as $environment) {
            $templatePath = $outputDir . '/' . $environment . '_app/config/app_local.template.php';
            $realPath = $outputDir . '/' . $environment . '_app/config/app_local.php';
            $this->assertTemplateConfig($templatePath, $environment === 'production');
            $this->assertFileDoesNotExist($realPath);
        }

        $databaseSchema = $outputDir . '/database/academy_management_db.sql';
        $seedAdmin = $outputDir . '/database/seed_admin.sql';
        $databaseReadme = (string)file_get_contents($outputDir . '/database/README.md');
        $deploymentReadme = (string)file_get_contents($outputDir . '/README.md');

        $this->assertFileExists($databaseSchema);
        $this->assertFileExists($seedAdmin);
        $this->assertStringContainsString('academy_management_db.sql', $databaseReadme);
        $this->assertStringContainsString('seed_admin.sql', $databaseReadme);
        $this->assertStringNotContainsString('schema.sql', $databaseReadme);
        $this->assertStringContainsString('config/app_local.template.php', $deploymentReadme);
        $this->assertStringContainsString('/home/cpaneluser/dev_app/storage/resources', (string)file_get_contents($outputDir . '/dev_app/config/app_local.template.php'));
        $this->assertStringContainsString('/resources', (string)file_get_contents($outputDir . '/dev_app/config/app_local.template.php'));
        $this->assertStringContainsString('Require all denied', (string)file_get_contents($outputDir . '/public_html_dev/uploads/resources/.htaccess'));
    }

    public function testCpanelDeployEmbedSecretsHandlesSpecialCharacterPasswords(): void
    {
        $passwords = [
            "pa'ssword",
            'slash\\password',
            'cash$money',
            'space password',
        ];
        $scriptPath = $this->repoRoot . '/scripts/cpanel-deploy.sh';

        foreach ($passwords as $index => $password) {
            $outputDir = $this->tempDir . '/cpanel-embed-' . $index;

            $this->runCommandExpectSuccess(sprintf(
                'cd %s && bash %s %s %s --skip-composer-install --embed-secrets --keep-artifacts --output-dir %s --dev-db-pass %s --production-db-pass %s --review-db-pass %s',
                escapeshellarg($this->repoRoot),
                escapeshellarg($scriptPath),
                escapeshellarg('cpaneluser'),
                escapeshellarg('example.com'),
                escapeshellarg($outputDir),
                escapeshellarg($password),
                escapeshellarg($password),
                escapeshellarg($password),
            ));

            foreach (['dev', 'production', 'review'] as $environment) {
                $configPath = $outputDir . '/' . $environment . '_app/config/app_local.php';
                $this->assertEmbeddedConfig($configPath, $environment === 'production');
            }
        }
    }

    private function runCommand(string $command): array
    {
        $output = [];
        $exitCode = 0;

        exec($command . ' 2>&1', $output, $exitCode);

        return [$exitCode, implode(PHP_EOL, $output)];
    }

    private function runCommandExpectSuccess(string $command): void
    {
        [$exitCode, $output] = $this->runCommand($command);

        $this->assertSame(0, $exitCode, $output);
    }

    private function assertTemplateConfig(string $configPath, bool $production): void
    {
        $this->assertFileExists($configPath);

        $contents = (string)file_get_contents($configPath);
        $this->assertStringContainsString('app_local.template.php', $configPath);
        $this->assertStringContainsString("'salt' => env('SECURITY_SALT', '__SET_A_UNIQUE_SECURITY_SALT__')", $contents);
        $this->assertStringContainsString("'password' => env('DATABASE_PASSWORD', null)", $contents);
        $this->assertStringNotContainsString('CHANGE_ME_', $contents);
        $this->assertPhpLintPasses($configPath);

        if ($production) {
            $this->assertStringContainsString("'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),", $contents);
        }
    }

    private function assertEmbeddedConfig(string $configPath, bool $production): void
    {
        $this->assertFileExists($configPath);

        $contents = (string)file_get_contents($configPath);
        $this->assertStringNotContainsString('__SALT__', $contents);
        $this->assertStringNotContainsString('CHANGE_ME_', $contents);
        $this->assertStringContainsString("'password' => env('DATABASE_PASSWORD',", $contents);
        $this->assertPhpLintPasses($configPath);

        if ($production) {
            $this->assertStringContainsString("'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),", $contents);
        }
    }

    private function assertPhpLintPasses(string $filePath): void
    {
        [$exitCode, $output] = $this->runCommand(sprintf('php -l %s', escapeshellarg($filePath)));
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('No syntax errors detected', $output);
    }
}
