<?php

declare(strict_types=1);

namespace yii\db\mssql;

use yii\db\Connection;
use yii\db\Expression;

/**
 * ColumnSchemaBuilder is the schema builder for MSSQL databases.
 *
 * @property-read string|null $checkValue The `CHECK` constraint for the column.
 * @property-read string|Expression|null $defaultValue Default value of the column.
 */
class ColumnSchemaBuilder extends \yii\db\ColumnSchemaBuilder
{
    /**
     * @var string The format string for the column's schema.
     */
    protected $format = '{type}{length}{notnull}{unique}{default}{check}{append}';

    /**
     * The column types that are auto-incremental or primary key.
     */
    private const CATEGORY_GROUP_AUTO_PK = [
        self::CATEGORY_AUTO,
        self::CATEGORY_BIGAUTO,
        self::CATEGORY_PK,
        self::CATEGORY_BIGPK,
    ];

    /**
     * @var array The parameters for the IDENTITY column.
     */
    private array $identityParams = [];

    public function __construct(Connection $db, string|null $type = null, $length = null, $config = [])
    {
        if ((in_array($type, self::CATEGORY_GROUP_AUTO_PK)) && is_array($length) && count($length) === 2) {
            // start, increment. If increment is 0, it will be set to 1.
            if ($length[1] === 0) {
                $length[1] = 1;
            }

            $this->identityParams = $length;
        }

        parent::__construct($db, $type, $length, $config);
    }

    /**
     * Builds the full string for the column's schema.
     *
     * @return string
     */
    public function __toString(): string
    {
        $format = $this->format;

        if (in_array($this->getTypeCategory(), self::CATEGORY_GROUP_AUTO_PK)) {
            $format = '{type}{identity}{check}{comment}{append}';
        }

        return $this->buildCompleteString($format);
    }

    /**
     * Creates an `IDENTITY` column.
     *
     * @param int|null $start The start value for the identity column. if null, it will be set to 1.
     * @param int|null $increment The increment value for the identity column. if null, it will be set to 1.
     *
     * @return self The column schema builder instance.
     */
    public function autoIncrement(int $start = null, int $increment = null): self
    {
        $this->type = self::CATEGORY_AUTO;

        if ($start !== null && $increment !== null) {
            $this->identityParams = [$start, $increment === 0 ? 1 : $increment];
        }

        return $this;
    }

    /**
     * Creates a `BIGINT IDENTITY` column.
     *
     * @param int|null $start The start value for the identity column. if null, it will be set to 1.
     * @param int|null $increment The increment value for the identity column. if null, it will be set to 1.
     *
     * @return self The column schema builder instance.
     */
    public function bigAutoIncrement(int $start = null, int $increment = null): self
    {
        $this->type = self::CATEGORY_BIGAUTO;

        if ($start !== null && $increment !== null) {
            $this->identityParams = [$start, $increment === 0 ? 1 : $increment];
        }

        return $this;
    }

    /**
     * Creates a `BIGINT IDENTITY PRIMARY KEY` column.
     *
     * @param int|null $start The start value for the identity column. if null, it will be set to 1.
     * @param int|null $increment The increment value for the identity column. if null, it will be set to 1.
     *
     * @return self The column schema builder instance.
     */
    public function bigPrimaryKey(int $start = null, int $increment = null): self
    {
        $this->type = self::CATEGORY_BIGPK;

        if ($start !== null && $increment !== null) {
            $this->identityParams = [$start, $increment === 0 ? 1 : $increment];
        }

        return $this;
    }

    /**
     * Creates an `INTEGER IDENTITY PRIMARY KEY` column.
     *
     * @param int|null $start The start value for the identity column. if null, it will be set to 1.
     * @param int|null $increment The increment value for the identity column. if null, it will be set to 1.
     *
     * @return self The column schema builder instance.
     */
    public function primaryKey(int $start = null, int $increment = null): self
    {
        $this->type = self::CATEGORY_PK;

        if ($start !== null && $increment !== null) {
            $this->identityParams = [$start, $increment === 0 ? 1 : $increment];
        }

        return $this;
    }

    /**
     * Changes default format string to MSSQL ALTER COMMAND.
     */
    public function setAlterColumnFormat(): void
    {
        $this->format = '{type}{length}{notnull}{append}';
    }

    /**
     * Getting the `Default` value for constraint.
     *
     * @return string|Expression|null default value of the column.
     */
    public function getDefaultValue(): string|Expression|null
    {
        if ($this->default instanceof Expression) {
            return $this->default;
        }

        return $this->buildDefaultValue();
    }

    /**
     * Get the `Check` value for constraint.
     *
     * @return string|null the `CHECK` constraint for the column.
     */
    public function getCheckValue(): string|null
    {
        return $this->check !== null ? (string) $this->check : null;
    }

    /**
     * @return bool whether the column values should be unique. If this is `true`, a `UNIQUE` constraint will be added.
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildCompleteString($format): string
    {
        $placeholderValues = [
            '{type}' => $this->type,
            '{length}' => $this->buildLengthString(),
            '{unsigned}' => $this->buildUnsignedString(),
            '{notnull}' => $this->buildNotNullString(),
            '{unique}' => $this->buildUniqueString(),
            '{default}' => $this->buildDefaultString(),
            '{check}' => $this->buildCheckString(),
            '{comment}' => $this->buildCommentString(),
            '{pos}' => $this->isFirst ? $this->buildFirstString() : $this->buildAfterString(),
            '{append}' => $this->buildAppendString(),
            '{identity}' => $this->buildIdentityString(),
        ];

        return strtr($format, $placeholderValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildIdentityString(): string
    {
        if (in_array($this->type, self::CATEGORY_GROUP_AUTO_PK) && $this->identityParams !== []) {
            return "({$this->identityParams[0]},{$this->identityParams[1]})";
        }

        return '';
    }
}
