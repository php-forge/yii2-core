<?php

declare(strict_types=1);

namespace yii\db;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\caching\Cache;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;

use function strpos;

/**
 * Schema is the base class for concrete DBMS-specific schema classes.
 *
 * Schema represents the database schema information that is DBMS specific.
 *
 * @property-read string $lastInsertID The row ID of the last row inserted, or the last value retrieved from
 * the sequence object.
 * @property-read QueryBuilder $queryBuilder The query builder for this connection.
 * @property-read string[] $schemaNames All schema names in the database, except system schemas.
 * @property-read string $serverVersion Server version as a string.
 * @property-read string[] $tableNames All table names in the database.
 * @property-read TableSchema[] $tableSchemas The metadata for all tables in the database. Each array element
 * is an instance of [[TableSchema]] or its child class.
 * @property-write string $transactionIsolationLevel The transaction isolation level to use for this
 * transaction. This can be one of [[Transaction::READ_UNCOMMITTED]], [[Transaction::READ_COMMITTED]],
 * [[Transaction::REPEATABLE_READ]] and [[Transaction::SERIALIZABLE]] but also a string containing DBMS specific
 * syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Sergey Makinen <sergey@makinen.ru>
 * @since 2.0
 */
abstract class Schema extends BaseObject
{
    // The following are the supported abstract column data types.
    const TYPE_PK = 'pk';
    const TYPE_UPK = 'upk';
    const TYPE_BIGPK = 'bigpk';
    const TYPE_UBIGPK = 'ubigpk';
    const TYPE_CHAR = 'char';
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_TINYINT = 'tinyint';
    const TYPE_SMALLINT = 'smallint';
    const TYPE_INTEGER = 'integer';
    const TYPE_BIGINT = 'bigint';
    const TYPE_FLOAT = 'float';
    const TYPE_DOUBLE = 'double';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_TIME = 'time';
    const TYPE_DATE = 'date';
    const TYPE_BINARY = 'binary';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_MONEY = 'money';
    const TYPE_JSON = 'json';
    /**
     * Schema cache version, to detect incompatibilities in cached values when the
     * data format of the cache changes.
     */
    const SCHEMA_CACHE_VERSION = 1;

    /**
     * @var Connection the database connection
     */
    public $db;
    /**
     * @var string the default schema name used for the current session.
     */
    public $defaultSchema;
    /**
     * @var array map of DB errors and corresponding exceptions
     * If left part is found in DB error message exception class from the right part is used.
     */
    public $exceptionMap = [
        'SQLSTATE[23' => 'yii\db\IntegrityException',
    ];
    /**
     * @var string|array column schema class or class config
     * @since 2.0.11
     */
    public $columnSchemaClass = 'yii\db\ColumnSchema';

    /**
     * @var string|string[] character used to quote schema, table, etc. names.
     * An array of 2 characters can be used in case starting and ending characters are different.
     * @since 2.0.14
     */
    protected array|string $tableQuoteCharacter = "'";
    /**
     * @var string|string[] character used to quote column names.
     * An array of 2 characters can be used in case starting and ending characters are different.
     * @since 2.0.14
     */
    protected array|string $columnQuoteCharacter = '"';

    /**
     * @var array list of ALL schema names in the database, except system schemas
     */
    private $_schemaNames;
    /**
     * @var array list of ALL table names in the database
     */
    private $_tableNames = [];
    /**
     * @var array list of loaded table metadata (table name => metadata type => metadata).
     */
    private $_tableMetadata = [];
    /**
     * @var QueryBuilder the query builder for this database
     */
    private $_builder;
    /**
     * @var string server version as a string.
     */
    private $_serverVersion;

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param string $name the table name. The table name may contain schema name if any. Do not quote the table name.
     *
     * @return TableSchema [[TableSchema]] with resolved table, schema, etc. names.
     *
     * @throws NotSupportedException if this method is not supported by the DBMS.
     */
    protected function resolveTableName(string $name): TableSchema
    {
        throw new NotSupportedException(get_class($this) . ' does not support resolving table names.');
    }

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     * This method should be overridden by child classes in order to support this feature
     * because the default implementation simply throws an exception.
     * @return array all schema names in the database, except system schemas.
     * @throws NotSupportedException if this method is not supported by the DBMS.
     * @since 2.0.4
     */
    protected function findSchemaNames()
    {
        throw new NotSupportedException(get_class($this) . ' does not support fetching all schema names.');
    }

