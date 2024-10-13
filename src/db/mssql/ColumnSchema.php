<?php

declare(strict_types=1);

namespace yii\db\mssql;

use yii\db\Expression;
use yii\db\PdoValue;

use function bin2hex;
use function is_string;
use function preg_match;

/**
 * Class ColumnSchema for MSSQL database
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * @var bool whether this column is a computed column.
     */
    public bool $isComputed = false;

    /**
     * Parses the default value from the provided input.
     *
     * This method processes the input to extract a default value. If the value is wrapped in quotes or parentheses,
     *
     * it recursively processes the value to return the underlying default value. If the value is `null`, it will return
     * `null`. Otherwise, the value is converted to a string.
     *
     * @param mixed $value the value to parse, which could be `null`, a string, or any other type.
     *
     * @return string|null the parsed default value.
     */
    public function parseDefaultValue(mixed $value): string|null
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        if (preg_match('/^\'(.*)\'$/', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^\((.*)\)$/', $value, $matches)) {
            return $this->parseDefaultValue($matches[1]);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->dbType === 'varbinary') {
            return $this->dbTypeCastAsVarbinary($value);
        }

        return parent::dbTypecast($value);
    }

    /**
     * Typecasts a value to a `VARBINARY` database type.
     *
     * If the value is a string, it converts it into a hexadecimal representation and wraps it in an SQL expression for
     * converting to `VARBINARY(MAX)`.
     *
     * @param mixed $value the value to be typecast.
     *
     * @return Expression the SQL expression representing the binary value in `VARBINARY` format.
     */
    protected function dbTypeCastAsVarbinary(mixed $value): mixed
    {
        if ($value instanceof PdoValue && is_string($value->getValue())) {
            $value = (string) $value->getValue();
        }

        if (is_string($value)) {
            return new Expression('CONVERT(VARBINARY(MAX), ' . ('0x' . bin2hex($value)) . ')');
        }

        return $value;
    }
}
