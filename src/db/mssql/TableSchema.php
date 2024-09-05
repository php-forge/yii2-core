<?php

declare(strict_types=1);

namespace yii\db\mssql;

/**
 * TableSchema represents the metadata of a database table.
 */
class TableSchema extends \yii\db\TableSchema
{
    /**
     * @var string|null name of the catalog (database) that this table belongs to.
     * Defaults to null, meaning no catalog (or the current database).
     */
    public string|null $catalogName = null;
    /**
     * @var string|null the name of the server that this table belongs to. Defaults to null, meaning no server (or the
     * current server).
     */
    public string|null $serverName = null;
}
