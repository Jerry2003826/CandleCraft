<?php
declare(strict_types=1);

use Cake\Database\Schema\TableSchema;

$lockFile = dirname(__DIR__) . '/config/Migrations/schema-dump-default.lock';
$rawSchema = unserialize((string)file_get_contents($lockFile));

$schema = [];
foreach ($rawSchema as $tableName => $tableSchema) {
    if (!$tableSchema instanceof TableSchema) {
        continue;
    }

    $table = [
        'table' => $tableName,
        'columns' => [],
    ];

    foreach ($tableSchema->columns() as $columnName) {
        $column = $tableSchema->getColumn($columnName);
        if ($column !== null) {
            if (
                in_array($tableName, ['payments', 'learning_resources'], true) &&
                in_array($columnName, ['payment_id', 'resource_id'], true) &&
                ($column['autoIncrement'] ?? false) === true &&
                ($column['type'] ?? null) === 'biginteger'
            ) {
                $column['type'] = 'integer';
            }

            $table['columns'][$columnName] = $column;
        }
    }

    $indexes = [];
    foreach ($tableSchema->indexes() as $indexName) {
        $index = $tableSchema->getIndex($indexName);
        if ($index !== null) {
            $indexes[$indexName] = $index;
        }
    }
    if ($indexes !== []) {
        $table['indexes'] = $indexes;
    }

    $constraints = [];
    foreach ($tableSchema->constraints() as $constraintName) {
        if (!is_string($constraintName)) {
            continue;
        }
        $constraint = $tableSchema->getConstraint($constraintName);
        if ($constraint !== null && ($constraint['type'] ?? null) !== 'check') {
            $constraints[$constraintName] = $constraint;
        }
    }
    if ($constraints !== []) {
        $table['constraints'] = $constraints;
    }

    $schema[$tableName] = $table;
}

if (!isset($schema['payment_webhook_incidents'])) {
    $schema['payment_webhook_incidents'] = [
        'table' => 'payment_webhook_incidents',
        'columns' => [
            'incident_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => null,
                'autoIncrement' => true,
            ],
            'event_id' => [
                'type' => 'string',
                'length' => 100,
                'null' => true,
                'default' => null,
            ],
            'event_type' => [
                'type' => 'string',
                'length' => 100,
                'null' => false,
                'default' => null,
            ],
            'session_id' => [
                'type' => 'string',
                'length' => 100,
                'null' => false,
                'default' => null,
            ],
            'payment_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => true,
                'default' => null,
            ],
            'booking_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => true,
                'default' => null,
            ],
            'reason_code' => [
                'type' => 'string',
                'length' => 100,
                'null' => false,
                'default' => null,
            ],
            'severity' => [
                'type' => 'string',
                'length' => 20,
                'null' => false,
                'default' => 'warning',
            ],
            'status' => [
                'type' => 'string',
                'length' => 20,
                'null' => false,
                'default' => 'open',
            ],
            'context_json' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
            ],
            'payload_hash' => [
                'type' => 'string',
                'length' => 64,
                'null' => false,
                'default' => null,
            ],
            'notes' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => null,
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => null,
            ],
            'resolved_at' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
            ],
            'resolved_by_admin_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => true,
                'default' => null,
            ],
        ],
        'indexes' => [
            'payment_webhook_incidents_status_idx' => [
                'type' => 'index',
                'columns' => ['status'],
            ],
            'payment_webhook_incidents_event_type_idx' => [
                'type' => 'index',
                'columns' => ['event_type'],
            ],
            'payment_webhook_incidents_event_id_idx' => [
                'type' => 'index',
                'columns' => ['event_id'],
            ],
            'payment_webhook_incidents_session_id_idx' => [
                'type' => 'index',
                'columns' => ['session_id'],
            ],
            'payment_webhook_incidents_reason_code_idx' => [
                'type' => 'index',
                'columns' => ['reason_code'],
            ],
            'payment_webhook_incidents_created_at_idx' => [
                'type' => 'index',
                'columns' => ['created_at'],
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => ['incident_id'],
            ],
            'payment_webhook_incidents_event_reason_status_uk' => [
                'type' => 'unique',
                'columns' => ['event_id', 'reason_code', 'status'],
            ],
        ],
    ];
}

