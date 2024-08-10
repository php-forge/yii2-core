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
    public $isComputed;

    /**
     * Prepares default value and converts it according to [[phpType]].
     *
     * @param mixed $value default value.
     *
     * @return mixed converted value.
     */
    public function defaultPhpTypecast($value)
    {
        if ($value !== null) {
            // convert from MSSQL column_default format, e.g. ('1') -> 1, ('string') -> string
            $value = substr(substr($value, 2), 0, -2);
        }

        return parent::phpTypecast($value);
    }

    public function dbTypecast(mixed $value): mixed
    {
        if ($this->type === Schema::TYPE_BINARY && $this->dbType === 'varbinary') {
            if ($value instanceof PdoValue && is_string($value->getValue())) {
                $value = (string) $value->getValue();
            }

            if (is_string($value)) {
                return new Expression('CONVERT(VARBINARY(MAX), ' . ('0x' . bin2hex($value)) . ')');
            }
        }

        return parent::dbTypecast($value);
    }
}
