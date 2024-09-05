<?php

declare(strict_types=1);

namespace yii\db;

use yii\base\BaseObject;
use yii\base\InvalidArgumentException;

/**
 * TableSchema represents the metadata of a database table.
 *
 * @property-read array $columnNames List of column names.
 */
class TableSchema extends BaseObject
{
    /**
     * @var string|null the name of the schema that this table belongs to. Defaults to null, meaning no schema (or the
     * default schema).
     */
    public string|null $schemaName = null;
    /**
     * @var string the name of this table. The schema name is not included. Use [[fullName]] to get the name with schema
     * name prefix. For defa
     */
    public string $name = '';
    /**
     * @var string the full name of this table, which includes the schema name prefix, if any.
     * Note that if the schema name is the same as the [[Schema::defaultSchema|default schema name]], the schema name
     * will not be included.
     */
    public string $fullName = '';
    /**
     * @var string[] primary keys of this table.
     */
    public array $primaryKey = [];
    /**
     * @var string|null sequence name for the primary key. Null if no sequence.
     */
    public string|null $sequenceName = null;
    /**
     * @var array foreign keys of this table. Each array element is of the following structure:
     *
     * ```php
     * [
     *  'ForeignTableName',
     *  'fk1' => 'pk1',  // pk1 is in foreign table
     *  'fk2' => 'pk2',  // if composite foreign key
     * ]
     * ```
     */
    public array $foreignKeys = [];
    /**
     * @var ColumnSchema[] column metadata of this table. Each array element is a [[ColumnSchema]] object, indexed by
     * column names.
     */
    public array $columns = [];
    /**
     * @var string|null the name of the server that this table belongs to. Defaults to null, meaning no server (or the
     * current server).
     */
    public string|null $serverName = null;

    /**
     * Gets the named column metadata.
     * This is a convenient method for retrieving a named column even if it does not exist.
     *
     * @param string $name column name.
     *
     * @return ColumnSchema|null metadata of the named column. Null if the named column does not exist.
     */
    public function getColumn(string $name): ColumnSchema|null
    {
        return isset($this->columns[$name]) ? $this->columns[$name] : null;
    }

    /**
     * Returns the names of all columns in this table.
     *
     * @return array list of column names.
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * Manually specifies the primary key for this table.
     *
     * @param string|array $keys the primary key (can be composite).
     *
     * @throws InvalidArgumentException if the specified key cannot be found in the table.
     */
    public function fixPrimaryKey(string|array $keys): void
    {
        $keys = (array) $keys;
        $this->primaryKey = $keys;

        foreach ($this->columns as $column) {
            $column->isPrimaryKey = false;
        }

        foreach ($keys as $key) {
            if (isset($this->columns[$key])) {
                $this->columns[$key]->isPrimaryKey = true;
            } else {
                throw new InvalidArgumentException("Primary key '$key' cannot be found in table '{$this->name}'.");
            }
        }
    }
}
