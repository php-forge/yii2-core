<?php

declare(strict_types=1);

namespace yii\db;

use PDO;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\caching\CacheInterface;
use yii\db\schema\Quoter;

/**
 * Connection represents a connection to a database via [PDO](https://www.php.net/manual/en/book.pdo.php).
 *
 * Connection works together with [[Command]], [[DataReader]] and [[Transaction]] provide data access to various DBMS in
 * a common set of APIs. They are a thin wrapper of the [PDO PHP extension](https://www.php.net/manual/en/book.pdo.php).
 *
 * Connection supports database replication and read-write splitting. In particular, a Connection component can be
 * configured with multiple [[masters]] and [[slaves]]. It will do load balancing and failover by choosing appropriate
 * servers. It will also automatically direct read operations to the slaves and write operations to the masters.
 *
 * To establish a DB connection, set [[dsn]], [[username]] and [[password]], and then  call [[open()]] to connect to the
 * database server. The current state of the connection can be checked using [[$isActive]].
 *
 * The following example shows how to create a Connection instance and establish the DB connection:
 *
 * ```php
 * $connection = new \yii\db\Connection(
 *     [
 *         'dsn' => $dsn,
 *         'username' => $username,
 *         'password' => $password,
 *     ],
 * );
 * $connection->open();
 * ```
 *
 * After the DB connection is established, one can execute SQL statements like the following:
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $posts = $command->queryAll();
 * $command = $connection->createCommand('UPDATE post SET status=1');
 * $command->execute();
 * ```
 *
 * One can also do prepared SQL execution and bind parameters to the prepared SQL.
 * When the parameters are coming from user input, you should use this approach to prevent SQL injection attacks.
 *
 * The following is an example:
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post WHERE id=:id');
 * $command->bindValue(':id', $_GET['id']);
 * $post = $command->query();
 * ```
 *
 * For more information about how to perform various DB queries, please refer to [[Command]].
 *
 * If the underlying DBMS supports transactions, you can perform transactional SQL queries like the following:
 *
 * ```php
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     // ... executing other SQL statements ...
 *     $transaction->commit();
 * } catch (Exception $e) {
 *     $transaction->rollBack();
 * }
 * ```
 *
 * You also can use shortcut for the above like the following:
 *
 * ```php
 * $connection->transaction(function () {
 *     $order = new Order($customer);
 *     $order->save();
 *     $order->addItems($items);
 * });
 * ```
 *
 * If needed you can pass transaction isolation level as a second parameter:
 *
 * ```php
 * $connection->transaction(function (Connection $db) {
 *     //return $db->...
 * }, Transaction::READ_UNCOMMITTED);
 * ```
 *
 * Connection is often used as an application component and configured in the application configuration like the
 * following:
 *
 * ```php
 * 'components' => [
 *     'db' => [
 *         'class' => '\yii\db\Connection',
 *         'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
 *         'username' => 'root',
 *         'password' => '',
 *         'charset' => 'utf8',
 *     ],
 * ],
 * ```
 *
 * @property string|null $driverName Name of the DB driver. Note that the type of this property differs in getter and
 * setter. See [[getDriverName()]] and [[setDriverName()]] for details.
 * @property-read bool $isActive Whether the DB connection is established.
 * @property-read string $lastInsertID The row ID of the last row inserted, or the last value retrieved from the
 * sequence object.
 * @property-read Connection|null $master The currently active master connection. `null` is returned if there is no
 * master available.
 * @property-read PDO $masterPdo The PDO instance for the currently active master connection.
 * @property QueryBuilder $queryBuilder The query builder for the current DB connection. Note that the type of this
 * property differs in getter and setter. See [[getQueryBuilder()]] and [[setQueryBuilder()]] for details.
 * @property-read Schema $schema The schema information for the database opened by this connection.
 * @property-read string $serverVersion Server version as a string.
 * @property-read Connection|null $slave The currently active slave connection. `null` is returned if there is no slave
 * available and `$fallbackToMaster` is false.
 * @property-read PDO|null $slavePdo The PDO instance for the currently active slave connection. `null` is returned if
 * no slave connection is available and `$fallbackToMaster` is false.
 * @property-read Transaction|null $transaction The currently active transaction. Null if no active transaction.
 * @property-read Quoter $quoter The Quoter that's used to quote table and column names for use in SQL statements.
 */
class Connection extends Component
{
    /**
     * @event \yii\base\Event an event that is triggered after a DB connection is established
     */
    public const EVENT_AFTER_OPEN = 'afterOpen';
    /**
     * @event \yii\base\Event an event that is triggered right before a top-level transaction is started
     */
    public const EVENT_BEGIN_TRANSACTION = 'beginTransaction';
    /**
     * @event \yii\base\Event an event that is triggered right after a top-level transaction is committed
     */
    public const EVENT_COMMIT_TRANSACTION = 'commitTransaction';
    /**
     * @event \yii\base\Event an event that is triggered right after a top-level transaction is rolled back
     */
    public const EVENT_ROLLBACK_TRANSACTION = 'rollbackTransaction';

