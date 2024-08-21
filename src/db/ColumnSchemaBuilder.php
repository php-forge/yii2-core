<?php

declare(strict_types=1);

namespace yii\db;

use yii\base\BaseObject;
use yii\helpers\StringHelper;

/**
 * ColumnSchemaBuilder helps to define database schema types using a PHP interface.
 *
 * See [[SchemaBuilderTrait]] for more detailed description and usage examples.
 *
 * @property array $categoryMap Mapping of abstract column types (keys) to type categories (values).
 */
class ColumnSchemaBuilder extends BaseObject implements \Stringable
{
    /**
     * Abstract column type for `INTEGER AUTO_INCREMENT`.
     */
    public const CATEGORY_AUTO = 'auto';

    /**
     * Abstract column type for `BIGINT AUTO_INCREMENT`.
     */
    public const CATEGORY_BIGAUTO = 'bigauto';

    /**
     * Abstract column type for `INTEGER AUTO_INCREMENT PRIMARY KEY`.
     */
    public const CATEGORY_PK = 'pk';

    /**
     * Abstract column type for `BIGINT AUTO_INCREMENT PRIMARY KEY`.
     */
    public const CATEGORY_BIGPK = 'bigpk';

    /**
     * Abstract column type for `STRING` columns.
     */
    public const CATEGORY_STRING = 'string';

    /**
     * Abstract column type for `NUMERIC` columns.
     */
    public const CATEGORY_NUMERIC = 'numeric';

    /**
     * Abstract column type for `TIME` columns.
     */
    public const CATEGORY_TIME = 'time';

    /**
     * Abstract column type for `OTHER` columns.
     */
    public const CATEGORY_OTHER = 'other';

    /**
     * The column types that are auto-incremental or primary key.
     */
    public const CATEGORY_GROUP_AUTO_PK = [
        self::CATEGORY_AUTO,
        self::CATEGORY_BIGAUTO,
        self::CATEGORY_PK,
        self::CATEGORY_BIGPK,
    ];

    /**
     * @var mixed SQL string to be appended to column schema definition.
     */
    protected mixed $append = null;

    /**
     * @var string|null the `CHECK` constraint for the column.
     */
    protected string|null $check = null;

    /**
     * @var Connection the database connection. This is used mainly to escape table and column names.
     */
    protected Connection $db;

    /**
     * @var mixed default value of the column.
     */
    protected mixed $default = null;

    /**
     * @var bool|null whether the column is or not nullable. If this is `true`, a `NOT NULL` constraint will be added.
     * If this is `false`, a `NULL` constraint will be added.
     */
    protected bool|null $isNotNull = null;

    /**
     * @var bool whether the column values should be unique. If this is `true`, a `UNIQUE` constraint will be added.
     */
    protected bool $isUnique = false;

    /**
     * @var array|int|string|null column size or precision definition. This is what goes into the parenthesis after
     * the column type. This can be either a string, an integer or an array. If it is an array, the array values will
     * be joined into a string separated by comma.
     */
    protected array|int|string|null $length = null;

    /**
     * @var string|null the column type definition such as INTEGER, VARCHAR, DATETIME, etc.
     */
    protected string|null $type = null;

    /**
     * @var string|null comment value of the column.
     */
    public string|null $comment = null;

