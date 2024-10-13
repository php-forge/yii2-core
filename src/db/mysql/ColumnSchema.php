<?php

declare(strict_types=1);

namespace yii\db\mysql;

use yii\db\{Expression, ExpressionInterface, JsonExpression};

use function bindec;
use function in_array;
use function json_decode;
use function preg_match;
use function trim;

/**
 * Class ColumnSchema for `MySQL` database
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * {@inheritdoc}
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->dbType === Schema::TYPE_JSON) {
            return $this->dbTypecastAsJson($value);
        }

        return parent::dbTypecast($value);
    }

    /**
     * Normalizes the default value from the provided input.
     *
     * This method processes and normalizes the default value based on the column type and the provided value.
     *
     * It handles specific cases for `timestamp`, `datetime`, `date`, and `time` types, especially differences
     * in how `CURRENT_TIMESTAMP` is represented in `MariaDB` versions. For `CURRENT_TIMESTAMP`, it returns an
     * `Expression`. Additionally, for `bit` types, the value is converted from `binary` to `decimal`. Other values
     * are typecast based on the column's PHP type.
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

        /**
         * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed
         * as CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as `current_timestamp()` from MariaDB 10.2.3.
         *
         * See details here: https://mariadb.com/kb/en/library/now/#description
         */
        if (
            in_array($this->type, ['timestamp', 'datetime', 'date', 'time'], true) &&
            preg_match('/^current_timestamp(?:\(([0-9]*)\))?$/i', $value, $matches)
        ) {
            return new Expression('CURRENT_TIMESTAMP' . (!empty($matches[1]) ? '(' . $matches[1] . ')' : ''));
        }

        if (stripos($this->dbType, 'bit') !== false && $value !== '') {
            return bindec(trim($value, 'b\''));
        }

        return $this->phpTypecast($value);
    }

    /**
     * Typecasts a value to a `JsonExpression` for the `JSON` database type.
     *
     * If the value is already a `JsonExpression`, it returns it as-is.
     * If the value is `null`, it is returned unchanged.
     * Otherwise, it converts the value into a `JsonExpression`, ensuring it is properly handled as a `JSON` value in
     * the database.
     *
     * @param mixed $value the value to be typecast.
     *
     * @return ExpressionInterface|null the typecasted value as a `JsonExpression` or `null`.
     */
    protected function dbTypecastAsJson(mixed $value): ExpressionInterface|null
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        return new JsonExpression($value, $this->type);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed the result of the type cast:
     * - json: the decoded `JSON` string to an array.
     */
    protected function typecastAsArray(mixed $value): mixed
    {
        return json_decode($value, true);
    }
}
