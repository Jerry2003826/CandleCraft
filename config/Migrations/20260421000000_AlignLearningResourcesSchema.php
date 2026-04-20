<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlignLearningResourcesSchema extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('learning_resources')) {
            return;
        }

        $table = $this->table('learning_resources');

        if ($table->hasColumn('id') && !$table->hasColumn('resource_id')) {
            $table->renameColumn('id', 'resource_id');
        }

        if ($table->hasColumn('title') && !$table->hasColumn('resource_name')) {
            $table->renameColumn('title', 'resource_name');
        }

        if ($table->hasColumn('description') && !$table->hasColumn('resource_description')) {
            $table->renameColumn('description', 'resource_description');
        }

        if (!$table->hasColumn('resource_url')) {
            $table->addColumn('resource_url', 'string', [
                'limit' => 500,
                'null' => true,
                'after' => 'resource_type',
            ]);
        }

        if (!$table->hasColumn('uploaded_by_teacher_id')) {
            $table->addColumn('uploaded_by_teacher_id', 'biginteger', [
                'null' => true,
                'after' => 'class_id',
            ]);
        }

        if (!$table->hasColumn('resource_status')) {
            $table->addColumn('resource_status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'active',
                'after' => 'resource_description',
            ]);
        }

        if (!$table->hasColumn('uploaded_at')) {
            $table->addColumn('uploaded_at', 'datetime', [
                'null' => true,
                'after' => 'resource_status',
            ]);
        }

        if ($table->hasColumn('resource_name')) {
            $table->changeColumn('resource_name', 'string', [
                'limit' => 150,
                'null' => false,
            ]);
        }

        if ($table->hasColumn('resource_type')) {
            $table->changeColumn('resource_type', 'string', [
                'limit' => 30,
                'null' => false,
            ]);
        }

        if ($table->hasColumn('file_path')) {
            $table->changeColumn('file_path', 'string', [
                'limit' => 500,
                'null' => true,
            ]);
        }

        $table->update();

        $fallbackTimestamp = $this->quoteLiteral(date('Y-m-d H:i:s'));
        if ($table->hasColumn('uploaded_at')) {
            $coalesceParts = ['uploaded_at'];
            if ($table->hasColumn('created')) {
                $coalesceParts[] = 'created';
            }
            if ($table->hasColumn('modified')) {
                $coalesceParts[] = 'modified';
            }
            $coalesceParts[] = $fallbackTimestamp;

            $this->execute(
                sprintf(
                    "UPDATE learning_resources
                     SET uploaded_at = COALESCE(%s)
                     WHERE uploaded_at IS NULL",
                    implode(', ', $coalesceParts)
                )
            );
        }

        if (!$this->hasIndex('learning_resources', ['resource_status'])) {
            $this->table('learning_resources')
                ->addIndex(['resource_status'], ['name' => 'idx_learning_resources_resource_status'])
                ->update();
        }

        if (!$this->hasIndex('learning_resources', ['uploaded_by_teacher_id'])) {
            $this->table('learning_resources')
                ->addIndex(['uploaded_by_teacher_id'], ['name' => 'idx_learning_resources_uploaded_by_teacher_id'])
                ->update();
        }
    }

    public function down(): void
    {
        // Keep the aligned schema in place; rolling back would reintroduce drift
        // between migrations and the current application code.
    }

    private function hasIndex(string $tableName, array $columns): bool
    {
        return $this->table($tableName)->hasIndex($columns);
    }

    private function quoteLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
