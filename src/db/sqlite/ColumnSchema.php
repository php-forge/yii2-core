<?php

declare(strict_types=1);

namespace yii\db\sqlite;

use yii\db\Expression;

/**
 * Class ColumnSchema for `SQLite` databases.
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * Normalizes the default value from the provided input.
     *
     * This method processes and normalizes the default value based on the column type and the provided value.
     * It treats values such as the string 'null', empty strings, and actual `null` as equivalent to `null`.
     * If the value is 'CURRENT_TIMESTAMP' for a `timestamp` type, it returns an `Expression`.
     * For other values, it trims any surrounding single or double quotes from the value before returning it.
     *
     * @param mixed $value the value to normalize, which could be `null`, a string, or any other type.
     *
     * @return mixed the normalized default value, which could be `null`, an `Expression`, or a typecast value.
     */
    public function normalizeDefaultValue(mixed $value): mixed
    {
        if ($value === 'null' || $value === '' || $value === null) {
            return null;
        }

        if ($this->type === 'timestamp' && $value === 'CURRENT_TIMESTAMP') {
            return new Expression('CURRENT_TIMESTAMP');
        }

        $value = (string) $value;
        $trimmedValue = trim($value, "'\"");

        return $this->phpTypecast($trimmedValue);
    }
}
