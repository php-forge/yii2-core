<?php

declare(strict_types=1);

namespace yii\db\mssql;

use yii\base\InvalidArgumentException;
use yii\db\Connection;

/**
 * Command represents an MSSQL SQL statement to be executed against a database.
 *
 * {@inheritdoc}
 */
class Command extends \yii\db\Command
{
    public function executeResetSequence(string $table, mixed $value = null, array $options = []): false|int
    {
        if ($value === null) {
            $value = $this->getNextAutoIncrementValue($table);
        }

        $sql = $this->db->queryBuilder->resetSequence($table, $value, $options);

        $result = $this->setSql($sql)->execute();

        if ($result === false) {
            return false;
        }

        return $value;
    }
}
