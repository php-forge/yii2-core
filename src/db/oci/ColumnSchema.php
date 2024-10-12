<?php

declare(strict_types=1);

namespace yii\db\oci;

use yii\db\Expression;
use yii\db\ExpressionInterface;

/**
 * Class ColumnSchema for Oracle databases.
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * @var string|null name of associated sequence if column is auto-incremental
     */
    public string|null $sequenceName = null;

    /**
     * {@inheritdoc}
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->dbType === 'BLOB' && is_string($value)) {
            return $this->dbTypeCastAsBlob($value);
        }

        return parent::dbTypecast($value);
    }

    /**
     * Typecasts a value to a `BLOB` for the `BLOB` database type.
     *
     * If the value is a string, it generates a unique placeholder and returns an SQL expression that converts the
     * string into a `BLOB` using `TO_BLOB` and `UTL_RAW.CAST_TO_RAW`.
     *
     * @param mixed $value the value to be typecast.
     *
     * @return ExpressionInterface the typecasted value as a `BLOB`.
     */
    protected function dbTypeCastAsBlob(mixed $value): ExpressionInterface
    {
        $placeholder = uniqid('exp_' . preg_replace('/[^a-z0-9]/i', '', $this->name));

        return new Expression('TO_BLOB(UTL_RAW.CAST_TO_RAW(:' . $placeholder . '))', [$placeholder => $value]);
    }
}
