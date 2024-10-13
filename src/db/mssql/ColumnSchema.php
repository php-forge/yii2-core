<?php

declare(strict_types=1);

namespace yii\db\mssql;

use yii\db\{Expression, ExpressionInterface, PdoValue};

use function bin2hex;
use function is_string;
use function preg_match;

/**
 * Class ColumnSchema for `MSSQL` database
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * @var bool whether this column is a computed column.
     */
    public bool $isComputed = false;

    /**
     * {@inheritdoc}
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->dbType === 'varbinary') {
            return $this->dbTypecastAsVarbinary($value);
        }

        return parent::dbTypecast($value);
    }

    /**
     * Normalizes the default value from the provided input.
     *
     * This method processes the input to normalize a default value. If the value is wrapped in quotes or parentheses,
     * it recursively processes the value to extract the underlying default value. If the value is `null`, it returns
     * `null`. Otherwise, the value is converted to a string.
     *
     * @param mixed $value the value to normalize, which could be `null`, a `string`, or any other type.
     *
     * @return mixed the normalized default value, or `null` if the input is `null`, or a typecast value.
     */
    public function normalizeDefaultValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        if (preg_match('/^\'(.*)\'$/', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^\((.*)\)$/', $value, $matches)) {
            return $this->normalizeDefaultValue($matches[1]);
        }

        return $this->phpTypecast($value);
    }

    /**
     * Typecasts a value to a `VARBINARY` database type.
     *
     * If the value is a string, it converts it into a hexadecimal representation and wraps it in an SQL expression for
     * converting to `VARBINARY(MAX)`.
     *
     * @param mixed $value the value to be typecast.
     *
     * @return ExpressionInterface the SQL expression representing the binary value in `VARBINARY` format.
     */
    protected function dbTypecastAsVarbinary(mixed $value): ExpressionInterface
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
