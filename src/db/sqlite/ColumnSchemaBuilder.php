<?php

declare(strict_types=1);

namespace yii\db\sqlite;

/**
 * ColumnSchemaBuilder is the schema builder for Sqlite databases.
 */
class ColumnSchemaBuilder extends \yii\db\ColumnSchemaBuilder
{
    /**
     * @var bool whether the column values should be unsigned. If this is `true`, an `UNSIGNED` keyword will be added.
     */
    protected bool $isUnsigned = false;

    /**
     * Marks column as unsigned.
     *
     * @return static Instance of the column schema builder.
     */
    public function unsigned(): static
    {
        $this->type = match ($this->type) {
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
    public function __toString()
    {
        $format = match ($this->getTypeCategory()) {
            self::CATEGORY_PK => '{type}{check}{append}',
            self::CATEGORY_NUMERIC => '{type}{length}{unsigned}{notnull}{unique}{check}{default}{append}',
            default => '{type}{length}{notnull}{unique}{check}{default}{append}',
        };

        return $this->buildCompleteString(
            $format,
            ['{unsigned}' => $this->buildUnsignedString()],
        );
    }
}
