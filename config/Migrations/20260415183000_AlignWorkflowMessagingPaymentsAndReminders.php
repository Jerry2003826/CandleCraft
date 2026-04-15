<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlignWorkflowMessagingPaymentsAndReminders extends BaseMigration
{
    public function up(): void
    {
        $adapter = $this->getAdapter();
        $isSqlite = $adapter->getAdapterType() === 'sqlite';

        if ($isSqlite) {
            if (!$adapter->hasColumn('messages', 'parent_message_id')) {
                $this->execute('ALTER TABLE messages ADD COLUMN parent_message_id BIGINT DEFAULT NULL');
            }
            if (!$adapter->hasColumn('messages', 'recipient_name')) {
                $this->execute('ALTER TABLE messages ADD COLUMN recipient_name VARCHAR(100) DEFAULT NULL');
            }
            if (!$adapter->hasColumn('messages', 'recipient_email')) {
                $this->execute('ALTER TABLE messages ADD COLUMN recipient_email VARCHAR(255) DEFAULT NULL');
            }
            if (!$adapter->hasColumn('messages', 'delivery_status')) {
                $this->execute("ALTER TABLE messages ADD COLUMN delivery_status VARCHAR(20) DEFAULT NULL");
            }
            if (!$adapter->hasColumn('bookings', 'reminder_sent_at')) {
                $this->execute('ALTER TABLE bookings ADD COLUMN reminder_sent_at DATETIME DEFAULT NULL');
            }
        } else {
            $messages = $this->table('messages');
            $messageNeedsUpdate = false;

            if (!$messages->hasColumn('parent_message_id')) {
                $messages->addColumn('parent_message_id', 'biginteger', [
                    'null' => true,
                    'signed' => false,
                    'after' => 'receiver_user_id',
                ]);
                $messageNeedsUpdate = true;
            }
            if (!$messages->hasColumn('recipient_name')) {
                $messages->addColumn('recipient_name', 'string', [
                    'limit' => 100,
                    'null' => true,
                    'after' => 'sender_name',
                ]);
                $messageNeedsUpdate = true;
            }
            if (!$messages->hasColumn('recipient_email')) {
                $messages->addColumn('recipient_email', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'after' => 'recipient_name',
                ]);
                $messageNeedsUpdate = true;
            }
            if (!$messages->hasColumn('delivery_status')) {
                $messages->addColumn('delivery_status', 'string', [
                    'limit' => 20,
                    'null' => true,
                    'after' => 'message_status',
                ]);
                $messageNeedsUpdate = true;
            }
            if (!$messages->hasIndexByName('idx_messages_parent_message_id')) {
                $messages->addIndex(['parent_message_id'], [
                    'name' => 'idx_messages_parent_message_id',
                ]);
                $messageNeedsUpdate = true;
            }
            if (!$messages->hasIndexByName('idx_messages_recipient_email')) {
                $messages->addIndex(['recipient_email'], [
                    'name' => 'idx_messages_recipient_email',
                ]);
                $messageNeedsUpdate = true;
            }
            if ($messageNeedsUpdate) {
                $messages->update();
            }

            $bookings = $this->table('bookings');
            if (!$bookings->hasColumn('reminder_sent_at')) {
                $bookings->addColumn('reminder_sent_at', 'datetime', [
                    'null' => true,
                    'default' => null,
                    'after' => 'notes',
                ])->addIndex(['reminder_sent_at'], [
                    'name' => 'idx_bookings_reminder_sent_at',
                ])->update();
            }
        }

        if (!$isSqlite) {
            $this->execute(
                "ALTER TABLE messages
                 MODIFY message_type ENUM('internal', 'contact_form', 'email_reply') NOT NULL DEFAULT 'internal'"
            );
            $this->execute(
                "ALTER TABLE messages
                 ADD CONSTRAINT fk_messages_parent_message_id
                 FOREIGN KEY (parent_message_id)
                 REFERENCES messages (message_id)
                 ON DELETE SET NULL
                 ON UPDATE CASCADE"
            );
        }

        if (!$this->hasTable('payment_profiles')) {
            $table = $this->table('payment_profiles', ['id' => false, 'primary_key' => ['payment_profile_id']]);
            $table
                ->addColumn('payment_profile_id', 'biginteger', [
                    'identity' => true,
                    'signed' => false,
                ])
                ->addColumn('user_id', 'biginteger', [
                    'null' => false,
                    'signed' => false,
                ])
                ->addColumn('billing_name', 'string', [
                    'limit' => 150,
                    'null' => false,
                ])
                ->addColumn('billing_email', 'string', [
                    'limit' => 255,
                    'null' => false,
                ])
                ->addColumn('billing_phone', 'string', [
                    'limit' => 30,
                    'null' => true,
                ])
                ->addColumn('billing_address_line1', 'string', [
                    'limit' => 255,
                    'null' => true,
                ])
                ->addColumn('billing_address_line2', 'string', [
                    'limit' => 255,
                    'null' => true,
                ])
                ->addColumn('billing_city', 'string', [
                    'limit' => 120,
                    'null' => true,
                ])
                ->addColumn('billing_state', 'string', [
                    'limit' => 120,
                    'null' => true,
                ])
                ->addColumn('billing_postcode', 'string', [
                    'limit' => 20,
                    'null' => true,
                ])
                ->addColumn('billing_country', 'string', [
                    'limit' => 120,
                    'null' => true,
                ])
                ->addColumn('preferred_payment_method', 'string', [
                    'limit' => 30,
                    'default' => 'card',
                    'null' => false,
                ])
                ->addColumn('profile_status', 'string', [
                    'limit' => 20,
                    'default' => 'active',
                    'null' => false,
                ])
                ->addColumn('is_default', 'boolean', [
                    'default' => false,
                    'null' => false,
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'null' => false,
                ])
                ->addColumn('updated_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                    'null' => false,
                ])
                ->addIndex(['user_id'], ['name' => 'idx_payment_profiles_user_id'])
                ->addIndex(['user_id', 'is_default'], ['name' => 'idx_payment_profiles_user_default'])
                ->addForeignKey('user_id', 'users', 'user_id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->create();
        }
    }

    public function down(): void
    {
        throw new RuntimeException('This migration is irreversible.');
    }
}
