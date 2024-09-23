<?php

declare(strict_types=1);

namespace yii\db\oci;

use yii\db\Expression;
use yii\db\Schema;

/**
 * Class ColumnSchema for PostgreSQL database.
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * @var string|null name of associated sequence if column is auto-incremental
     */
    public string|null $sequenceName = null;

    public function dbTypecast($value)
    {
        if ($this->type === Schema::TYPE_BINARY && $this->dbType === 'BLOB') {
            if (is_string($value)) {
                $placeholder = uniqid('exp_' . preg_replace('/[^a-z0-9]/i', '', $this->name));

                return new Expression('TO_BLOB(UTL_RAW.CAST_TO_RAW(:' . $placeholder . '))', [$placeholder => $value]);
            }
        }

        return parent::dbTypecast($value);
    }

    protected function typecast($value)
    {
        if ($this->phpType === 'string' && is_bool($value)) {
            return $value ? '1' : '0';
        }

        return parent::typecast($value);
    }
}