    /**
     * @var array mapping of abstract column types (keys) to type categories (values).
     */
    public static array $typeCategoryMap = [
        // auto-incremental column types
        Schema::TYPE_AUTO => self::CATEGORY_AUTO,
        Schema::TYPE_BIGAUTO => self::CATEGORY_BIGAUTO,

        // integer auto-incremental primary key column types
        Schema::TYPE_PK => self::CATEGORY_PK,
        Schema::TYPE_UPK => self::CATEGORY_PK,

        // big integer auto-incremental primary key column types
        Schema::TYPE_BIGPK => self::CATEGORY_BIGPK,
        Schema::TYPE_UBIGPK => self::CATEGORY_BIGPK,

        Schema::TYPE_CHAR => self::CATEGORY_STRING,
        Schema::TYPE_STRING => self::CATEGORY_STRING,
        Schema::TYPE_TEXT => self::CATEGORY_STRING,
        Schema::TYPE_TINYINT => self::CATEGORY_NUMERIC,
        Schema::TYPE_SMALLINT => self::CATEGORY_NUMERIC,
        Schema::TYPE_INTEGER => self::CATEGORY_NUMERIC,
        Schema::TYPE_BIGINT => self::CATEGORY_NUMERIC,
        Schema::TYPE_FLOAT => self::CATEGORY_NUMERIC,
        Schema::TYPE_DOUBLE => self::CATEGORY_NUMERIC,
        Schema::TYPE_DECIMAL => self::CATEGORY_NUMERIC,
        Schema::TYPE_DATETIME => self::CATEGORY_TIME,
        Schema::TYPE_TIMESTAMP => self::CATEGORY_TIME,
        Schema::TYPE_TIME => self::CATEGORY_TIME,
        Schema::TYPE_DATE => self::CATEGORY_TIME,
        Schema::TYPE_BINARY => self::CATEGORY_OTHER,
        Schema::TYPE_BOOLEAN => self::CATEGORY_NUMERIC,
        Schema::TYPE_MONEY => self::CATEGORY_NUMERIC,
    ];

    /**
     * Create a column schema builder instance giving the type and value precision.
     *
     * @param string $type type of the column. See [[$type]].
     * @param array|int|string|null $length length or precision of the column. See [[$length]].
     * @param Connection|null $db the current database connection. See [[$db]].
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct(
        Connection $db,
        string|null $type = null,
        array|int|string|null $length = null,
        array $config = []
    ) {
        $this->db = $db;
        $this->type = $type;
        $this->length = $length;

        parent::__construct($config);
    }

    /**
     * Builds the full string for the column's schema.
     *
     * @return string
     */
    public function __toString(): string
    {
        $format = match ($this->getTypeCategory()) {
            self::CATEGORY_PK => '{type}{check}{comment}{append}',
            default => '{type}{length}{notnull}{unique}{default}{check}{comment}{append}',
        };

        return $this->buildCompleteString($format);
    }

    /**
     * Specify additional SQL to be appended to column definition.
     * Position modifiers will be appended after column definition in databases that support them.
     *
     * @param string $sql the SQL string to be appended.
     *
     * @return static Instance of the column schema builder.
     */
    public function append(string $sql): static
    {
        $this->append = $sql;

        return $this;
    }

    /**
     * Sets a `CHECK` constraint for the column.
     *
     * @param string $check the SQL of the `CHECK` constraint to be added.
     *
     * @return static Instance of the column schema builder.
     */
    public function check(string $check): static
    {
        $this->check = $check;

        return $this;
    }

    /**
     * Specifies the comment for column.
     *
     * @param string $comment the comment.
     *
     * @return static Instance of the column schema builder.
     */
    public function comment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Specify the default SQL expression for the column.
     *
     * @param string $default the default value expression.
     *
     * @return static Instance of the column schema builder.
     */
    public function defaultExpression(string $default): static
    {
        $this->default = new Expression($default);

        return $this;
    }

    /**
     * Specify the default value for the column.
     *
     * @param mixed $default the default value.
     *
     * @return static Instance of the column schema builder.
     */
    public function defaultValue(mixed $default): static
    {
        if ($default === null) {
            $this->null();
        }

        $this->default = $default;

        return $this;
    }

    /**
     * @return array mapping of abstract column types (keys) to type categories (values).
     */
    public function getCategoryMap(): array
    {
        return static::$typeCategoryMap;
    }

    /**
     * Adds a `NOT NULL` constraint to the column.
     *
     * @return static Instance of the column schema builder.
     */
    public function notNull(): static
    {
        $this->isNotNull = true;

        return $this;
    }

    /**
     * Adds a `NULL` constraint to the column.
     *
     * @return static Instance of the column schema builder.
     */
    public function null(): static
    {
        $this->isNotNull = false;

        return $this;
    }

