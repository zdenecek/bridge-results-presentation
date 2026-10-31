<?php

/** Adds a column to a table created before the column existed; CREATE TABLE IF NOT EXISTS leaves old tables as they are. */
function schemaEnsureColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    $columns = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
        ? array_column($pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC), 'name')
        : $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($column, $columns, true)) {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
    }
}
