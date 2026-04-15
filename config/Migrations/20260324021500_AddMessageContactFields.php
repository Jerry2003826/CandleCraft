<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddMessageContactFields extends BaseMigration
{
    public function change(): void
    {
        $adapter = $this->getAdapter();

        // SQLite test databases already come from the current schema dump.
        // Applying column/index changes through Phinx's table-rebuild path can
        // generate invalid INSERT ... SELECT statements with no shared columns.
        if ($adapter->getAdapterType() === 'sqlite') {
            if (!$adapter->hasColumn('messages', 'sender_phone')) {
                $this->execute('ALTER TABLE messages ADD COLUMN sender_phone VARCHAR(30) DEFAULT NULL');
            }

            if (!$adapter->hasColumn('messages', 'source_page')) {
                $this->execute('ALTER TABLE messages ADD COLUMN source_page VARCHAR(255) DEFAULT NULL');
            }

            if (!$adapter->hasIndexByName('messages', 'idx_messages_source_page')) {
                $this->execute('CREATE INDEX idx_messages_source_page ON messages (source_page)');
            }

            if ($adapter->hasColumn('messages', 'source_page')) {
                $this->execute(
                    "UPDATE messages
                     SET source_page = 'homepage'
                     WHERE message_type = 'contact_form'
                       AND source_page IS NULL"
                );
            }

            return;
        }

        $table = $this->table('messages');
        $needsUpdate = false;

        if (!$table->hasColumn('sender_phone')) {
            $table->addColumn('sender_phone', 'string', [
                'limit' => 30,
                'null' => true,
                'after' => 'sender_email',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('source_page')) {
            $table->addColumn('source_page', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'sender_phone',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasIndexByName('idx_messages_source_page')) {
            $table->addIndex(['source_page'], [
                'name' => 'idx_messages_source_page',
            ]);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }

        $this->execute(
            "UPDATE messages
             SET source_page = 'homepage'
             WHERE message_type = 'contact_form'
               AND source_page IS NULL"
        );
    }
}
