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
     * Creates a `UNSIGNED BIGINT AUTO_INCREMENT PRIMARY KEY` column.
     *
     * @param int|null $length column size or precision definition. Defaults to 20.
     *
     * @return self The column schema builder instance.
     */
    public function unsignedBigPrimaryKey(int|null $length = null): self
    {
        $this->type = self::CATEGORY_UBIGPK;
        $this->isUnsigned = true;

        if ($length !== null) {
            $this->length = $length;
        }

        return $this;
    }

    /**
     * Creates a `UNSIGNED INTEGER AUTO_INCREMENT PRIMARY KEY` column.
     *
     * @param int|null $length column size or precision definition. Defaults to 11.
     *
     * @return self The column schema builder instance.
     */
    public function unsignedPrimaryKey(int|null $length = null): self
    {
        $this->type = self::CATEGORY_UPK;
        $this->isUnsigned = true;

        if ($length !== null) {
            $this->length = $length;
        }

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
        return $this->after !== '' ?
            ' AFTER ' . $this->after :
            '';
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
        return $this->comment !== '' ? ' COMMENT ' . $this->comment : '';
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $format = match ($this->getTypeCategory()) {
            self::CATEGORY_AUTO,
            self::CATEGORY_BIGAUTO,
            self::CATEGORY_BIGPK,
            self::CATEGORY_PK => '{type}{length}{comment}{check}{append}{pos}',
            self::CATEGORY_NUMERIC => '{type}{length}{unsigned}{notnull}{default}{unique}{comment}{append}{pos}{check}',
            default => '{type}{length}{notnull}{default}{unique}{comment}{append}{pos}{check}',
        };

        return $this->buildCompleteString($format);
    }
}
