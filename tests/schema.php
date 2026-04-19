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
        ],
    ];
}

return $schema;
