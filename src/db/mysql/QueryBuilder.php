<?php

declare(strict_types=1);

namespace yii\db\mysql;

use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\caching\CacheInterface;
use yii\caching\DbCache;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\db\Query;

/**
 * QueryBuilder is the query builder for MySQL databases.
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    public array $typeMap = [
        Schema::TYPE_PK => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_UPK => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_CHAR => 'char(1)',
        Schema::TYPE_STRING => 'varchar(255)',
        Schema::TYPE_TEXT => 'text',
        Schema::TYPE_TINYINT => 'tinyint(3)',
        Schema::TYPE_SMALLINT => 'smallint(6)',
        Schema::TYPE_INTEGER => 'int(11)',
        Schema::TYPE_BIGINT => 'bigint(20)',
        Schema::TYPE_FLOAT => 'float',
        Schema::TYPE_DOUBLE => 'double',
        Schema::TYPE_DECIMAL => 'decimal(10,0)',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'blob',
        Schema::TYPE_BOOLEAN => 'tinyint(1)',
        Schema::TYPE_MONEY => 'decimal(19,4)',
        Schema::TYPE_JSON => 'json'
    ];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->typeMap = array_merge($this->typeMap, $this->defaultTimeTypeMap());
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultExpressionBuilders(): array
    {
        return array_merge(
            parent::defaultExpressionBuilders(),
            [
                'yii\db\JsonExpression' => 'yii\db\mysql\JsonExpressionBuilder',
            ],
        );
    }

    /**
     * Builds a SQL statement for renaming a column.
     *
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB column.
     *
     * @throws Exception
     */
    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        $quotedTable = $this->db->quoteTableName($table);
        $row = $this->db->createCommand('SHOW CREATE TABLE ' . $quotedTable)->queryOne();

        if ($row === false) {
            throw new Exception("Unable to find column '$oldName' in table '$table'.");
        }

        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        if (preg_match_all('/^\s*[`"](.*?)[`"]\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $i => $c) {
                if ($c === $oldName) {
                    return "ALTER TABLE $quotedTable CHANGE "
                        . $this->db->quoteColumnName($oldName) . ' '
                        . $this->db->quoteColumnName($newName) . ' '
                        . $matches[2][$i];
                }
            }
        }

        // try to give back a SQL anyway
        return
            "ALTER TABLE $quotedTable CHANGE "
            . $this->db->quoteColumnName($oldName) . ' '
            . $this->db->quoteColumnName($newName);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://bugs.mysql.com/bug.php?id=48875
     */
    public function createIndex(string $name, string $table, array|string $columns, bool $unique = false): string
    {
        return
            'ALTER TABLE '
            . $this->db->quoteTableName($table)
            . ($unique ? ' ADD UNIQUE INDEX ' : ' ADD INDEX ')
            . $this->db->quoteTableName($name)
            . ' (' . $this->buildColumns($columns) . ')';
    }

    /**
     * Builds a SQL statement for dropping a foreign key constraint.
     *
     * @param string $name the name of the foreign key constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table the table whose foreign is to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping a foreign key constraint.
     */
    public function dropForeignKey(string $name, string $table): string
    {
        return
            'ALTER TABLE ' .
            $this->db->quoteTableName($table) .
            ' DROP FOREIGN KEY ' .
            $this->db->quoteColumnName($name);
    }

    /**
     * Builds a SQL statement for removing a primary key constraint to an existing table.
     *
     * @param string $name the name of the primary key constraint to be removed.
     * @param string $table the table that the primary key constraint will be removed from.
     *
     * @return string the SQL statement for removing a primary key constraint from an existing table.
     */
    public function dropPrimaryKey(string $name, string $table): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' DROP PRIMARY KEY';
    }

    /**
     * {@inheritdoc}
     */
    public function dropUnique(string $name, string $table): string
    {
        return $this->dropIndex($name, $table);
    }

    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     * The sequence will be reset such that the primary key of the next new row inserted will have the specified value
     * or 1.
     *
     * @param string $tableName the name of the table whose primary key sequence will be reset
     * @param mixed $value the value for the primary key of the next new row inserted. If this is not set,
     * the next new row's primary key will have a value 1.
     *
     * @return string the SQL statement for resetting sequence.
     *
     * @throws InvalidArgumentException if the table does not exist or there is no sequence associated with the table.
     */
    public function resetSequence(string $tableName, mixed $value = null): string
    {
        $table = $this->db->getTableSchema($tableName);

        if ($table !== null && $table->sequenceName !== null) {
            $tableName = $this->db->quoteTableName($tableName);

            if ($value === null) {
                $key = reset($table->primaryKey);
                $value = $this->db->createCommand("SELECT MAX(`$key`) FROM $tableName")->queryScalar() + 1;
            } else {
                $value = (int) $value;
            }

            return "ALTER TABLE $tableName AUTO_INCREMENT=$value";
        } elseif ($table === null) {
            throw new InvalidArgumentException("Table not found: $tableName");
        }

        throw new InvalidArgumentException("There is no sequence associated with table '$tableName'.");
    }

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     *
     * @param bool $check whether to turn on or off the integrity check.
     * @param string $schema the schema of the tables. Meaningless for MySQL.
     * @param string $table the table name. Meaningless for MySQL.
     *
     * @return string the SQL statement for checking integrity.
     */
    public function checkIntegrity(bool $check = true, string $schema = '', string $table = ''): string
    {
        return 'SET FOREIGN_KEY_CHECKS = ' . ($check ? 1 : 0);
    }

    /**
     * {@inheritdoc}
     */
    public function buildLimit(ExpressionInterface|int|null $limit, ExpressionInterface|int|null $offset): string
    {
        $sql = '';

        if ($this->hasLimit($limit)) {
            $sql = 'LIMIT ' . $limit;
            if ($this->hasOffset($offset)) {
                $sql .= ' OFFSET ' . $offset;
            }
        } elseif ($this->hasOffset($offset)) {
            // limit is not optional in MySQL
            // https://stackoverflow.com/questions/255517/mysql-offset-infinite-rows/271650#271650
            // https://dev.mysql.com/doc/refman/5.7/en/select.html#idm46193796386608
            $sql = "LIMIT $offset, 18446744073709551615"; // 2^64-1
        }

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasLimit(mixed $limit): bool
    {
        // In MySQL limit argument must be nonnegative integer constant
        return ctype_digit((string) $limit);
    }

    /**
     * {@inheritdoc}
     */
    protected function hasOffset(mixed $offset): bool
    {
        // In MySQL offset argument must be nonnegative integer constant
        $offset = (string) $offset;

        return ctype_digit($offset) && $offset !== '0';
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareInsertValues(string $table, array|Query $columns, array $params = []): array
    {
        [$names, $placeholders, $values, $params] = parent::prepareInsertValues($table, $columns, $params);

        if (!$columns instanceof Query && empty($names)) {
            $tableSchema = $this->db->getSchema()->getTableSchema($table);
            if ($tableSchema !== null) {
                if (!empty($tableSchema->primaryKey)) {
                    $columns = $tableSchema->primaryKey;
                    $defaultValue = 'NULL';
                } else {
                    $columns = [reset($tableSchema->columns)->name];
                    $defaultValue = 'DEFAULT';
                }

                foreach ($columns as $name) {
                    $names[] = $this->db->quoteColumnName($name);
                    $placeholders[] = $defaultValue;
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    /**
     * {@inheritdoc}
     *
     * @see https://downloads.mysql.com/docs/refman-5.1-en.pdf
     */
    public function upsert(string $table, array|Query $insertColumns, array|bool $updateColumns, array &$params): string
    {
        $insertSql = $this->insert($table, $insertColumns, $params);
        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

        if (empty($uniqueNames)) {
            return $insertSql;
        }

        if ($updateNames === []) {
            // there are no columns to update
            $updateColumns = false;
        }

        if ($updateColumns === true) {
            $updateColumns = [];
            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression('VALUES(' . $this->db->quoteColumnName($name) . ')');
            }
        } elseif ($updateColumns === false) {
            $name = $this->db->quoteColumnName(reset($uniqueNames));
            $updateColumns = [$name => new Expression($this->db->quoteTableName($table) . '.' . $name)];
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    }

    /**
     * {@inheritdoc}
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        // Strip existing comment which may include escaped quotes
        $definition = trim(preg_replace("/COMMENT '(?:''|[^'])*'/i", '', $this->getColumnDefinition($table, $column)));

        $checkRegex = '/CHECK *(\(([^()]|(?-2))*\))/';
        $check = preg_match($checkRegex, $definition, $checkMatches);

        if ($check === 1) {
            $definition = preg_replace($checkRegex, '', $definition);
        }

        $alterSql = 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' CHANGE ' . $this->db->quoteColumnName($column)
            . ' ' . $this->db->quoteColumnName($column)
            . (empty($definition) ? '' : ' ' . $definition)
            . ' COMMENT ' . $this->db->quoteValue($comment);

        if ($check === 1) {
            $alterSql .= ' ' . $checkMatches[0];
        }

        return $alterSql;
    }

    /**
     * {@inheritdoc}
     */
    public function addCommentOnTable(string $table, string $comment): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' COMMENT ' . $this->db->quoteValue($comment);
    }

    /**
     * {@inheritdoc}
     */
    public function dropCommentFromColumn(string $table, string $column): string
    {
        return $this->addCommentOnColumn($table, $column, '');
    }

    /**
     * {@inheritdoc}
     */
    public function dropCommentFromTable(string $table): string
    {
        return $this->addCommentOnTable($table, '');
    }


    /**
     * Gets column definition.
     *
     * @param string $table table name.
     * @param string $column column name.
     *
     * @return string|null the column definition.
     *
     * @throws Exception in case when table does not contain column.
     */
    private function getColumnDefinition(string $table, string $column): string|null
    {
        $quotedTable = $this->db->quoteTableName($table);
        $row = $this->db->createCommand('SHOW CREATE TABLE ' . $quotedTable)->queryOne();

        if ($row === false) {
            throw new Exception("Unable to find column '$column' in table '$table'.");
        }

        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        if (preg_match_all('/^\s*[`"](.*?)[`"]\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $i => $c) {
                if ($c === $column) {
                    return $matches[2][$i];
                }
            }
        }

        return null;
    }

    /**
     * Checks the ability to use fractional seconds.
     *
     * @return bool whether the MySQL version is greater than 5.6.4.
     *
     * @see https://dev.mysql.com/doc/refman/5.6/en/fractional-seconds.html
     */
    private function supportsFractionalSeconds(): bool
    {
        // use cache to prevent opening MySQL connection
        // https://github.com/yiisoft/yii2/issues/13749#issuecomment-481657224
        $key = [__METHOD__, $this->db->dsn];
        $cache = null;
        $schemaCache = (\Yii::$app && is_string($this->db->schemaCache))
            ? \Yii::$app->get($this->db->schemaCache, false) : $this->db->schemaCache;

        // If the `$schemaCache` is an instance of `DbCache` we don't use it to avoid a loop
        if ($this->db->enableSchemaCache && $schemaCache instanceof CacheInterface && !($schemaCache instanceof DbCache)) {
            $cache = $schemaCache;
        }

        $version = $cache ? $cache->get($key) : null;

        if (!$version) {
            $version = $this->db->getSlavePdo(true)->getAttribute(\PDO::ATTR_SERVER_VERSION);

            if ($cache) {
                $cache->set($key, $version, $this->db->schemaCacheDuration);
            }
        }

        return version_compare($version, '5.6.4', '>=');
    }

    /**
     * Returns the map for default time type.
     *
     * If the version of MySQL is lower than 5.6.4, then the types will be without fractional seconds, otherwise with
     * fractional seconds.
     *
     * @return array the map for default time type.
     */
    private function defaultTimeTypeMap() : array
    {
        $map = [
            Schema::TYPE_DATETIME => 'datetime',
            Schema::TYPE_TIMESTAMP => 'timestamp',
            Schema::TYPE_TIME => 'time',
        ];

        if ($this->supportsFractionalSeconds()) {
            $map = [
                Schema::TYPE_DATETIME => 'datetime(0)',
                Schema::TYPE_TIMESTAMP => 'timestamp(0)',
                Schema::TYPE_TIME => 'time(0)',
            ];
        }

        return $map;
    }
}
