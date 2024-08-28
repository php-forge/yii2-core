<?php

declare(strict_types=1);

namespace yii\db\oci;

use yii\db\ColumnSchemaBuilder as AbstractColumnSchemaBuilder;

/**
 * ColumnSchemaBuilder is the schema builder for Oracle databases.
 */
class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder
{
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $format = match ($this->getTypeCategory()) {
            self::CATEGORY_PK => '{type}{length}{check}{append}',
            self::CATEGORY_NUMERIC => '{type}{length}{default}{notnull}{check}{append}',
            default => '{type}{length}{default}{notnull}{check}{append}',
        };

        return $this->buildCompleteString($format);
    }
}