    /**
     * @param array $categoryMap mapping of abstract column types (keys) to type categories (values).
     */
    public function setCategoryMap(array $categoryMap): void
    {
        static::$typeCategoryMap = $categoryMap;
    }

    /**
     * Adds a `UNIQUE` constraint to the column.
     *
     * @return static Instance of the column schema builder.
     */
    public function unique(): static
    {
        $this->isUnique = true;

        return $this;
    }

    /**
     * Builds the custom string that's appended to column definition.
     *
     * @return string custom string to append.
     */
    protected function buildAppendString(): string
    {
        return $this->append !== null ? ' ' . $this->append : '';
    }

    /**
     * Builds the check constraint for the column.
     *
     * @return string a string containing the CHECK constraint.
     */
    protected function buildCheckString(): string
    {
        return $this->check !== null ? " CHECK ({$this->check})" : '';
    }

    /**
     * Builds the comment specification for the column.
     *
     * @return string a string containing the COMMENT keyword and the comment itself.
     */
    protected function buildCommentString(): string
    {
        return '';
    }

    /**
     * Returns the complete column definition from input format.
     *
     * @param string $format the format of the definition.
     * @param array $customPlaceHolder custom placeholder values.
     *
     * @return string a string containing the complete column definition.
     */
    protected function buildCompleteString(string $format, array $customPlaceHolder = []): string
    {
        $placeholderValues = [
            '{type}' => $this->type,
            '{length}' => $this->buildLengthString(),
            '{notnull}' => $this->buildNotNullString(),
            '{unique}' => $this->buildUniqueString(),
            '{default}' => $this->buildDefaultString(),
            '{check}' => $this->buildCheckString(),
            '{comment}' => $this->buildCommentString(),
            '{append}' => $this->buildAppendString(),
        ];

        $placeholderValues += $customPlaceHolder;

        return strtr($format, $placeholderValues);
    }

    /**
     * Builds the default value specification for the column.
     *
     * @return string string with default value of column.
     */
    protected function buildDefaultString(): string
    {
        $defaultValue = $this->buildDefaultValue();

        if ($defaultValue === null) {
            return '';
        }

        return ' DEFAULT ' . $defaultValue;
    }

    /**
     * Return the default value for the column.
     *
     * @return string|null string with default value of column.
     */
    protected function buildDefaultValue(): string|null
    {
        if ($this->default === null) {
            return $this->isNotNull === false ? 'NULL' : null;
        }

        return match (gettype($this->default)) {
            // ensure type cast always has . as decimal separator in all locales
            'double' => StringHelper::floatToString($this->default),
            'boolean' => $this->default ? 'TRUE' : 'FALSE',
            'integer', 'object' => (string) $this->default,
            default => "'{$this->default}'",
        };
    }

    /**
     * Builds the length/precision part of the column.
     *
     * @return string a string containing the length/precision of the column.
     */
    protected function buildLengthString()
    {
        if ($this->length === null || $this->length === []) {
            return '';
        }

        if (is_array($this->length)) {
            $this->length = implode(',', $this->length);
        }

        return "({$this->length})";
    }

    /**
     * Builds the not null constraint for the column.
     *
     * @return string returns 'NOT NULL' if [[isNotNull]] is true, 'NULL' if [[isNotNull]] is false or an empty string
     * otherwise.
     */
    protected function buildNotNullString(): string
    {
        return match ($this->isNotNull) {
            true => ' NOT NULL',
            false => ' NULL',
            default => '',
        };
    }

    /**
     * Builds the unique constraint for the column.
     *
     * @return string returns string 'UNIQUE' if [[isUnique]] is true, otherwise it returns an empty string.
     */
    protected function buildUniqueString(): string
    {
        return $this->isUnique ? ' UNIQUE' : '';
    }

    /**
     * Returns the category of the column type.
     *
     * @return string|null a string containing the column type category name.
     */
    protected function getTypeCategory(): string|null
    {
        return isset($this->categoryMap[$this->type]) ? $this->categoryMap[$this->type] : null;
    }
}
