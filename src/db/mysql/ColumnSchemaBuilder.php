<?php

declare(strict_types=1);

namespace yii\db\mysql;

/**
 * ColumnSchemaBuilder is the schema builder for MySQL databases.
 */
class ColumnSchemaBuilder extends \yii\db\ColumnSchemaBuilder
{
    /**
     * Abstract column type for `UNSIGNED BIGINT AUTO_INCREMENT PRIMARY KEY` columns.
     */
    public const CATEGORY_UBIGPK = 'ubigpk';

    /**
     * Abstract column type for `UNSIGNED AUTO_INCREMENT PRIMARY KEY` columns.
     */
    public const CATEGORY_UPK = 'upk';

    /**
     * @var string|null the column after which this column will be added.
     */
    protected string|null $after = null;

    /**
     * @var bool whether this column is to be inserted at the beginning of the table.
     */
    protected bool $isFirst = false;

    /**
     * @var bool whether the column values should be unsigned. If this is `true`, an `UNSIGNED` keyword will be added.
     */
    protected bool $isUnsigned = false;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $format = match ($this->getTypeCategory()) {
            self::CATEGORY_PK => '{type}{length}{comment}{check}{append}{pos}',
            self::CATEGORY_NUMERIC => '{type}{length}{unsigned}{notnull}{default}{unique}{comment}{append}{pos}{check}',
            default => '{type}{length}{notnull}{default}{unique}{comment}{append}{pos}{check}',
        };

        return $this->buildCompleteString($format);
    }

    /**
     * Adds an `AFTER` constraint to the column.
     *
     * @param string $after the column after which $this column will be added.
     *
     * @return static Instance of the column schema builder.
     */
    public function after(string $after): static
    {
        $this->after = $after;

        return $this;
    }

    /**
     * Creates an `INTEGER AUTO_INCREMENT` column.
     *
     * @param int|null $length column size or precision definition.
     *
     * @return self The column schema builder instance.
     */
    public function autoIncrement(int|null $length = null): self
    {
        $this->type = self::CATEGORY_AUTO;

        if ($length !== null) {
            $this->length = $length;
        }

        return $this;
    }

    /**
     * Creates a `BIGINT AUTO_INCREMENT` column.
     *
     * @param int|null $length column size or precision definition. Defaults to 20.
     *
     * @return self The column schema builder instance.
     */
    public function bigAutoIncrement(int|null $length = null): self
    {
        $this->type = self::CATEGORY_BIGAUTO;

        if ($length !== null) {
            $this->length = $length;
        }

        return $this;
    }

    /**
     * Creates a `BIGINT AUTO_INCREMENT PRIMARY KEY` column.
     *
     * @param int|null $length column size or precision definition. Defaults to 20.
     *
     * @return self The column schema builder instance.
     */
    public function bigPrimaryKey(int|null $length = null): self
    {
        $this->type = self::CATEGORY_BIGPK;

        if ($length !== null) {
            $this->length = $length;
        }

        return $this;
    }

    /**
     * Adds an `FIRST` constraint to the column.
     *
     * @return static Instance of the column schema builder.
     */
    public function first(): static
    {
        $this->isFirst = true;

        return $this;
    }

    /**
     * Creates a `INTEGER AUTO_INCREMENT PRIMARY KEY` column.
     *
     * @param int|null $length column size or precision definition. Defaults to 11.
     *
     * @return self The column schema builder instance.
     */
    public function primaryKey(int|null $length = null): self
    {
        $this->type = self::CATEGORY_PK;

        if ($length !== null) {
            $this->length = $length;
        }

        return $this;
    }

    /**
     * Marks column as unsigned.
     *
     * @return static Instance of the column schema builder.
     */
    public function unsigned(): static
    {
        $this->type = match ($this->type) {
            Schema::TYPE_AUTO => Schema::TYPE_UAUTO,
            Schema::TYPE_BIGAUTO => Schema::TYPE_UBIGAUTO,
            Schema::TYPE_PK => Schema::TYPE_UPK,
            Schema::TYPE_BIGPK => Schema::TYPE_UBIGPK,
            default => $this->type,
        };

        $this->isUnsigned = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildUnsignedString(): string
    {
        return $this->isUnsigned ? ' UNSIGNED' : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildAfterString(): string
    {
        return $this->after !== null ? ' AFTER ' . $this->db->quoteColumnName($this->after) : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildFirstString(): string
    {
        return $this->isFirst ? ' FIRST' : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildCommentString(): string
    {
        return $this->comment !== null ? ' COMMENT ' . $this->db->quoteValue($this->comment) : '';
    }

    /**
     * Returns the complete column definition from input format.
     *
     * @param string $format the format of the definition.
     *
     * @return string a string containing the complete column definition.
     */
    protected function buildCompleteString(string $format, array $customPlaceHolder = []): string
    {
        return parent::buildCompleteString(
            $format,
            [
                '{unsigned}' => $this->buildUnsignedString(),
                '{pos}' => $this->isFirst ? $this->buildFirstString() : $this->buildAfterString(),
            ],
        );
    }
}