    /**
     * @var string|null the Data Source Name, or DSN, contains the information required to connect to the database.
     * Please refer to the [PHP manual](https://www.php.net/manual/en/pdo.construct.php) on the format of the DSN
     * string.
     *
     * For [SQLite](https://www.php.net/manual/en/ref.pdo-sqlite.connection.php) you may use a
     * [path alias](guide:concept-aliases) for specifying the database path, e.g. `sqlite:@app/data/db.sql`.
     *
     * @see charset
     */
    public string|null $dsn = null;
    /**
     * @var string|null the username for establishing DB connection. Defaults to `null` meaning no username to use.
     */
    public string|null $username = null;
    /**
     * @var string|null the password for establishing DB connection. Defaults to `null` meaning no password to use.
     */
    public string|null $password = null;
    /**
     * @var array PDO attributes (name => value) that should be set when calling [[open()]] to establish a DB
     * connection. Please refer to the
     * [PHP manual](https://www.php.net/manual/en/pdo.setattribute.php) for details about available attributes.
     */
    public array $attributes = [];
    /**
     * @var PDO|null the PHP PDO instance associated with this DB connection.
     * This property is mainly managed by [[open()]] and [[close()]] methods.
     * When a DB connection is active, this property will represent a PDO instance; otherwise, it will be null.
     * @see pdoClass
     */
    public PDO|null $pdo = null;
    /**
     * @var bool whether to enable schema caching.
     * Note that in order to enable truly schema caching, a valid cache component as specified by [[schemaCache]] must
     * be enabled and [[enableSchemaCache]] must be set true.
     * @see schemaCacheDuration
     * @see schemaCacheExclude
     * @see schemaCache
     */
    public bool $enableSchemaCache = false;
    /**
     * @var int|null number of seconds that table metadata can remain valid in cache.
     * If this is 0, the cache will be invalidated on every request.
     * Use null to indicate that the cached data will never expire.
     * @see enableSchemaCache
     */
    public int|null $schemaCacheDuration = 3600;
    /**
     * @var array list of tables whose metadata should NOT be cached. Defaults to empty array.
     * The table names may contain schema prefix, if any. Do not quote the table names.
     * @see enableSchemaCache
     */
    public array $schemaCacheExclude = [];
    /**
     * @var CacheInterface|string|null the cache object or the ID of the cache application component that is used to
     * cache the table metadata.
     * @see enableSchemaCache
     */
    public CacheInterface|string|null $schemaCache = 'cache';
    /**
     * @var bool whether to enable query caching.
     * Note that in order to enable query caching, a valid cache component as specified by [[queryCache]] must be
     * enabled and [[enableQueryCache]] must be set true.
     * Also, only the results of the queries enclosed within [[cache()]] will be cached.
     * @see queryCache
     * @see cache()
     * @see noCache()
     */
    public bool $enableQueryCache = true;
    /**
     * @var int the default number of seconds that query results can remain valid in cache.
     * Defaults to 3600, meaning 3600 seconds, or one hour.
     * If this is 0, the cache will be invalidated on every request.
     * Use null to indicate that the cached data will never expire.
     * The value of this property will be used when [[cache()]] is called without a cache duration.
     * @see enableQueryCache
     * @see cache()
     */
    public int|null $queryCacheDuration = 3600;
    /**
     * @var CacheInterface|string the cache object or the ID of the cache application component
     * that is used for query caching.
     * @see enableQueryCache
     */
    public CacheInterface|string|null $queryCache = 'cache';
    /**
     * @var string|null the charset used for database connection. The property is only used
     * for MySQL, PostgreSQL and CUBRID databases. Defaults to null, meaning using default charset
     * as configured by the database.
     *
     * For Oracle Database, the charset must be specified in the [[dsn]], for example for UTF-8 by appending
     * `;charset=UTF-8` to the DSN string.
     *
     * The same applies for if you're using GBK or BIG5 charset with MySQL, then it's highly recommended to
     * specify charset via [[dsn]] like `'mysql:dbname=mydatabase;host=127.0.0.1;charset=GBK;'`.
     */
    public string|null $charset = null;
    /**
     * @var bool|null whether to turn on prepare emulation. Defaults to false, meaning PDO
     * will use the native prepare support if available. For some databases (such as MySQL),
     * this may need to be set true so that PDO can emulate the prepare support to bypass
     * the buggy native prepare support.
     * The default value is null, which means the PDO ATTR_EMULATE_PREPARES value will not be changed.
     */
    public bool|null $emulatePrepare = null;
    /**
     * @var string the common prefix or suffix for table names. If a table name is given
     * as `{{%TableName}}`, then the percentage character `%` will be replaced with this
     * property value. For example, `{{%post}}` becomes `{{tbl_post}}`.
     */
    public string $tablePrefix = '';
    /**
     * @var array mapping between PDO driver names and [[Schema]] classes.
     * The keys of the array are PDO driver names while the values are either the corresponding
     * schema class names or configurations. Please refer to [[Yii::createObject()]] for
     * details on how to specify a configuration.
     *
     * This property is mainly used by [[getSchema()]] when fetching the database schema information.
     * You normally do not need to set this property unless you want to use your own
     * [[Schema]] class to support DBMS that is not supported by Yii.
     */
    public array $schemaMap = [
        'pgsql' => \yii\db\pgsql\Schema::class, // PostgreSQL
        'mysqli' => \yii\db\mysql\Schema::class, // MySQL
        'mysql' => \yii\db\mysql\Schema::class, // MySQL
        'sqlite' => \yii\db\sqlite\Schema::class, // sqlite 3
        'sqlite2' => \yii\db\sqlite\Schema::class, // sqlite 2
        'sqlsrv' => \yii\db\mssql\Schema::class, // newer MSSQL driver on MS Windows hosts
        'oci' => \yii\db\oci\Schema::class, // Oracle driver
        'mssql' => \yii\db\mssql\Schema::class, // older MSSQL driver on MS Windows hosts
        'dblib' => \yii\db\mssql\Schema::class, // dblib drivers on GNU/Linux (and maybe other OSes) hosts
    ];
    /**
     * @var string|null Custom PDO wrapper class. If not set, it will use [[PDO]] or [[\yii\db\mssql\PDO]] when MSSQL is
     * used.
     * @see pdo
     */
    public string|null $pdoClass = null;
    /**
     * @var array mapping between PDO driver names and [[Command]] classes.
     * The keys of the array are PDO driver names while the values are either the corresponding command class names or
     * configurations. Please refer to [[Yii::createObject()]] for  details on how to specify a configuration.
     *
     * This property is mainly used by [[createCommand()]] to create new database [[Command]] objects.
     * You normally do not need to set this property unless you want to use your own [[Command]] class or support DBMS
     * that is not supported by Yii.
     */
    public array $commandMap = [
        'pgsql' => \yii\db\Command::class, // PostgreSQL
        'mysqli' => \yii\db\Command::class, // MySQL
        'mysql' => \yii\db\Command::class, // MySQL
        'sqlite' => \yii\db\sqlite\Command::class, // sqlite 3
        'sqlite2' => \yii\db\sqlite\Command::class, // sqlite 2
        'sqlsrv' => \yii\db\Command::class, // newer MSSQL driver on MS Windows hosts
        'oci' => \yii\db\oci\Command::class, // Oracle driver
        'mssql' => \yii\db\Command::class, // older MSSQL driver on MS Windows hosts
        'dblib' => \yii\db\Command::class, // dblib drivers on GNU/Linux (and maybe other OSes) hosts
    ];
    /**
     * @var bool whether to enable [savepoint](https://en.wikipedia.org/wiki/Savepoint).
     * Note that if the underlying DBMS does not support savepoint, setting this property to be true will have no
     * effect.
     */
    public bool $enableSavepoint = true;
    /**
     * @var CacheInterface|string|false the cache object or the ID of the cache application component that is used to
     * store the health status of the DB servers specified in [[masters]] and [[slaves]].
     * This is used only when read/write splitting is enabled or [[masters]] is not empty.
     * Set boolean `false` to disabled server status caching.
     * @see openFromPoolSequentially() for details about the failover behavior.
     * @see serverRetryInterval
     */
    public CacheInterface|string|false $serverStatusCache = 'cache';
    /**
     * @var int the retry interval in seconds for dead servers listed in [[masters]] and [[slaves]].
     * This is used together with [[serverStatusCache]].
     */
    public int $serverRetryInterval = 600;
    /**
     * @var bool whether to enable read/write splitting by using [[slaves]] to read data.
     * Note that if [[slaves]] is empty, read/write splitting will NOT be enabled no matter what value this property
     * takes.
     */
    public bool $enableSlaves = true;
    /**
     * @var array list of slave connection configurations. Each configuration is used to create a slave DB connection.
     * When [[enableSlaves]] is true, one of these configurations will be chosen and used to create a DB connection
     * for performing read queries only.
     * @see enableSlaves
     * @see slaveConfig
     */
    public array $slaves = [];
    /**
     * @var array the configuration that should be merged with every slave configuration listed in [[slaves]].
     * For example,
     *
     * ```php
     * [
     *     'username' => 'slave',
     *     'password' => 'slave',
     *     'attributes' => [
     *         // use a smaller connection timeout
     *         PDO::ATTR_TIMEOUT => 10,
     *     ],
     * ]
     * ```
     */
    public array $slaveConfig = [];
    /**
     * @var array list of master connection configurations. Each configuration is used to create a master DB connection.
     * When [[open()]] is called, one of these configurations will be chosen and used to create a DB connection
     * which will be used by this object.
     * Note that when this property is not empty, the connection setting (e.g. "dsn", "username") of this object will
     * be ignored.
     * @see masterConfig
     * @see shuffleMasters
     */
    public array $masters = [];
    /**
     * @var array the configuration that should be merged with every master configuration listed in [[masters]].
     * For example,
     *
     * ```php
     * [
     *     'username' => 'master',
     *     'password' => 'master',
     *     'attributes' => [
     *         // use a smaller connection timeout
     *         PDO::ATTR_TIMEOUT => 10,
     *     ],
     * ]
     * ```
     */
    public array $masterConfig = [];
    /**
     * @var bool whether to shuffle [[masters]] before getting one.
     * @see masters
     */
    public bool $shuffleMasters = true;
    /**
     * @var bool whether to enable logging of database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @see enableProfiling
     */
    public bool $enableLogging = true;
    /**
     * @var bool whether to enable profiling of opening database connection and database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @see enableLogging
     */
    public bool $enableProfiling = true;
    /**
     * @var bool If the database connected via pdo_dblib is SyBase.
     */
    public bool $isSybase = false;
    public array $quoterMap = [
        'oci' => \yii\db\oci\Quoter::class,
        'mysql' => \yii\db\mysql\Quoter::class,
        'pgsql' => \yii\db\pgsql\Quoter::class,
        'sqlite' => \yii\db\sqlite\Quoter::class,
        'sqlsrv' => \yii\db\mssql\Quoter::class,
    ];

