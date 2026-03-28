<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateLearningResources extends BaseMigration
{
    public function change(): void
    {
        if ($this->hasTable('learning_resources')) {
            return;
        }
        $table = $this->table('learning_resources');
        $table->addColumn('class_id', 'integer', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('title', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => false,
        ]);
        $table->addColumn('description', 'text', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('resource_type', 'string', [
            'default' => null,
            'limit' => 30,
            'null' => false,
        ]);
        $table->addColumn('file_path', 'string', [
            'default' => null,
            'limit' => 500,
            'null' => true,
        ]);
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addIndex([
            'class_id',
        ], ['name' => 'idx_learning_resources_class_id']);
        $table->addForeignKey('class_id', 'classes', 'class_id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
        ]);
        $table->create();
    }
}
