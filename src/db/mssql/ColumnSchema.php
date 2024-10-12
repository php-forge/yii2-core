<?php

declare(strict_types=1);

namespace yii\db\mssql;

use yii\db\Expression;
use yii\db\PdoValue;

use function bin2hex;
use function is_string;
use function substr;

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
     * Prepares default value and converts it according to [[phpType]].
     *
     * @param mixed $value default value.
     *
     * @return mixed converted value.
     */
    public function defaultPhpTypecast(mixed $value): mixed
    {
        if ($value !== null) {
            // convert from MSSQL column_default format, e.g. ('1') -> 1, ('string') -> string
            $value = substr(substr($value, 2), 0, -2);
        }

        return parent::phpTypecast($value);
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