    /**
     * @var array An array of [[setQueryBuilder()]] calls, holding the passed arguments.
     * Is used to restore a QueryBuilder configuration after the connection close/open cycle.
     *
     * @see restoreQueryBuilderConfiguration()
     */
    private array $_queryBuilderConfigurations = [];
    /**
     * @var Transaction the currently active transaction.
     */
    private Transaction|null $_transaction = null;
    /**
     * @var Schema the database schema.
     */
    private Schema|null $_schema = null;
    /**
     * @var string driver name.
     */
    private string $_driverName = '';
    /**
     * @var Connection|false the currently active master connection.
     */
    private Connection|false|null $_master = false;
    /**
     * @var Connection|false the currently active slave connection.
     */
    private Connection|false|null $_slave = false;
    /**
     * @var array query cache parameters for the [[cache()]] calls.
     */
    private array $_queryCacheInfo = [];
    /**
     * @var string[] quoted table name cache for [[quoteTableName()]] calls.
     */
    private array $_quotedTableNames = [];
    /**
     * @var string[] quoted column name cache for [[quoteColumnName()]] calls
     */
    private array $_quotedColumnNames = [];
    /**
     * @var Quoter|null the Quoter instance.
     */
    private Quoter|null $_quoter = null;

    /**
     * Returns a value indicating whether the DB connection is established.
     *
     * @return bool whether the DB connection is established
     */
    public function getIsActive(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Uses query cache for the queries performed with the callable.
     *
     * When query caching is enabled ([[enableQueryCache]] is true and [[queryCache]] refers to a valid cache), queries
     * performed within the callable will be cached and their results will be fetched from cache if available.
     *
     * For example,
     *
     * ```php
     * // The customer will be fetched from cache if available.
     * // If not, the query will be made against DB and cached for use next time.
     * $customer = $db->cache(
     *     static function (Connection $db): array {
     *         return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     *     }
     * );
     * ```
     *
     * Note that query cache is only meaningful for queries that return results. For queries performed with
     * [[Command::execute()]], query cache will not be used.
     *
     * @param callable $callable a PHP callable that contains DB queries which will make use of query cache.
     * The signature of the callable is `function (Connection $db)`.
     * @param int|null $duration the number of seconds that query results can remain valid in the cache. If this is not
     * set, the value of [[queryCacheDuration]] will be used instead.
     * Use null to indicate that the cached data will never expire.
     * Use 0 to indicate that the cached data will be invalidated immediately.
     * @param \yii\caching\Dependency|null $dependency the cache dependency associated with the cached query results.
     *
     * @return mixed the return result of the callable.
     *
     * @throws \Throwable if there is any exception during query.
     *
     * @see enableQueryCache
     * @see queryCache
     * @see noCache()
     */
    public function cache(callable $callable, int|null $duration = null, int|null $dependency = null): mixed
    {
        $this->_queryCacheInfo[] = [$duration === null ? $this->queryCacheDuration : $duration, $dependency];

        try {
            $result = call_user_func($callable, $this);
            array_pop($this->_queryCacheInfo);

            return $result;
        } catch (\Exception $e) {
            array_pop($this->_queryCacheInfo);

            throw $e;
        } catch (\Throwable $e) {
            array_pop($this->_queryCacheInfo);

            throw $e;
        }
    }

    /**
     * Disables query cache temporarily.
     *
     * Queries performed within the callable will not use query cache at all. For example,
     *
     * ```php
     * $db->cache(
     *     static function (Connection $db): array {
     *
     *         // ... queries that use query cache ...
     *
     *         return $db->noCache(
     *             static function (Connection $db): array {
     *                 // this query will not use query cache
     *                 return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     *             }
     *         );
     *     }
     * );
     * ```
     *
     * @param callable $callable a PHP callable that contains DB queries which should not use query cache.
     * The signature of the callable is `function (Connection $db)`.
     *
     * @return mixed the return result of the callable.
     *
     * @throws \Throwable if there is any exception during query.
     *
     * @see enableQueryCache
     * @see queryCache
     * @see cache()
     */
    public function noCache(callable $callable): mixed
    {
        $this->_queryCacheInfo[] = false;

        try {
            $result = call_user_func($callable, $this);
            array_pop($this->_queryCacheInfo);

            return $result;
        } catch (\Exception $e) {
            array_pop($this->_queryCacheInfo);

            throw $e;
        } catch (\Throwable $e) {
            array_pop($this->_queryCacheInfo);

            throw $e;
        }
    }

    /**
     * Returns the current query cache information.
     * This method is used internally by [[Command]].
     *
     * @param int|null $duration the preferred caching duration. If null, it will be ignored.
     * @param \yii\caching\Dependency|null $dependency the preferred caching dependency. If null, it will be ignored.
     *
     * @return array|null the current query cache information, or null if query cache is not enabled.
     *
     * @internal
     */
    public function getQueryCacheInfo(int|null $duration, \yii\caching\Dependency|null $dependency): array|null
    {
        if (!$this->enableQueryCache) {
            return null;
        }

        $info = end($this->_queryCacheInfo);

        if (is_array($info)) {
            if ($duration === null) {
                $duration = $info[0];
            }
            if ($dependency === null) {
                $dependency = $info[1];
            }
        }

        if ($duration === 0 || $duration > 0) {
            if (is_string($this->queryCache) && Yii::$app) {
                $cache = Yii::$app->get($this->queryCache, false);
            } else {
                $cache = $this->queryCache;
            }
            if ($cache instanceof CacheInterface) {
                return [$cache, $duration, $dependency];
            }
        }

        return null;
    }

    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     *
     * @throws Exception if connection fails.
     */
    public function open(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        if (!empty($this->masters)) {
            $db = $this->getMaster();
            if ($db !== null) {
                $this->pdo = $db->pdo;
                return;
            }

            throw new InvalidConfigException('None of the master DB servers is available.');
        }

        if (empty($this->dsn)) {
            throw new InvalidConfigException('Connection::dsn cannot be empty.');
        }

        $token = 'Opening DB connection: ' . $this->dsn;
        $enableProfiling = $this->enableProfiling;

        try {
            if ($this->enableLogging) {
                Yii::info($token, __METHOD__);
            }

            if ($enableProfiling) {
                Yii::beginProfile($token, __METHOD__);
            }

            $this->pdo = $this->createPdoInstance();
            $this->initConnection();

            if ($enableProfiling) {
                Yii::endProfile($token, __METHOD__);
            }
        } catch (\PDOException $e) {
            if ($enableProfiling) {
                Yii::endProfile($token, __METHOD__);
            }

            throw new Exception($e->getMessage(), $e->errorInfo, $e->getCode(), $e);
        }
    }

    /**
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    public function close(): void
    {
        if ($this->_master) {
            if ($this->pdo === $this->_master->pdo) {
                $this->pdo = null;
            }

            $this->_master->close();
            $this->_master = false;
        }

        if ($this->pdo !== null) {
            Yii::debug('Closing DB connection: ' . $this->dsn, __METHOD__);
            $this->pdo = null;
        }

        if ($this->_slave) {
            $this->_slave->close();
            $this->_slave = false;
        }

        $this->_schema = null;
        $this->_transaction = null;
        $this->_driverName = '';
        $this->_queryCacheInfo = [];
        $this->_quotedTableNames = [];
        $this->_quotedColumnNames = [];
    }

    /**
     * Creates the PDO instance.
     * This method is called by [[open]] to establish a DB connection.
     * The default implementation will create a PHP PDO instance.
     * You may override this method if the default PDO needs to be adapted for certain DBMS.
     *
     * @return PDO the pdo instance.
     */
    protected function createPdoInstance(): PDO
    {
        $pdoClass = $this->pdoClass;

        if ($pdoClass === null) {
            $driver = null;

            if ($this->_driverName !== '') {
                $driver = $this->_driverName;
            } elseif (($pos = strpos($this->dsn, ':')) !== false) {
                $driver = strtolower(substr($this->dsn, 0, $pos));
            }

            switch ($driver) {
                case 'mssql':
                    $pdoClass = \yii\db\mssql\PDO::class;
                    break;
                case 'dblib':
                    $pdoClass = \yii\db\mssql\DBLibPDO::class;
                    break;
                case 'sqlsrv':
                    $pdoClass = \yii\db\mssql\SqlsrvPDO::class;
                    break;
                default:
                    $pdoClass = PDO::class;
            }
        }

        $dsn = $this->dsn;

        if (strncmp('sqlite:@', $dsn, 8) === 0) {
            $dsn = 'sqlite:' . Yii::getAlias(substr($dsn, 7));
        }

        /** @var PDO $pdoClass */
        return new $pdoClass($dsn, $this->username, $this->password, $this->attributes);
    }

    /**
     * Initializes the DB connection.
     * This method is invoked right after the DB connection is established.
     * The default implementation turns on `PDO::ATTR_EMULATE_PREPARES`
     * if [[emulatePrepare]] is true, and sets the database [[charset]] if it is not empty.
     * It then triggers an [[EVENT_AFTER_OPEN]] event.
     */
    protected function initConnection(): void
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($this->emulatePrepare !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
            if ($this->driverName !== 'sqlsrv') {
                $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->emulatePrepare);
            }
        }

