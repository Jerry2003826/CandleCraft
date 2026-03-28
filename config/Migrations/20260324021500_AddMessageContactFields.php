<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddMessageContactFields extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('messages');

        if (!$table->hasColumn('sender_phone')) {
            $table->addColumn('sender_phone', 'string', [
                'limit' => 30,
                'null' => true,
                'after' => 'sender_email',
            ]);
        }

        if (!$table->hasColumn('source_page')) {
            $table->addColumn('source_page', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'sender_phone',
            ]);
        }

        if (!$table->hasIndex(['source_page'])) {
            $table->addIndex(['source_page'], [
                'name' => 'idx_messages_source_page',
            ]);
        }

        $table->update();

        $this->execute(
            "UPDATE messages
             SET source_page = 'homepage'
             WHERE message_type = 'contact_form'
               AND source_page IS NULL"
        );
    }
}
