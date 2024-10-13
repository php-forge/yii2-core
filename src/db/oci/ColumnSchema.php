<?php

declare(strict_types=1);

namespace yii\db\oci;

use yii\db\{Expression, ExpressionInterface};

use function is_string;
use function preg_replace;
use function strlen;
use function strncmp;
use function stripos;
use function substr;
use function trim;
use function uniqid;

/**
 * Class ColumnSchema for `Oracle` databases.
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * @var string|null name of associated sequence if column is auto-incremental
     */
    public string|null $sequenceName = null;

    /**
     * {@inheritdoc}
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->dbType === 'BLOB' && is_string($value)) {
            return $this->dbTypecastAsBlob($value);
        }

        return parent::dbTypecast($value);
    }

    /**
     * Normalizes the default value from the provided input.
     *
     * This method processes and normalizes the default value based on the column type and the provided value.
     *
     * It handles specific cases for `timestamp` types, removing default `timestamp` values when appropriate.
     *
     * For `CURRENT_TIMESTAMP`, it returns a corresponding `Expression`. If the value is enclosed in single quotes,
     * the quotes are stripped. Otherwise, the value is typecast using the column's PHP type.
     *
     * @param mixed $value the value to normalize, which could be `null`, a `string`, or any other type.
     *
     * @return mixed the normalized default value, which could be `null`, an `Expression`, or a typecast value.
     */
    public function normalizeDefaultValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = (string) trim($value);

        if (stripos($value, 'timestamp') !== false) {
            return null;
        }

        if ($this->type === 'timestamp' && $value === 'CURRENT_TIMESTAMP') {
            return new Expression('CURRENT_TIMESTAMP');
        }

        if (strlen($value) > 2 && strncmp($value, "'", 1) === 0 && substr($value, -1) === "'") {
            return substr($value, 1, -1);
        }

        return $this->phpTypecast($value);
    }

    /**
     * Typecasts a value to a `BLOB` for the `BLOB` database type.
     *
     * If the value is a string, it generates a unique placeholder and returns an SQL expression that converts the
     * string into a `BLOB` using `TO_BLOB` and `UTL_RAW.CAST_TO_RAW`.
     *
     * @param mixed $value the value to be typecast.
     *
     * @return ExpressionInterface the typecasted value as a `BLOB`.
     */
    protected function dbTypecastAsBlob(mixed $value): ExpressionInterface
    {
        $placeholder = uniqid('exp_' . preg_replace('/[^a-z0-9]/i', '', $this->name));

        return new Expression('TO_BLOB(UTL_RAW.CAST_TO_RAW(:' . $placeholder . '))', [$placeholder => $value]);
    }
}
