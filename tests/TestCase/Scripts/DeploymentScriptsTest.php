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

    public function testDeployOneclickPackageOnlyGeneratesUniqueSaltsAndNoPlaceholders(): void
    {
        $outputDir = $this->tempDir . '/deploy-oneclick';
        $scriptPath = $this->repoRoot . '/scripts/deploy-oneclick.sh';

        $this->runCommand(sprintf(
            'cd %s && bash %s --package-only --skip-composer-install --output-dir %s --user %s --host %s --db-user %s --db-pass %s',
            escapeshellarg($this->repoRoot),
            escapeshellarg($scriptPath),
            escapeshellarg($outputDir),
            escapeshellarg('deployuser'),
            escapeshellarg('package-only.local'),
            escapeshellarg('deployuser'),
            escapeshellarg('shared-db-password')
        ));

        $salts = [];
        foreach (['dev', 'production', 'review'] as $environment) {
            $configPath = $outputDir . '/' . $environment . '_app/config/app_local.php';
            $salts[] = $this->assertGeneratedConfig($configPath, 'shared-db-password', $environment === 'production');
        }

        $this->assertCount(3, array_unique($salts));
    }

    public function testCpanelDeployCopiesLegacyDatabaseReferencesAndGeneratesRealSecrets(): void
    {
        $outputDir = $this->tempDir . '/cpanel';
        $scriptPath = $this->repoRoot . '/scripts/cpanel-deploy.sh';

        $this->runCommand(sprintf(
            'cd %s && bash %s %s %s --skip-composer-install --output-dir %s --dev-db-pass %s --production-db-pass %s --review-db-pass %s',
            escapeshellarg($this->repoRoot),
            escapeshellarg($scriptPath),
            escapeshellarg('cpaneluser'),
            escapeshellarg('example.com'),
            escapeshellarg($outputDir),
            escapeshellarg('dev-secret'),
            escapeshellarg('prod-secret'),
            escapeshellarg('review-secret')
        ));

        $salts = [];
        $salts[] = $this->assertGeneratedConfig($outputDir . '/dev_app/config/app_local.php', 'dev-secret', false);
        $salts[] = $this->assertGeneratedConfig($outputDir . '/production_app/config/app_local.php', 'prod-secret', true);
        $salts[] = $this->assertGeneratedConfig($outputDir . '/review_app/config/app_local.php', 'review-secret', false);

        $this->assertCount(3, array_unique($salts));

        $databaseSchema = $outputDir . '/database/academy_management_db.sql';
        $seedAdmin = $outputDir . '/database/seed_admin.sql';
        $databaseReadme = (string)file_get_contents($outputDir . '/database/README.md');
        $deploymentReadme = (string)file_get_contents($outputDir . '/README.md');

        $this->assertFileExists($databaseSchema);
        $this->assertFileExists($seedAdmin);
        $this->assertStringContainsString('academy_management_db.sql', $databaseReadme);
        $this->assertStringContainsString('seed_admin.sql', $databaseReadme);
        $this->assertStringNotContainsString('schema.sql', $databaseReadme);
        $this->assertStringContainsString('academy_management_db.sql', $deploymentReadme);
        $this->assertStringContainsString('seed_admin.sql', $deploymentReadme);
        $this->assertStringContainsString('rotate database credentials if needed', $deploymentReadme);
    }

    private function runCommand(string $command): void
    {
        $output = [];
        $exitCode = 0;

        exec($command . ' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
    }

    private function assertGeneratedConfig(string $configPath, string $expectedPassword, bool $production): string
    {
        $this->assertFileExists($configPath);

        $contents = (string)file_get_contents($configPath);
        $this->assertStringNotContainsString('__SALT__', $contents);
        $this->assertStringNotContainsString('CHANGE_ME_', $contents);
        $this->assertStringContainsString("'password' => '" . $expectedPassword . "'", $contents);

        if ($production) {
            $this->assertStringContainsString("'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),", $contents);
        }

        $matched = preg_match("/'salt' => env\\('SECURITY_SALT', '([^']+)'\\),/", $contents, $matches);
        $this->assertSame(1, $matched, 'Expected generated config to contain an embedded unique salt.');

        return $matches[1];
    }
}
