<?php

declare(strict_types=1);

namespace yii\db;

use yii\base\BaseObject;
use yii\helpers\StringHelper;

/**
 * ColumnSchema class describes the metadata of a column in a database table.
 */
class ColumnSchema extends BaseObject
{
    /**
     * @var string name of this column (without quotes)
     */
    public string $name = '';
    /**
     * @var string abstract type of this column.
     * Possible abstract types include:
     * - `char`, `string`, `text`, `boolean`, `smallint`, `integer`, `bigint`, `float`, `decimal`, `datetime`,
     *   `timestamp`, `time`, `date`, `binary`, `money`, `json`, `uuid`, and `uuid_pk`
     */
    public string $type = '';
    /**
     * @var string the PHP type of this column. Possible PHP types include:
     * `PHPType::INTEGER`, `PHPType::STRING`, `PHPType::BOOLEAN`, `PHPType::DOUBLE`, `PHPType::RESOURCE`,
     * `PHPType::ARRAY`, `PHPType::OBJECT`, and `PHPType::NULL`
     *
     * @see [[\yii\db\enum\PHPType]]
     */
    public string $phpType = 'string';
    /**
     * @var string the DB type of this column. Possible DB types vary according to the type of DBMS
     */
    public string $dbType = '';
    /**
     * @var bool whether this column is a primary key
     */
    public bool $isPrimaryKey = false;
    /**
     * @var bool whether this column is auto-incremental
     */
    public bool $autoIncrement = false;
    /**
     * @var bool whether this column is unique constraint
     */
    public bool $isUnique = false;
    /**
     * @var string|null whether this column is check constraint
     */
    public string|null $checkExpression = null;
    /**
     * @var mixed default value of this column
     */
    public mixed $defaultValue = null;
    /**
     * @var bool whether this column can be null
     */
    public bool $allowNull = false;
    /**
     * @var int|null display size of the column
     */
    public int|null $size = null;
    /**
     * @var int|null precision of the column data, if it is numeric
     */
    public $precision;
    /**
     * @var int|null scale of the column data, if it is numeric
     */
    public int|null $scale = null;
    /**
     * @var bool whether this column is unsigned
     * This is only meaningful when [[type]] is `smallint`, `integer` or `bigint`
     */
    public bool $unsigned = false;
    /**
     * @var array enumerable values. This is set only if the column is declared to be an enumerable type
     */
    public array $enumValues = [];
    /**
     * @var string|null comment of this column. Not all DBMS support this
     */
    public string|null $comment = null;

    /**
     * Converts the input value according to [[phpType]] after retrieval from the database.
     * If the value is `null` or an [[Expression]], it will not be converted.
     *
     * @param mixed $value input value.
     *
     * @return mixed converted value.
     */
    public function phpTypecast(mixed $value): mixed
    {
        return $this->typecast($value);
    }

    /**
     * Converts the input value according to [[type]] and [[dbType]] for use in a db query.
     * If the value is `null` or an [[Expression]], it will not be converted.
     *
     * @param mixed $value input value.
     *
     * @return mixed converted value. This may also be an array containing the value as the first element and the PDO
     * type as the second element.
     */
    public function dbTypecast(mixed $value): mixed
    {
        // the default implementation does the same as casting for PHP, but it should be possible
        // to override this with annotation of explicit PDO type.
        return $this->typecast($value);
    }

