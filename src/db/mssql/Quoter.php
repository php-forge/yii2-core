<?php

declare(strict_types=1);

namespace yii\db\mssql;

use function array_slice;
use function preg_match;
use function preg_match_all;

/**
 * Implements the MSSQL Server quoting and unquoting methods.
 */
final class Quoter extends \yii\db\Quoter
{
    /**
     * {@inheritdoc}
     */
    public function extractAlias(string $tableName): array|false
    {
        if (preg_match('/^\[.*\]$/', $tableName)) {
            return false;
        }

        return parent::extractAlias($tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableNameParts(string $tableName, bool $withColumn = false): array
    {
        if (preg_match_all('/([^.\[\]]+)|\[([^\[\]]+)]/', $tableName, $matches)) {
            $parts = array_slice($matches[0], -4, 4);
        } else {
            $parts = [$tableName];
        }

        return $this->unquoteParts($parts, $withColumn);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteColumnName(string $columnName): string
    {
        if (preg_match('/^\[.*]$/', $columnName)) {
            return $columnName;
        }

        return parent::quoteColumnName($columnName);
    }
}
