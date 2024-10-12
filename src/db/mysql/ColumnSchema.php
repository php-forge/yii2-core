<?php

declare(strict_types=1);

namespace yii\db\mysql;

use yii\db\ExpressionInterface;
use yii\db\JsonExpression;

use function json_decode;

/**
 * Class ColumnSchema for MySQL database
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * {@inheritdoc}
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->dbType === Schema::TYPE_JSON) {
            return $this->dbTypeCastAsJson($value);
        }

        return parent::dbTypecast($value);
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
    protected function dbTypeCastAsJson(mixed $value): ExpressionInterface|null
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
     * @return array the result of the type cast:
     * - json: the decoded `JSON` string to an array.
     */
    protected function typeCastAsArray(mixed $value): array
    {
        return json_decode($value, true);
    }
}