    /**
     * Converts the input value according to [[phpType]] after retrieval from the database.
     * If the value is `null` or an [[Expression]], it will not be converted.
     *
     * @param mixed $value input value.
     *
     * @return mixed converted value.
     */
    protected function typecast(mixed $value): mixed
    {
        if ($value === null || $this->isEmptyValueForNonTextType($value)) {
            return null;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        return match ($this->phpType) {
            'array' => $this->typeCastAsArray($value),
            'boolean' => $this->typeCastAsBoolean($value),
            'double' => $this->typeCastAsDouble($value),
            'integer' => $this->typeCastAsInteger($value),
            'resource' => $this->typeCastAsResource($value),
            'string' => $this->typeCastAsString($value),
            default => $value,
        };
    }

    /**
     * Checks if the given value is an empty string and the column type is not a text type.
     *
     * This method is used to determine if an empty string value should be treated as `null` for non-text type columns.
     *
     * @param mixed $value the value to check.
     *
     * @return bool `true` if the value is an empty string and the column is not a text type, false otherwise.
     */
    protected function isEmptyValueForNonTextType(mixed $value): bool
    {
        $textTypes = [
            Schema::TYPE_BINARY,
            Schema::TYPE_CHAR,
            Schema::TYPE_STRING,
            Schema::TYPE_TEXT,
        ];

        return ($value === '' && (is_string($value) && trim($value) === ''))
            && !in_array($this->type, $textTypes, true);
    }

    /**
     * Converts a given database value to an `array` type.
     *
     * @param mixed $value the value to be cast. Can be a `string`, `number`, `boolean`, or `array`.
     *
     * @return mixed the result of the type cast.
     */
    protected function typecastAsArray(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Converts a given database value to a `boolean` type.
     *
     * @param mixed $value the value to be cast. Can be a `string`, `number`, `boolean`.
     *
     * @return bool the result of the type cast:
     * - bool: `false` for strings `'false'`, `'0'`, or any value that equates to false, `true` for strings `'1'`, or
     *   any non-empty, non-null value that equates to true.
     */
    protected function typecastAsBoolean(mixed $value): bool
    {
        // treating a 0 bit value as false too
        // https://github.com/yiisoft/yii2/issues/9006
        return (bool) $value && $value !== "\0" && strtolower((string) $value) !== 'false';
    }

    /**
     * Converts a given database value to a `double` type.
     *
     * @param mixed $value the value to be cast. Can be a `string`, `number`.
     *
     * @return float the result of the type cast:
     * - float: the float representation of a numeric value for numeric column types.
     */
    protected function typecastAsDouble(mixed $value): float
    {
        return (float) $value;
    }

    /**
     * Converts a given database value to an `integer` type.
     *
     * @param mixed $value the value to be cast. Can be a `string`, `number`, or a `BackedEnum` instance.
     *
     * @return mixed the result of the type cast:
     * - [[BackedEnum]]: the `integer` value of the enum.
     * - int: the integer representation of a numeric value for numeric column types.
     */
    protected function typecastAsInteger(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return (int) $value->value;
        }

        return (int) $value;
    }

    /**
     * Converts a given database value to a `resource` type.
     *
     * @param mixed $value the value to be cast. Can be a `resource`.
     *
     * @return mixed the result of the type cast.
     */
    protected function typecastAsResource(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Converts a given database value to a `string` type.
     *
     * @param mixed $value the value to be cast. Can be a `string`, `number` or `boolean`.
     *
     * @return mixed the result of the type cast:
     * - string: the string representation of a float with '.' as decimal separator.
     * - int: the integer representation of a numeric value for numeric column types.
     * - string: the string value of a BackedEnum instance.
     * - string: the string representation of any other value type.
     */
    protected function typecastAsString(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_float($value)) {
            // ensure type cast always has . as decimal separator in all locales
            return StringHelper::floatToString($value);
        }

        if (
            is_numeric($value) &&
            ColumnSchemaBuilder::CATEGORY_NUMERIC === ColumnSchemaBuilder::$typeCategoryMap[$this->type]
        ) {
            // https://github.com/yiisoft/yii2/issues/14663
            return $value;
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }

    /**
     * @return array array of numbers that represent possible PDO parameter types.
     *
     * @psalm-return int[] array of numbers that represent possible PDO parameter types.
     */
    private function getPdoParamTypes(): array
    {
        return [
            \PDO::PARAM_BOOL,
            \PDO::PARAM_INT,
            \PDO::PARAM_STR,
            \PDO::PARAM_LOB,
            \PDO::PARAM_NULL,
            \PDO::PARAM_STMT
        ];
    }
}
