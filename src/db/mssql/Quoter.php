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
    public function getTableNameParts(string $name, bool $withColumn = false): array
    {
        if (preg_match_all('/([^.\[\]]+)|\[([^\[\]]+)]/', $name, $matches)) {
            $parts = array_slice($matches[0], -4, 4);
        } else {
            $parts = [$name];
        }

        return $this->unquoteParts($parts, $withColumn);
    }

    public function quoteColumnName(string $name): string
    {
        if (preg_match('/^\[.*]$/', $name)) {
            return $name;
        }

        return parent::quoteColumnName($name);
    }
}
