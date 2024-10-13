<?php

declare(strict_types=1);

namespace yii\db\pgsql;

use yii\db\{ArrayExpression, ExpressionInterface, JsonExpression, PdoValue};

/**
 * Class ColumnSchema for `PostgreSQL` database.
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * @var int the dimension of array. Defaults to 0, means this column is not an array.
     */
    public int $dimension = 0;
    /**
     * @var string|null name of associated sequence if column is auto-incremental
     */
    public string|null $sequenceName = null;

    /**
     * {@inheritdoc}
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->dimension > 0) {
            return $this->dbTypecastAsArray($value);
        }

        return $this->typecast($value);
    }

    /**
     * {@inheritdoc}
     */
    public function phpTypecast(mixed $value): mixed
    {
        if ($this->dimension > 0) {
            if (!is_array($value) && (is_string($value) || $value === null)) {
                $value = $this->getArrayParser()->parse($value);
            }

            if (is_array($value)) {
                array_walk_recursive($value, function (&$val, $key) {
                    $val = $this->phpTypecastValue($val);
                });
            } elseif ($value === null) {
                return null;
            }

            return $value;
        }

        return $this->phpTypecastValue($value);
    }

    /**
     * Creates instance of ArrayParser.
     */
    protected function getArrayParser()
    {
        return new ArrayParser();
    }

    /**
     * Casts value after retrieving from the DBMS to PHP representation.
     */
    protected function phpTypecastValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        switch ($this->type) {
            case Schema::TYPE_BOOLEAN:
                /** @psalm-var mixed $value */
                $value = is_string($value) ? strtolower($value) : $value;

                return match ($value) {
                    't', 'true' => true,
                    'f', 'false' => false,
                    default => (bool) $value,
                };
            case Schema::TYPE_JSON:
                return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        }

        return parent::phpTypecast($value);
    }

    /**
     * Typecasts a value to an array expression for the `ARRAY` database type.
     *
     * @param mixed $value the value to be typecast.
     *
     * @return ExpressionInterface|null the SQL expression representing the array value.
     */
    protected function dbTypecastAsArray($value): ExpressionInterface|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        return new ArrayExpression($value, $this->dbType, $this->dimension);
    }

    /**
     * {@inheritdoc}
     */
    protected function typecastAsArray(mixed $value): mixed
    {
        if (in_array($this->dbType, [Schema::TYPE_JSON, Schema::TYPE_JSONB], true)) {
            return new JsonExpression($value, $this->dbType);
        }

        return parent::typeCastAsArray($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function typecastAsResource(mixed $value): mixed
    {
        if ($this->type === Schema::TYPE_BINARY && is_string($value)) {
            return new PdoValue($value, \PDO::PARAM_LOB);
        }

        return parent::typeCastAsResource($value);
    }
}
