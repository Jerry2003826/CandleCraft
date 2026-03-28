<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCategories extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('categories');
        $table->addColumn('name', 'string', [
            'default' => null,
            'limit' => 64,
            'null' => false,
        ]);
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->create();
    }
}
