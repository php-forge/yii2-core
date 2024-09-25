<?php

declare(strict_types=1);

namespace yii\db\pgsql;

use yii\db\Expression;
use yii\db\QueryInterface;
use yii\db\SqlHelper;

use function str_ends_with;
use function strtolower;

/**
 * QueryBuilder is the query builder for PostgreSQL databases.
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * Defines a UNIQUE index for [[createIndex()]].
     * @since 2.0.6
     */
    const INDEX_UNIQUE = 'unique';
    /**
     * Defines a B-tree index for [[createIndex()]].
     * @since 2.0.6
     */
    const INDEX_B_TREE = 'btree';
    /**
     * Defines a hash index for [[createIndex()]].
     * @since 2.0.6
     */
    const INDEX_HASH = 'hash';
    /**
     * Defines a GiST index for [[createIndex()]].
     * @since 2.0.6
     */
    const INDEX_GIST = 'gist';
    /**
     * Defines a GIN index for [[createIndex()]].
     * @since 2.0.6
     */
    const INDEX_GIN = 'gin';

    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    public $typeMap = [
        Schema::TYPE_PK => 'serial NOT NULL PRIMARY KEY',
        Schema::TYPE_UPK => 'serial NOT NULL PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigserial NOT NULL PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigserial NOT NULL PRIMARY KEY',
        Schema::TYPE_CHAR => 'char(1)',
        Schema::TYPE_STRING => 'varchar(255)',
        Schema::TYPE_TEXT => 'text',
        Schema::TYPE_TINYINT => 'smallint',
        Schema::TYPE_SMALLINT => 'smallint',
        Schema::TYPE_INTEGER => 'integer',
        Schema::TYPE_BIGINT => 'bigint',
        Schema::TYPE_FLOAT => 'double precision',
        Schema::TYPE_DOUBLE => 'double precision',
        Schema::TYPE_DECIMAL => 'numeric(10,0)',
        Schema::TYPE_DATETIME => 'timestamp(0)',
        Schema::TYPE_TIMESTAMP => 'timestamp(0)',
        Schema::TYPE_TIME => 'time(0)',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'bytea',
        Schema::TYPE_BOOLEAN => 'boolean',
        Schema::TYPE_MONEY => 'numeric(19,4)',
        Schema::TYPE_JSON => 'jsonb',
    ];


    /**
     * {@inheritdoc}
     */
    protected function defaultConditionClasses()
    {
        return array_merge(parent::defaultConditionClasses(), [
            'ILIKE' => 'yii\db\conditions\LikeCondition',
            'NOT ILIKE' => 'yii\db\conditions\LikeCondition',
            'OR ILIKE' => 'yii\db\conditions\LikeCondition',
            'OR NOT ILIKE' => 'yii\db\conditions\LikeCondition',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultExpressionBuilders()
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            'yii\db\ArrayExpression' => 'yii\db\pgsql\ArrayExpressionBuilder',
            'yii\db\JsonExpression' => 'yii\db\pgsql\JsonExpressionBuilder',
        ]);
    }

    /**
     * Builds a SQL statement for creating a new index.
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by the method.
     * @param string|array $columns the column(s) that should be included in the index. If there are multiple columns,
     * separate them with commas or use an array to represent them. Each column name will be properly quoted
     * by the method, unless a parenthesis is found in the name.
     * @param bool|string $unique whether to make this a UNIQUE index constraint. You can pass `true` or [[INDEX_UNIQUE]] to create
     * a unique index, `false` to make a non-unique index using the default index type, or one of the following constants to specify
     * the index method to use: [[INDEX_B_TREE]], [[INDEX_HASH]], [[INDEX_GIST]], [[INDEX_GIN]].
     * @return string the SQL statement for creating a new index.
     * @see https://www.postgresql.org/docs/8.2/sql-createindex.html
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        if ($unique === self::INDEX_UNIQUE || $unique === true) {
            $index = false;
            $unique = true;
        } else {
            $index = $unique;
            $unique = false;
        }

        return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ') .
        $this->db->quoteTableName($name) . ' ON ' .
        $this->db->quoteTableName($table) .
        ($index !== false ? " USING $index" : '') .
        ' (' . $this->buildColumns($columns) . ')';
    }

    /**
     * Creates an `SEQUENCE` SQL statement.
     *
     * @param string $sequence the name of the sequence.
     * The name will be properly quoted by the method.
     * The sequence name will be generated based on the suffix '_SEQ' if it is not provided. For example sequence name
     * for the table `customer` will be `customer_SEQ`.
     * @param int $start the starting value for the sequence. Defaults to `1`.
     * @param int $increment the increment value for the sequence. Defaults to `1`.
     * @param array $options the additional SQL fragment that will be appended to the generated SQL.
     * If enabled, the `CACHE` option will be used to cache sequence values for better performance, example
     * `cache` => `20`, will cache 20 sequence values. If `false` is provided, the `NOCACHE` option will be used.
     * If enabled, the `CYCLE` option will be used to allow the sequence to restart once the maximal value is reached.
     * If `false` is provided, the `NOCYCLE` option will be used.
     * If enabled, the `MINVALUE` option will be used to set the minimal value for the sequence. If `false` is provided,
     * the `NO MINVALUE` option will be used. If not provided, the default value will be used.
     * If enabled, the `MAXVALUE` option will be used to set the maximal value for the sequence. If `false` is provided,
     * for default the `PHP_INT_MAX` value will be used.
     * If enabled, the `TYPE` option will be used to set the sequence data type. If not provided, the default value will
     * be used.
     *
     * @return string the SQL statement for creating the sequence.
     *
     * @see https://www.postgresql.org/docs/9.5/sql-createsequence.html
     */
    public function createSequence(string $sequence, int $start = 1, int $increment = 1, array $options = []): string
    {
        $types = ['bigint', 'int', 'smallint'];

        $type = isset($options['type']) && in_array($options['type'], $types, true)
            ? 'AS ' . $options['type'] : '';
        $minValue = isset($options['minValue']) && is_int($options['minValue'])
            ? 'MINVALUE ' . $options['minValue'] : 'NO MINVALUE';
        $maxValue = isset($options['maxValue']) && is_int($options['maxValue'])
            ? 'MAXVALUE ' . $options['maxValue'] : 'NO MAXVALUE';
        $cycle = isset($options['cycle']) ? 'CYCLE' : 'NO CYCLE';
        $cache = isset($options['cache']) && is_int($options['cache']) ? 'CACHE ' . $options['cache'] : '';

        if (str_ends_with(strtolower($sequence), '_seq') === false) {
            $sequence .= '_SEQ';
        }

        if ($start < 1) {
            $minValue = "MINVALUE $start";
        }

        $sql = <<<SQL
        CREATE SEQUENCE {$this->db->quoteTableName($sequence)}
            $type
            INCREMENT BY $increment
            $minValue
            $maxValue
            START WITH $start
            $cycle
            $cache
        SQL;

        return SqlHelper::cleanSql($sql);
    }

    /**
     * Builds a SQL statement for dropping an index.
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping an index.
     */
    public function dropIndex($name, $table)
    {
        if (strpos($table, '.') !== false && strpos($name, '.') === false) {
            if (strpos($table, '{{') !== false) {
                $table = preg_replace('/\\{\\{(.*?)\\}\\}/', '\1', $table);
                list($schema, $table) = explode('.', $table);
                if (strpos($schema, '%') === false) {
                    $name = $schema . '.' . $name;
                } else {
                    $name = '{{' . $schema . '.' . $name . '}}';
                }
            } else {
                list($schema) = explode('.', $table);
                $name = $schema . '.' . $name;
            }
        }
        return 'DROP INDEX ' . $this->db->quoteTableName($name);
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     * @param string $oldName the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable($oldName, $newName)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($oldName) . ' RENAME TO ' . $this->db->quoteTableName($newName);
    }

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     * @param bool $check whether to turn on or off the integrity check.
     * @param string $schema the schema of the tables.
     * @param string $table the table name.
     * @return string the SQL statement for checking integrity
     */
    public function checkIntegrity($check = true, $schema = '', $table = '')
    {
        $enable = $check ? 'ENABLE' : 'DISABLE';
        $schema = $schema ?: $this->db->getSchema()->defaultSchema;
        $tableNames = $table ? [$table] : $this->db->getSchema()->getTableNames($schema);
        $viewNames = $this->db->getSchema()->getViewNames($schema);
        $tableNames = array_diff($tableNames, $viewNames);
        $command = '';

        foreach ($tableNames as $tableName) {
            $tableName = $this->db->quoteTableName("{$schema}.{$tableName}");
            $command .= "ALTER TABLE $tableName $enable TRIGGER ALL; ";
        }

        // enable to have ability to alter several tables
        $this->db->getMasterPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

        return $command;
    }

    /**
     * Builds a SQL statement for truncating a DB table.
     * Explicitly restarts identity for PGSQL to be consistent with other databases which all do this by default.
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     * @return string the SQL statement for truncating a DB table.
     */
    public function truncateTable($table)
    {
        return 'TRUNCATE TABLE ' . $this->db->quoteTableName($table) . ' RESTART IDENTITY';
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The [[getColumnType()]] method will be invoked to convert abstract
     * column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept
     * in the generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null'
     * will become 'varchar(255) not null'. You can also use PostgreSQL-specific syntax such as `SET NOT NULL`.
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn($table, $column, $type)
    {
        $columnName = $this->db->quoteColumnName($column);
        $tableName = $this->db->quoteTableName($table);

        // https://github.com/yiisoft/yii2/issues/4492
        // https://www.postgresql.org/docs/9.1/sql-altertable.html
        if (preg_match('/^(DROP|SET|RESET)\s+/i', (string) $type)) {
            return "ALTER TABLE {$tableName} ALTER COLUMN {$columnName} {$type}";
        }

        $type = 'TYPE ' . $this->getColumnType($type);

        $multiAlterStatement = [];
        $constraintPrefix = preg_replace('/[^a-z0-9_]/i', '', $table . '_' . $column);

        if (preg_match('/\s+DEFAULT\s+(["\']?\w*["\']?)/i', $type, $matches)) {
            $type = preg_replace('/\s+DEFAULT\s+(["\']?\w*["\']?)/i', '', $type);
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} SET DEFAULT {$matches[1]}";
        } else {
            // safe to drop default even if there was none in the first place
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} DROP DEFAULT";
        }

        $type = preg_replace('/\s+NOT\s+NULL/i', '', $type, -1, $count);
        if ($count) {
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} SET NOT NULL";
        } else {
            // remove additional null if any
            $type = preg_replace('/\s+NULL/i', '', $type);
            // safe to drop not null even if there was none in the first place
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} DROP NOT NULL";
        }

        if (preg_match('/\s+CHECK\s+\((.+)\)/i', $type, $matches)) {
            $type = preg_replace('/\s+CHECK\s+\((.+)\)/i', '', $type);
            $multiAlterStatement[] = "ADD CONSTRAINT {$constraintPrefix}_check CHECK ({$matches[1]})";
        }

        $type = preg_replace('/\s+UNIQUE/i', '', $type, -1, $count);
        if ($count) {
            $multiAlterStatement[] = "ADD UNIQUE ({$columnName})";
        }

        // add what's left at the beginning
        array_unshift($multiAlterStatement, "ALTER COLUMN {$columnName} {$type}");

        return 'ALTER TABLE ' . $tableName . ' ' . implode(', ', $multiAlterStatement);
    }

    public function insertWithReturningPks(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        $tableSchema = $this->db->getTableSchema($table);

        $returnColumns = [];

        if ($tableSchema !== null) {
            $returnColumns = $tableSchema->primaryKey ?? [];
        }

        $sql = $this->insert($table, $columns, $params);

        if (empty($returnColumns)) {
            return $sql;
        }

        $returning = [];

        foreach ($returnColumns as $name) {
            $returning[] = $this->db->quoteColumnName($name);
        }

        return $sql . ' RETURNING ' . implode(', ', $returning);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://www.postgresql.org/docs/9.5/static/sql-insert.html#SQL-ON-CONFLICT
     * @see https://stackoverflow.com/questions/1109061/insert-on-duplicate-update-in-postgresql/8702291#8702291
     */
    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        $updateColumns,
        array &$params = []
    ): string {
        $insertSql = $this->insert($table, $insertColumns, $params);

        /** @psalm-var array $uniqueNames */
        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
        );

        if (empty($uniqueNames)) {
            return $insertSql;
        }

        if ($updateNames === []) {
            /** there are no columns to update */
            $updateColumns = false;
        }

        if ($updateColumns === false) {
            return "$insertSql ON CONFLICT DO NOTHING";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            /** @psalm-var string $name */
            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression(
                    'EXCLUDED.' . $this->db->quoteColumnName($name)
                );
            }
        }

        /**
         * @psalm-var array $updateColumns
         * @psalm-var string[] $uniqueNames
         * @psalm-var string[] $updates
         */
        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql
            . ' ON CONFLICT (' . implode(', ', $uniqueNames) . ') DO UPDATE SET ' . implode(', ', $updates);
    }
}
