<?php

declare(strict_types=1);

namespace yii\db\sqlite;

/**
 * ColumnSchemaBuilder is the schema builder for Sqlite databases.
 */
class ColumnSchemaBuilder extends \yii\db\ColumnSchemaBuilder
{
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

        return $this->buildCompleteString($format);
    }
}