    /**
     * Returns all table names in the database.
     * This method should be overridden by child classes in order to support this feature
     * because the default implementation simply throws an exception.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * @return array all table names in the database. The names have NO schema name prefix.
     * @throws NotSupportedException if this method is not supported by the DBMS.
     */
    protected function findTableNames($schema = '')
    {
        throw new NotSupportedException(get_class($this) . ' does not support fetching all table names.');
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $table table name.
     *
     * @return TableSchema|null DBMS-dependent table metadata, `null` if the table does not exist.
     */
    abstract protected function loadTableSchema(string $table): TableSchema|null;

    /**
     * Creates a column schema for the database.
     * This method may be overridden by child classes to create a DBMS-specific column schema.
     * @return ColumnSchema column schema instance.
     * @throws InvalidConfigException if a column schema class cannot be created.
     */
    protected function createColumnSchema()
    {
        return Yii::createObject($this->columnSchemaClass);
    }

    /**
     * Obtains the metadata for the named table.
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the table schema even if it is found in the cache.
     * @return TableSchema|null table metadata. `null` if the named table does not exist.
     */
    public function getTableSchema($name, $refresh = false)
    {
        return $this->getTableMetadata($name, 'schema', $refresh);
    }

    /**
     * Returns the metadata for all tables in the database.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is `false`,
     * cached data may be returned if available.
     * @return TableSchema[] the metadata for all tables in the database.
     * Each array element is an instance of [[TableSchema]] or its child class.
     */
    public function getTableSchemas($schema = '', $refresh = false)
    {
        return $this->getSchemaMetadata($schema, 'schema', $refresh);
    }

    /**
     * Returns all schema names in the database, except system schemas.
     * @param bool $refresh whether to fetch the latest available schema names. If this is false,
     * schema names fetched previously (if available) will be returned.
     * @return string[] all schema names in the database, except system schemas.
     * @since 2.0.4
     */
    public function getSchemaNames($refresh = false)
    {
        if ($this->_schemaNames === null || $refresh) {
            $this->_schemaNames = $this->findSchemaNames();
        }

        return $this->_schemaNames;
    }

    /**
     * Returns all table names in the database.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema name.
     * If not empty, the returned table names will be prefixed with the schema name.
     * @param bool $refresh whether to fetch the latest available table names. If this is false,
     * table names fetched previously (if available) will be returned.
     * @return string[] all table names in the database.
     */
    public function getTableNames($schema = '', $refresh = false)
    {
        if (!isset($this->_tableNames[$schema]) || $refresh) {
            $this->_tableNames[$schema] = $this->findTableNames($schema);
        }

        return $this->_tableNames[$schema];
    }

    /**
     * @return QueryBuilder the query builder for this connection.
     */
    public function getQueryBuilder()
    {
        if ($this->_builder === null) {
            $this->_builder = $this->createQueryBuilder();
        }

        return $this->_builder;
    }

    /**
     * Determines the PDO type for the given PHP data value.
     * @param mixed $data the data whose PDO type is to be determined
     * @return int the PDO type
     * @see https://www.php.net/manual/en/pdo.constants.php
     */
    public function getPdoType($data)
    {
        static $typeMap = [
            // php type => PDO type
            'boolean' => \PDO::PARAM_BOOL,
            'integer' => \PDO::PARAM_INT,
            'string' => \PDO::PARAM_STR,
            'resource' => \PDO::PARAM_LOB,
            'NULL' => \PDO::PARAM_NULL,
        ];
        $type = gettype($data);

        return isset($typeMap[$type]) ? $typeMap[$type] : \PDO::PARAM_STR;
    }

    /**
     * Refreshes the schema.
     * This method cleans up all cached table schemas so that they can be re-created later
     * to reflect the database schema change.
     */
    public function refresh()
    {
        /* @var $cache CacheInterface */
        $cache = is_string($this->db->schemaCache) ? Yii::$app->get($this->db->schemaCache, false) : $this->db->schemaCache;
        if ($this->db->enableSchemaCache && $cache instanceof CacheInterface) {
            TagDependency::invalidate($cache, $this->getCacheTag());
        }
        $this->_tableNames = [];
        $this->_tableMetadata = [];
    }

    /**
     * Refreshes the particular table schema.
     * This method cleans up cached table schema so that it can be re-created later
     * to reflect the database schema change.
     * @param string $name table name.
     * @since 2.0.6
     */
    public function refreshTableSchema($name)
    {
        $rawName = $this->getRawTableName($name);
        unset($this->_tableMetadata[$rawName]);
        $this->_tableNames = [];
        /* @var $cache CacheInterface */
        $cache = is_string($this->db->schemaCache) ? Yii::$app->get($this->db->schemaCache, false) : $this->db->schemaCache;
        if ($this->db->enableSchemaCache && $cache instanceof CacheInterface) {
            $cache->delete($this->getCacheKey($rawName));
        }
    }

    /**
     * Creates a query builder for the database.
     * This method may be overridden by child classes to create a DBMS-specific query builder.
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder()
    {
        return Yii::createObject(QueryBuilder::class, [$this->db]);
    }

    /**
     * Create a column schema builder instance giving the type and value precision.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema builder.
     *
     * @param string $type type of the column. See [[ColumnSchemaBuilder::$type]].
     * @param int|string|array|null $length length or precision of the column. See [[ColumnSchemaBuilder::$length]].
     * @return ColumnSchemaBuilder column schema builder instance
     * @since 2.0.6
     */
    public function createColumnSchemaBuilder($type, $length = null)
    {
        return Yii::createObject(ColumnSchemaBuilder::class, [$type, $length]);
    }

    /**
     * Returns all unique indexes for the given table.
     *
     * Each array element is of the following structure:
     *
     * ```php
     * [
     *  'IndexName1' => ['col1' [, ...]],
     *  'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * This method should be overridden by child classes in order to support this feature
     * because the default implementation simply throws an exception
     * @param TableSchema $table the table metadata
     * @return array all unique indexes for the given table.
     * @throws NotSupportedException if this method is called
     */
    public function findUniqueIndexes($table)
    {
        throw new NotSupportedException(get_class($this) . ' does not support getting unique indexes information.');
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     * @throws InvalidCallException if the DB connection is not active
     * @see https://www.php.net/manual/en/function.PDO-lastInsertId.php
     */
    public function getLastInsertID($sequenceName = '')
    {
        if ($this->db->isActive) {
            return $this->db->pdo->lastInsertId($sequenceName === '' ? null : $this->quoteTableName($sequenceName));
        }

        throw new InvalidCallException('DB Connection is not active.');
    }

    /**
     * @return bool whether this DBMS supports [savepoint](https://en.wikipedia.org/wiki/Savepoint).
     */
    public function supportsSavepoint()
    {
        return $this->db->enableSavepoint;
    }

    /**
     * Creates a new savepoint.
     * @param string $name the savepoint name
     */
    public function createSavepoint($name)
    {
        $this->db->createCommand("SAVEPOINT $name")->execute();
    }

    /**
     * Releases an existing savepoint.
     * @param string $name the savepoint name
     */
    public function releaseSavepoint($name)
    {
        $this->db->createCommand("RELEASE SAVEPOINT $name")->execute();
    }

    /**
     * Rolls back to a previously created savepoint.
     * @param string $name the savepoint name
     */
    public function rollBackSavepoint($name)
    {
        $this->db->createCommand("ROLLBACK TO SAVEPOINT $name")->execute();
    }

    /**
     * Sets the isolation level of the current transaction.
     * @param string $level The transaction isolation level to use for this transaction.
     * This can be one of [[Transaction::READ_UNCOMMITTED]], [[Transaction::READ_COMMITTED]], [[Transaction::REPEATABLE_READ]]
     * and [[Transaction::SERIALIZABLE]] but also a string containing DBMS specific syntax to be used
     * after `SET TRANSACTION ISOLATION LEVEL`.
     * @see https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public function setTransactionIsolationLevel($level)
    {
        $this->db->createCommand("SET TRANSACTION ISOLATION LEVEL $level")->execute();
    }

    /**
     * Gets the next auto-increment value for the specified table's primary key.
     *
     * This method retrieves the maximum value of the specified primary key column from the given table and adds 1 to
     * it. If the table is empty, it returns 1.
     *
     * @param string $tableName the name of the table.
     * @param string $columnPK the name of the primary key column.
     *
     * @return int the next auto-increment value for the primary key column.
     */
    public function getNextAutoIncrementValue(string $tableName, string $columnPK): int
    {
        $sql = <<<SQL
        SELECT COALESCE(MAX({$this->db->quoteColumnName($columnPK)}), 0) + 1 FROM {$this->db->quoteTableName($tableName)}
        SQL;

        // use master connection to get the next auto-increment value
        return $this->db->useMaster(static fn (Connection $db) => (int) $db->createCommand($sql)->queryScalar());
    }

    /**
     * Splits full table name into parts.
     *
     * @param string $name the table name.
     * @param bool $withColumn whether to quote the column name.
     *
     * @return array the table name parts.
     */
    public function getTableNameParts(string $name, bool $withColumn = false): array
    {
        return $this->db->getQuoter()->getTableNameParts($name, $withColumn);
    }

    /**
     * Returns the actual name of a given table name.
     *
     * This method will strip off curly brackets from the given table name and replace the percentage character '%' with
     * [[Connection::tablePrefix]].
     *
     * @param string $tableName the table name to be converted.
     *
     * @return string the real name of the given table name.
     */
    public function getRawTableName(string $tableName): string
    {
        return $this->db->getQuoter()->getRawTableName($tableName);
    }

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains '(', '[[' or '{{', then this method will do nothing.
     *
     * @param string $name column name.
     *
     * @return string the properly quoted column name.
     */
    public function quoteColumnName(string $name): string
    {
        return $this->db->quoteColumnName($name);
    }

    /**
     * Quotes a simple table name for use in a query.
     * A simple table name should contain the table name only without any schema prefix.
     * If the table name is already quoted, this method will do nothing.
     *
     * @param string $name table name.
     *
     * @return string the properly quoted table name.
     */
    public function quoteSimpleTableName($name)
    {
        return $this->db->getQuoter()->quoteSimpleTableName($name);
    }

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains '(' or '{{',
     * then this method will do nothing.
     *
     * @param string $name table name
     *
     * @return string the properly quoted table name
     */
    public function quoteTableName(string $name): string
    {
        return $this->db->quoteTableName($name);
    }

    /**
     * Quotes a string value for use in a query.
     *
     * Note that if the parameter is not a string, it will be returned without change.
     *
     * @param string $str string to be quoted.
     *
     * @return string the properly quoted string.
     *
     * @see https://www.php.net/manual/en/function.PDO-quote.php
     */
    public function quoteValue($str)
    {
        return $this->db->getQuoter()->quoteValue($str);
    }

    /**
     * Extracts the PHP type from abstract DB type.
     *
     * @param ColumnSchema $column the column schema information.
     *
     * @return string PHP type name.
     */
    protected function getColumnPhpType(ColumnSchema $column): string
    {
        $phpType = match ($column->type) {
            self::TYPE_TINYINT, self::TYPE_SMALLINT, self::TYPE_INTEGER => PHPType::INTEGER,
            self::TYPE_BIGINT => PHP_INT_SIZE === 8 ? PHPType::INTEGER : PHPType::STRING,
            self::TYPE_BOOLEAN => PHPType::BOOLEAN,
            self::TYPE_DECIMAL, self::TYPE_FLOAT, self::TYPE_DOUBLE => PHPType::DOUBLE,
            self::TYPE_BINARY => PHPType::RESOURCE,
            self::TYPE_JSON => PHPType::ARRAY,
            default => PHPType::STRING,
        };

        return $phpType->value;
    }

    /**
     * Converts a DB exception to a more concrete one if possible.
     *
     * @param \Exception $e
     * @param string $rawSql SQL that produced exception
     * @return Exception
     */
    public function convertException(\Exception $e, $rawSql)
    {
        if ($e instanceof Exception) {
            return $e;
        }

        $exceptionClass = '\yii\db\Exception';
        foreach ($this->exceptionMap as $error => $class) {
            if (strpos($e->getMessage(), $error) !== false) {
                $exceptionClass = $class;
            }
        }
        $message = $e->getMessage() . "\nThe SQL being executed was: $rawSql";
        $errorInfo = $e instanceof \PDOException ? $e->errorInfo : null;
        return new $exceptionClass($message, $errorInfo, $e->getCode(), $e);
    }

    /**
     * Returns a value indicating whether a SQL statement is for read purpose.
     * @param string $sql the SQL statement
     * @return bool whether a SQL statement is for read purpose.
     */
    public function isReadQuery($sql)
    {
        $pattern = '/^\s*(SELECT|SHOW|DESCRIBE)\b/i';
        return preg_match($pattern, $sql) > 0;
    }

    /**
     * Returns a server version as a string comparable by [[\version_compare()]].
     * @return string server version as a string.
     * @since 2.0.14
     */
    public function getServerVersion()
    {
        if ($this->_serverVersion === null) {
            $this->_serverVersion = $this->db->getSlavePdo(true)->getAttribute(\PDO::ATTR_SERVER_VERSION);
        }
        return $this->_serverVersion;
    }

    /**
     * Returns the cache key for the specified table name.
     * @param string $name the table name.
     * @return mixed the cache key.
     */
    protected function getCacheKey($name)
    {
        return [
            __CLASS__,
            $this->db->dsn,
            $this->db->username,
            $this->getRawTableName($name),
        ];
    }

    /**
     * Returns the cache tag name.
     * This allows [[refresh()]] to invalidate all cached table schemas.
     * @return string the cache tag name
     */
    protected function getCacheTag()
    {
        return md5(serialize([
            __CLASS__,
            $this->db->dsn,
            $this->db->username,
        ]));
    }

    /**
     * Returns the metadata of the given type for the given table.
     * If there's no metadata in the cache, this method will call
     * a `'loadTable' . ucfirst($type)` named method with the table name to obtain the metadata.
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param string $type metadata type.
     * @param bool $refresh whether to reload the table metadata even if it is found in the cache.
     * @return mixed metadata.
     * @since 2.0.13
     */
    protected function getTableMetadata($name, $type, $refresh)
    {
        $cache = null;
        if ($this->db->enableSchemaCache && !in_array($name, $this->db->schemaCacheExclude, true)) {
            $schemaCache = is_string($this->db->schemaCache) ? Yii::$app->get($this->db->schemaCache, false) : $this->db->schemaCache;
            if ($schemaCache instanceof CacheInterface) {
                $cache = $schemaCache;
            }
        }

        $rawName = $this->getRawTableName($name);

        if (!isset($this->_tableMetadata[$rawName])) {
            $this->loadTableMetadataFromCache($cache, $rawName);
        }

        if ($refresh || !array_key_exists($type, $this->_tableMetadata[$rawName])) {
            $this->_tableMetadata[$rawName][$type] = $this->{'loadTable' . ucfirst($type)}($rawName);
            $this->saveTableMetadataToCache($cache, $rawName);
        }

        return $this->_tableMetadata[$rawName][$type];
    }

    /**
     * Returns the metadata of the given type for all tables in the given schema.
     * This method will call a `'getTable' . ucfirst($type)` named method with the table name
     * and the refresh flag to obtain the metadata.
     * @param string $schema the schema of the metadata. Defaults to empty string, meaning the current or default schema name.
     * @param string $type metadata type.
     * @param bool $refresh whether to fetch the latest available table metadata. If this is `false`,
     * cached data may be returned if available.
     * @return array array of metadata.
     * @since 2.0.13
     */
    protected function getSchemaMetadata($schema, $type, $refresh)
    {
        $metadata = [];
        $methodName = 'getTable' . ucfirst($type);
        foreach ($this->getTableNames($schema, $refresh) as $name) {
            if ($schema !== '') {
                $name = $schema . '.' . $name;
            }
            $tableMetadata = $this->$methodName($name, $refresh);
            if ($tableMetadata !== null) {
                $metadata[] = $tableMetadata;
            }
        }

        return $metadata;
    }

    /**
     * Sets the metadata of the given type for the given table.
     * @param string $name table name.
     * @param string $type metadata type.
     * @param mixed $data metadata.
     * @since 2.0.13
     */
    protected function setTableMetadata($name, $type, $data)
    {
        $this->_tableMetadata[$this->getRawTableName($name)][$type] = $data;
    }

    /**
     * Changes row's array key case to lower if PDO's one is set to uppercase.
     * @param array $row row's array or an array of row's arrays.
     * @param bool $multiple whether multiple rows or a single row passed.
     * @return array normalized row or rows.
     * @since 2.0.13
     */
    protected function normalizePdoRowKeyCase(array $row, $multiple)
    {
        if ($this->db->getSlavePdo(true)->getAttribute(\PDO::ATTR_CASE) !== \PDO::CASE_UPPER) {
            return $row;
        }

        if ($multiple) {
            return array_map(function (array $row) {
                return array_change_key_case($row, CASE_LOWER);
            }, $row);
        }

        return array_change_key_case($row, CASE_LOWER);
    }

    /**
     * Tries to load and populate table metadata from cache.
     * @param Cache|null $cache
     * @param string $name
     */
    private function loadTableMetadataFromCache($cache, $name)
    {
        if ($cache === null) {
            $this->_tableMetadata[$name] = [];
            return;
        }

        $metadata = $cache->get($this->getCacheKey($name));
        if (!is_array($metadata) || !isset($metadata['cacheVersion']) || $metadata['cacheVersion'] !== static::SCHEMA_CACHE_VERSION) {
            $this->_tableMetadata[$name] = [];
            return;
        }

        unset($metadata['cacheVersion']);
        $this->_tableMetadata[$name] = $metadata;
    }

    /**
     * Saves table metadata to cache.
     * @param Cache|null $cache
     * @param string $name
     */
    private function saveTableMetadataToCache($cache, $name)
    {
        if ($cache === null) {
            return;
        }

        $metadata = $this->_tableMetadata[$name];
        $metadata['cacheVersion'] = static::SCHEMA_CACHE_VERSION;
        $cache->set(
            $this->getCacheKey($name),
            $metadata,
            $this->db->schemaCacheDuration,
            new TagDependency(['tags' => $this->getCacheTag()])
        );
    }

    public function getColumnQuoteCharacter(): array|string
    {
        return $this->columnQuoteCharacter;
    }

    public function getTableQuoteCharacter(): array|string
    {
        return $this->tableQuoteCharacter;
    }
}
