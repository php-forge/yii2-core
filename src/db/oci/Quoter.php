<?php

declare(strict_types=1);

namespace yii\db\oci;

use function str_contains;

/**
 * Implements the Oracle Server quoting and unquoting methods.
 */
final class Quoter extends \yii\db\Quoter
{
    /**
     * {@inheritdoc}
     */
    public function quoteSimpleTableName(string $tableName): string
    {
        return str_contains($tableName, '"') ? $tableName : '"' . $tableName . '"';
    }
}
