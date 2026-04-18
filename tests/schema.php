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

return $schema;
