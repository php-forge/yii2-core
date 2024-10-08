<?php

declare(strict_types=1);

namespace yii\db\sqlite;

use function array_slice;
use function explode;

/**
 * Implements the SQLite quoting and unquoting methods.
 */
final class Quoter extends \yii\db\Quoter
{
    /**
     * {@inheritdoc}
     */
    public function getTableNameParts(string $tableName, bool $withColumn = false): array
    {
        $parts = array_slice(explode('.', $tableName), -2, 2);

        return $this->unquoteParts($parts, $withColumn);
    }
}
