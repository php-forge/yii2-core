<?php

declare(strict_types=1);

namespace yii\db\mysql;

/**
 * Command represents an MySQL/MariaDB SQL statement to be executed against a database.
 */
class Command extends \yii\db\Command
{
    public function insertWithReturningPks(string $table, array $columns): array|bool|int
    {
        $params = [];
        $result = [];

        $sql = $this->db->getQueryBuilder()->insert($table, $columns, $params);
        $this->setSql($sql)->bindValues($params);

        $result = $this->execute();

        if ($result === false) {
            return false;
        }

        $tableSchema = $this->db->getTableSchema($table);
        $tablePrimaryKeys = $tableSchema->primaryKey ?? [];

        if (empty($tablePrimaryKeys)) {
            return $result;
        }

        $result = [];

        foreach ($tablePrimaryKeys as $name) {
            if ($tableSchema->getColumn($name)?->autoIncrement) {
                $result[$name] = $this->db->getLastInsertID((string) $tableSchema->sequenceName);

                continue;
            }

            /** @psalm-var mixed */
            $result[$name] = $columns[$name] ?? $tableSchema->getColumn($name)?->defaultValue;
        }

        return $result;
    }
}