if (!isset($schema['stripe_webhook_events'])) {
    $schema['stripe_webhook_events'] = [
        'table' => 'stripe_webhook_events',
        'columns' => [
            'webhook_event_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => null,
                'autoIncrement' => true,
            ],
            'event_id' => [
                'type' => 'string',
                'length' => 100,
                'null' => false,
                'default' => null,
            ],
            'event_type' => [
                'type' => 'string',
                'length' => 100,
                'null' => false,
                'default' => null,
            ],
            'session_id' => [
                'type' => 'string',
                'length' => 100,
                'null' => true,
                'default' => null,
            ],
            'business_event_key' => [
                'type' => 'string',
                'length' => 255,
                'null' => true,
                'default' => null,
            ],
            'payload_hash' => [
                'type' => 'string',
                'length' => 64,
                'null' => false,
                'default' => null,
            ],
            'processing_status' => [
                'type' => 'string',
                'length' => 20,
                'null' => false,
                'default' => 'processing',
            ],
            'suspicious_state' => [
                'type' => 'string',
                'length' => 20,
                'null' => false,
                'default' => 'clean',
            ],
            'suspicious_reason_code' => [
                'type' => 'string',
                'length' => 100,
                'null' => true,
                'default' => null,
            ],
            'suspicious_seen_at' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
            ],
            'suspicious_business_event_key' => [
                'type' => 'string',
                'length' => 255,
                'null' => true,
                'default' => null,
            ],
            'suspicious_payload_hash' => [
                'type' => 'string',
                'length' => 64,
                'null' => true,
                'default' => null,
            ],
            'suspicious_target_status' => [
                'type' => 'string',
                'length' => 20,
                'null' => true,
                'default' => null,
            ],
            'suspicious_count' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => 0,
                'autoIncrement' => null,
            ],
            'replay_count' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => 0,
                'autoIncrement' => null,
            ],
            'last_replay_event_id' => [
                'type' => 'string',
                'length' => 100,
                'null' => true,
                'default' => null,
            ],
            'last_replay_payload_hash' => [
                'type' => 'string',
                'length' => 64,
                'null' => true,
                'default' => null,
            ],
            'last_replay_seen_at' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
            ],
            'last_suppressed_status_update' => [
                'type' => 'string',
                'length' => 20,
                'null' => true,
                'default' => null,
            ],
            'last_suppressed_status_event_id' => [
                'type' => 'string',
                'length' => 100,
                'null' => true,
                'default' => null,
            ],
            'last_suppressed_status_payload_hash' => [
                'type' => 'string',
                'length' => 64,
                'null' => true,
                'default' => null,
            ],
            'last_suppressed_status_seen_at' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
            ],
            'first_seen_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => null,
            ],
            'processing_started_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => null,
            ],
            'last_seen_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => null,
            ],
        ],
        'indexes' => [
            'stripe_webhook_events_processing_status_idx' => [
                'type' => 'index',
                'columns' => ['processing_status'],
            ],
            'idx_stripe_webhook_events_suspicious_audit' => [
                'type' => 'index',
                'columns' => ['suspicious_state', 'suspicious_seen_at'],
            ],
            'idx_stripe_webhook_events_suspicious_reason' => [
                'type' => 'index',
                'columns' => ['suspicious_reason_code', 'suspicious_seen_at'],
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => ['webhook_event_id'],
            ],
            'stripe_webhook_events_event_id_uk' => [
                'type' => 'unique',
                'columns' => ['event_id'],
            ],
            'stripe_webhook_events_business_event_key_uk' => [
                'type' => 'unique',
                'columns' => ['business_event_key'],
            ],
        ],
    ];
}

if (!isset($schema['parents'])) {
    $schema['parents'] = [
        'table' => 'parents',
        'columns' => [
            'parent_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => null,
                'autoIncrement' => true,
            ],
            'user_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => null,
            ],
            'parent_name' => [
                'type' => 'string',
                'length' => 100,
                'null' => false,
                'default' => null,
            ],
            'phone_number' => [
                'type' => 'string',
                'length' => 30,
                'null' => false,
                'default' => null,
            ],
            'emergency_contact_name' => [
                'type' => 'string',
                'length' => 100,
                'null' => true,
                'default' => null,
            ],
            'emergency_contact_phone' => [
                'type' => 'string',
                'length' => 30,
                'null' => true,
                'default' => null,
            ],
            'address' => [
                'type' => 'string',
                'length' => 255,
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => null,
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => null,
            ],
        ],
        'indexes' => [
            'idx_parents_phone_number' => [
                'type' => 'index',
                'columns' => ['phone_number'],
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => ['parent_id'],
            ],
            'uk_parents_user_id' => [
                'type' => 'unique',
                'columns' => ['user_id'],
            ],
        ],
    ];
}

if (!isset($schema['parent_students'])) {
    $schema['parent_students'] = [
        'table' => 'parent_students',
        'columns' => [
            'parent_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => null,
            ],
            'student_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => null,
            ],
            'relationship_to_student' => [
                'type' => 'string',
                'length' => 50,
                'null' => false,
                'default' => null,
            ],
            'is_primary_guardian' => [
                'type' => 'boolean',
                'null' => false,
                'default' => false,
            ],
            'can_pick_up' => [
                'type' => 'boolean',
                'null' => false,
                'default' => true,
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => null,
            ],
        ],
        'indexes' => [
            'idx_parent_students_student_id' => [
                'type' => 'index',
                'columns' => ['student_id'],
            ],
            'idx_parent_students_relationship' => [
                'type' => 'index',
                'columns' => ['relationship_to_student'],
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => ['parent_id', 'student_id'],
            ],
        ],
    ];
}

if (!isset($schema['notifications'])) {
    $schema['notifications'] = [
        'table' => 'notifications',
        'columns' => [
            'id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => null,
                'autoIncrement' => true,
            ],
            'user_id' => [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
                'default' => null,
            ],
            'title' => [
                'type' => 'string',
                'length' => 200,
                'null' => false,
                'default' => null,
            ],
            'message' => [
                'type' => 'text',
                'null' => false,
                'default' => null,
            ],
            'notification_type' => [
                'type' => 'string',
                'length' => 50,
                'null' => false,
                'default' => null,
            ],
            'is_read' => [
                'type' => 'boolean',
                'null' => false,
                'default' => false,
            ],
            'created' => [
                'type' => 'datetime',
                'null' => false,
                'default' => null,
            ],
        ],
        'indexes' => [
            'notifications_user_id_idx' => [
                'type' => 'index',
                'columns' => ['user_id'],
            ],
            'notifications_is_read_idx' => [
                'type' => 'index',
                'columns' => ['is_read'],
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
            ],
        ],
    ];
}

return $schema;