        if (PHP_VERSION_ID >= 80100 && $this->getDriverName() === 'sqlite') {
            $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
        }

        if (!$this->isSybase && in_array($this->getDriverName(), ['mssql', 'dblib'], true)) {
            $this->pdo->exec('SET ANSI_NULL_DFLT_ON ON');
        }

        if ($this->charset !== null && in_array($this->getDriverName(), ['pgsql', 'mysql', 'mysqli'], true)) {
            $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
        }

        $this->trigger(self::EVENT_AFTER_OPEN);
    }

    /**
     * Creates a command for execution.
     *
     * @param string|null $sql the SQL statement to be executed.
     * @param array $params the parameters to be bound to the SQL statement.
     *
     * @return Command the DB command.
     */
    public function createCommand(string|null $sql = null, array $params = [])
    {
        $driver = $this->getDriverName();
        $config = ['class' => 'yii\db\Command'];

        if (isset($this->commandMap[$driver])) {
            $config = !is_array($this->commandMap[$driver]) ?
                ['class' => $this->commandMap[$driver]] : $this->commandMap[$driver];
        }

        $config['db'] = $this;
        $config['sql'] = $sql;

        /** @var Command $command */
        $command = Yii::createObject($config);

        return $command->bindValues($params);
    }

    /**
     * Returns the currently active transaction.
     *
     * @return Transaction|null the currently active transaction. Null if no active transaction.
     */
    public function getTransaction(): Transaction|null
    {
        return $this->_transaction && $this->_transaction->getIsActive() ? $this->_transaction : null;
    }

    /**
     * Starts a transaction.
     *
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     * See [[Transaction::begin()]] for details.
     *
     * @return Transaction the transaction initiated.
     */
    public function beginTransaction(string|null $isolationLevel = null): Transaction
    {
        $this->open();

        if (($transaction = $this->getTransaction()) === null) {
            $transaction = $this->_transaction = new Transaction(['db' => $this]);
        }

        $transaction->begin($isolationLevel);

        return $transaction;
    }

    /**
     * Executes callback provided in a transaction.
     *
     * @param callable $callback a valid PHP callback that performs the job. Accepts connection instance as parameter.
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     * See [[Transaction::begin()]] for details.
     *
     * @return mixed result of callback function.
     *
     * @throws \Throwable if there is any exception during query. In this case the transaction will be rolled back.
     */
    public function transaction(callable $callback, string|null $isolationLevel = null): mixed
    {
        $transaction = $this->beginTransaction($isolationLevel);
        $level = $transaction->level;

        try {
            $result = call_user_func($callback, $this);
            if ($transaction->isActive && $transaction->level === $level) {
                $transaction->commit();
            }
        } catch (\Exception $e) {
            $this->rollbackTransactionOnLevel($transaction, $level);
            throw $e;
        } catch (\Throwable $e) {
            $this->rollbackTransactionOnLevel($transaction, $level);
            throw $e;
        }

        return $result;
    }

    /**
     * Rolls back given [[Transaction]] object if it's still active and level match.
     * In some cases rollback can fail, so this method is fail safe. Exception thrown from rollback will be caught and
     * just logged with [[\Yii::error()]].
     *
     * @param Transaction $transaction Transaction object given from [[beginTransaction()]].
     * @param int $level Transaction level just after [[beginTransaction()]] call.
     */
    private function rollbackTransactionOnLevel(Transaction $transaction, int $level): void
    {
        if ($transaction->isActive && $transaction->level === $level) {
            // https://github.com/yiisoft/yii2/pull/13347
            try {
                $transaction->rollBack();
            } catch (\Exception $e) {
                \Yii::error($e, __METHOD__);
                // hide this exception to be able to continue throwing original exception outside
            }
        }
    }

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     *
     * @throws NotSupportedException if there is no support for the current driver type.
     */
    public function getSchema(): Schema
    {
        if ($this->_schema !== null) {
            return $this->_schema;
        }

        $driver = $this->getDriverName();

        if (isset($this->schemaMap[$driver])) {
            $config = !is_array($this->schemaMap[$driver])
                ? ['class' => $this->schemaMap[$driver]] : $this->schemaMap[$driver];
            $config['db'] = $this;

            $this->_schema = Yii::createObject($config);
            $this->restoreQueryBuilderConfiguration();

            return $this->_schema;
        }

        throw new NotSupportedException("Connection does not support reading schema information for '$driver' DBMS.");
    }

    public function getQuoter(): Quoter
    {
        if ($this->_quoter === null) {
            $driver = $this->getDriverName();
            $schema = $this->getSchema();

            if (isset($this->quoterMap[$driver])) {
                $config = !is_array($this->quoterMap[$driver])
                    ? ['class' => $this->quoterMap[$driver]] : $this->quoterMap[$driver];
                $config['db'] = $this;

                $this->_quoter = Yii::createObject(
                    $config,
                    [
                        $schema->getTableQuoteCharacter(),
                        $schema->getColumnQuoteCharacter(),
                        $this->tablePrefix,
                    ],
                );
            }
        }

        return $this->_quoter;
    }

    /**
     * Returns the query builder for the current DB connection.
     *
     * @return QueryBuilder the query builder for the current DB connection.
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getSchema()->getQueryBuilder();
    }

    /**
     * Can be used to set [[QueryBuilder]] configuration via Connection configuration array.
     *
     * @param array $value the [[QueryBuilder]] properties to be configured.
     */
    public function setQueryBuilder(array $value): void
    {
        Yii::configure($this->getQueryBuilder(), $value);

        $this->_queryBuilderConfigurations[] = $value;
    }

    /**
     * Restores custom QueryBuilder configuration after the connection close/open cycle
     */
    private function restoreQueryBuilderConfiguration(): void
    {
        if ($this->_queryBuilderConfigurations === []) {
            return;
        }

        $queryBuilderConfigurations = $this->_queryBuilderConfigurations;
        $this->_queryBuilderConfigurations = [];

        foreach ($queryBuilderConfigurations as $queryBuilderConfiguration) {
            $this->setQueryBuilder($queryBuilderConfiguration);
        }
    }

    /**
     * Obtains the schema information for the named table.
     *
     * @param string $name table name.
     * @param bool $refresh whether to reload the table schema even if it is found in the cache.
     *
     * @return TableSchema|null table schema information. Null if the named table does not exist.
     */
    public function getTableSchema(string $name, bool $refresh = false): TableSchema|null
    {
        return $this->getSchema()->getTableSchema($name, $refresh);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sequenceName name of the sequence object (required by some DBMS).
     *
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object.
     *
     * @see https://www.php.net/manual/en/pdo.lastinsertid.php
     */
    public function getLastInsertID(string $sequenceName = ''): string
    {
        return $this->getSchema()->getLastInsertID($sequenceName);
    }

    /**
     * Quotes a column name for use in a query.
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains special characters including '(', '[[' and '{{', then this
     * method will do nothing.
     *
     * @param string $name column name.
     *
     * @return string the properly quoted column name.
     */
    public function quoteColumnName($name)
    {
        if (isset($this->_quotedColumnNames[$name])) {
            return $this->_quotedColumnNames[$name];
        }

        return $this->_quotedColumnNames[$name] = $this->getQuoter()->quoteColumnName($name);
    }

    /**
     * Quotes a table name for use in a query.
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains special characters including '(', '[[' and '{{', then this method
     * will do nothing.
     *
     * @param string $name table name.
     *
     * @return string the properly quoted table name.
     */
    public function quoteTableName(string $name): string
    {
        if (isset($this->_quotedTableNames[$name])) {
            return $this->_quotedTableNames[$name];
        }

        return $this->_quotedTableNames[$name] = $this->getQuoter()->quoteTableName($name);
    }

    /**
     * Processes a SQL statement by quoting table and column names that are enclosed within double brackets.
     * Tokens enclosed within double curly brackets are treated as table names, while tokens enclosed within double
     * square brackets are column names. They will be quoted accordingly.
     * Also, the percentage character "%" at the beginning or ending of a table name will be replaced with
     * [[tablePrefix]].
     *
     * @param string $sql the SQL to be quoted.
     *
     * @return string the quoted SQL.
     */
    public function quoteSql(string $sql): string
    {
        return $this->getQuoter()->quoteSql($sql);
    }

   /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     *
     * @param mixed $value the value to be quoted.
     *
     * @return mixed the properly quoted value.
     *
     * @see https://www.php.net/manual/en/pdo.quote.php
     */
    public function quoteValue(mixed $value): mixed
    {
        return $this->getQuoter()->quoteValue($value);
    }

    /**
     * Returns the name of the DB driver. Based on the the current [[dsn]], in case it was not set explicitly by an end
     * user.
     *
     * @return string|null name of the DB driver
     */
    public function getDriverName()
    {
        if ($this->_driverName === '') {
            if ($this->dsn !== null && ($pos = strpos($this->dsn, ':')) !== false) {
                $this->_driverName = strtolower(substr($this->dsn, 0, $pos));
            } else {
                $this->_driverName = strtolower($this->getSlavePdo(true)->getAttribute(PDO::ATTR_DRIVER_NAME));
            }
        }

        return $this->_driverName;
    }

    /**
     * Changes the current driver name.
     *
     * @param string $driverName name of the DB driver.
     */
    public function setDriverName(string $driverName): void
    {
        $this->_driverName = strtolower($driverName);
    }

    /**
     * Returns a server version as a string comparable by [[\version_compare()]].
     *
     * @return string server version as a string.
     */
    public function getServerVersion(): string
    {
        return $this->getSchema()->getServerVersion();
    }

    /**
     * Returns the PDO instance for the currently active slave connection.
     * When [[enableSlaves]] is true, one of the slaves will be used for read queries, and its PDO instance will be
     * returned by this method.
     *
     * @param bool $fallbackToMaster whether to return a master PDO in case none of the slave connections is available.
     * @return PDO|null the PDO instance for the currently active slave connection. `null` is returned if no slave
     * connection is available and `$fallbackToMaster` is false.
     */
    public function getSlavePdo($fallbackToMaster = true)
    {
        $db = $this->getSlave(false);

        if ($db === null) {
            return $fallbackToMaster ? $this->getMasterPdo() : null;
        }

        return $db->pdo;
    }

    /**
     * Returns the PDO instance for the currently active master connection.
     * This method will open the master DB connection and then return [[pdo]].
     *
     * @return PDO the PDO instance for the currently active master connection.
     */
    public function getMasterPdo(): PDO
    {
        $this->open();

        return $this->pdo;
    }

    /**
     * Returns the currently active slave connection.
     * If this method is called for the first time, it will try to open a slave connection when [[enableSlaves]] is
     * true.
     * @param bool $fallbackToMaster whether to return a master connection in case there is no slave connection
     * available.
     *
     * @return Connection|null the currently active slave connection. `null` is returned if there is no slave available
     * and `$fallbackToMaster` is false.
     */
    public function getSlave(bool $fallbackToMaster = true): Connection|null
    {
        if (!$this->enableSlaves) {
            return $fallbackToMaster ? $this : null;
        }

        if ($this->_slave === false) {
            $this->_slave = $this->openFromPool($this->slaves, $this->slaveConfig);
        }

        return $this->_slave === null && $fallbackToMaster ? $this : $this->_slave;
    }

    /**
     * Returns the currently active master connection.
     * If this method is called for the first time, it will try to open a master connection.
     *
     * @return Connection|null the currently active master connection. `null` is returned if there is no master
     * available.
     */
    public function getMaster(): Connection|null
    {
        if ($this->_master === false) {
            $this->_master = $this->shuffleMasters
                ? $this->openFromPool($this->masters, $this->masterConfig)
                : $this->openFromPoolSequentially($this->masters, $this->masterConfig);
        }

        return $this->_master;
    }

    /**
     * Executes the provided callback by using the master connection.
     *
     * This method is provided so that you can temporarily force using the master connection to perform DB operations
     * even if they are read queries. For example,
     *
     * ```php
     * $result = $db->useMaster(
     *     static function ($db): array {
     *         return $db->createCommand('SELECT * FROM user LIMIT 1')->queryOne();
     *     }
     * );
     * ```
     *
     * @param callable $callback a PHP callable to be executed by this method. Its signature is
     * `function (Connection $db)`. Its return value will be returned by this method.
     *
     * @return mixed the return value of the callback.
     *
     * @throws \Throwable if there is any exception thrown from the callback.
     */
    public function useMaster(callable $callback): mixed
    {
        if ($this->enableSlaves) {
            $this->enableSlaves = false;

            try {
                $result = call_user_func($callback, $this);
            } catch (\Exception $e) {
                $this->enableSlaves = true;

                throw $e;
            } catch (\Throwable $e) {
                $this->enableSlaves = true;

                throw $e;
            }
            // TODO: use "finally" keyword when miminum required PHP version is >= 5.5
            $this->enableSlaves = true;
        } else {
            $result = call_user_func($callback, $this);
        }

        return $result;
    }

    /**
     * Opens the connection to a server in the pool.
     *
     * This method implements load balancing and failover among the given list of the servers.
     * Connections will be tried in random order.
     * For details about the failover behavior, see [[openFromPoolSequentially]].
     *
     * @param array $pool the list of connection configurations in the server pool
     * @param array $sharedConfig the configuration common to those given in `$pool`.
     *
     * @return Connection|null the opened DB connection, or `null` if no server is available.
     *
     * @throws InvalidConfigException if a configuration does not specify "dsn".
     *
     * @see openFromPoolSequentially
     */
    protected function openFromPool(array $pool, array $sharedConfig): Connection|null
    {
        shuffle($pool);

        return $this->openFromPoolSequentially($pool, $sharedConfig);
    }

    /**
     * Opens the connection to a server in the pool.
     *
     * This method implements failover among the given list of servers.
     * Connections will be tried in sequential order. The first successful connection will return.
     *
     * If [[serverStatusCache]] is configured, this method will cache information about unreachable servers and does not
     * try to connect to these for the time configured in [[serverRetryInterval]].
     * This helps to keep the application stable when some servers are unavailable. Avoiding connection attempts to
     * unavailable servers saves time when the connection attempts fail due to timeout.
     *
     * If none of the servers are available the status cache is ignored and connection attempts are made to all servers.
     * This is to avoid downtime when all servers are unavailable for a short time.
     * After a successful connection attempt the server is marked as available again.
     *
     * @param array $pool the list of connection configurations in the server pool.
     * @param array $sharedConfig the configuration common to those given in `$pool`.
     *
     * @return Connection|null the opened DB connection, or `null` if no server is available.
     *
     * @throws InvalidConfigException if a configuration does not specify "dsn".
     *
     * @see openFromPool
     * @see serverStatusCache
     */
    protected function openFromPoolSequentially(array $pool, array $sharedConfig): Connection|null
    {
        if (empty($pool)) {
            return null;
        }

        if (!isset($sharedConfig['class'])) {
            $sharedConfig['class'] = get_class($this);
        }

        $cache = is_string($this->serverStatusCache)
            ? Yii::$app->get($this->serverStatusCache, false) : $this->serverStatusCache;

        foreach ($pool as $i => $config) {
            $pool[$i] = $config = array_merge($sharedConfig, $config);
            if (empty($config['dsn'])) {
                throw new InvalidConfigException('The "dsn" option must be specified.');
            }

            $key = [__METHOD__, $config['dsn']];

            if ($cache instanceof CacheInterface && $cache->get($key)) {
                // should not try this dead server now
                continue;
            }

            /* @var $db Connection */
            $db = Yii::createObject($config);

            try {
                $db->open();
                return $db;
            } catch (\Exception $e) {
                Yii::warning("Connection ({$config['dsn']}) failed: " . $e->getMessage(), __METHOD__);

                if ($cache instanceof CacheInterface) {
                    // mark this server as dead and only retry it after the specified interval
                    $cache->set($key, 1, $this->serverRetryInterval);
                }

                // exclude server from retry below
                unset($pool[$i]);
            }
        }

        if ($cache instanceof CacheInterface) {
            // if server status cache is enabled and no server is available
            // ignore the cache and try to connect anyway
            // $pool now only contains servers we did not already try in the loop above
            foreach ($pool as $config) {
                /* @var $db Connection */
                $db = Yii::createObject($config);

                try {
                    $db->open();
                } catch (\Exception $e) {
                    Yii::warning("Connection ({$config['dsn']}) failed: " . $e->getMessage(), __METHOD__);
                    continue;
                }

                // mark this server as available again after successful connection
                $cache->delete([__METHOD__, $config['dsn']]);

                return $db;
            }
        }

        return null;
    }

    /**
     * Close the connection before serializing.
     *
     * @return array the property names of the object to be serialized.
     */
    public function __sleep(): array
    {
        $fields = (array) $this;

        unset($fields['pdo']);
        unset($fields["\000" . __CLASS__ . "\000" . '_master']);
        unset($fields["\000" . __CLASS__ . "\000" . '_slave']);
        unset($fields["\000" . __CLASS__ . "\000" . '_transaction']);
        unset($fields["\000" . __CLASS__ . "\000" . '_schema']);

        return array_keys($fields);
    }

    /**
     * Reset the connection after cloning.
     */
    public function __clone(): void
    {
        parent::__clone();

        $this->_master = false;
        $this->_slave = false;
        $this->_schema = null;
        $this->_transaction = null;

        if (strncmp($this->dsn, 'sqlite::memory:', 15) !== 0) {
            // reset PDO connection, unless its sqlite in-memory, which can only have one connection
            $this->pdo = null;
        }
    }
}
