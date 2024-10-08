<?php

declare(strict_types=1);

namespace yii\db\pgsql;

use yii\db\ArrayExpression;
use yii\db\ExpressionInterface;
use yii\db\JsonExpression;
use yii\db\PdoValue;

/**
 * Class ColumnSchema for PostgreSQL database.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
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
    public function dbTypecast($value)
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if ($this->dimension > 0) {
            return new ArrayExpression($value, $this->dbType, $this->dimension);
        }

        if (in_array($this->dbType, [Schema::TYPE_JSON, Schema::TYPE_JSONB], true)) {
            return new JsonExpression($value, $this->dbType);
        }

        if (is_string($value) && $this->type === Schema::TYPE_BINARY) {
            return new PdoValue($value, \PDO::PARAM_LOB);
        }

        return $this->typecast($value);
    }

    /**
     * {@inheritdoc}
     */
    public function phpTypecast($value)
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
     * Casts $value after retrieving from the DBMS to PHP representation.
     *
     * @param string|null $value
     * @return bool|mixed|null
     */
    protected function phpTypecastValue($value)
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
                    default => (bool)$value,
                };
            case Schema::TYPE_JSON:
                return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        }

        return parent::phpTypecast($value);
    }

    /**
     * Creates instance of ArrayParser
     *
     * @return ArrayParser
     */
    protected function getArrayParser()
    {
        static $parser = null;

        if ($parser === null) {
            $parser = new ArrayParser();
        }

        return $parser;
    }
}
