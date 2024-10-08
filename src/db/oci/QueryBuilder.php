<?php

declare(strict_types=1);

namespace yii\db\oci;

use PDO;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\db\QueryInterface;
use yii\db\SqlHelper;
use yii\db\TableSchema;

/**
 * QueryBuilder is the query builder for Oracle databases.
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    public $typeMap = [
        Schema::TYPE_PK => 'NUMBER(10) NOT NULL PRIMARY KEY',
        Schema::TYPE_UPK => 'NUMBER(10) UNSIGNED NOT NULL PRIMARY KEY',
        Schema::TYPE_BIGPK => 'NUMBER(20) NOT NULL PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'NUMBER(20) UNSIGNED NOT NULL PRIMARY KEY',
        Schema::TYPE_CHAR => 'CHAR(1)',
        Schema::TYPE_STRING => 'VARCHAR2(255)',
        Schema::TYPE_TEXT => 'CLOB',
        Schema::TYPE_TINYINT => 'NUMBER(3)',
        Schema::TYPE_SMALLINT => 'NUMBER(5)',
        Schema::TYPE_INTEGER => 'NUMBER(10)',
        Schema::TYPE_BIGINT => 'NUMBER(20)',
        Schema::TYPE_FLOAT => 'NUMBER',
        Schema::TYPE_DOUBLE => 'NUMBER',
        Schema::TYPE_DECIMAL => 'NUMBER',
        Schema::TYPE_DATETIME => 'TIMESTAMP',
        Schema::TYPE_TIMESTAMP => 'TIMESTAMP',
        Schema::TYPE_TIME => 'TIMESTAMP',
        Schema::TYPE_DATE => 'DATE',
        Schema::TYPE_BINARY => 'BLOB',
        Schema::TYPE_BOOLEAN => 'NUMBER(1)',
        Schema::TYPE_MONEY => 'NUMBER(19,4)',
    ];


    /**
     * {@inheritdoc}
     */
    protected function defaultExpressionBuilders()
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            'yii\db\conditions\InCondition' => 'yii\db\oci\conditions\InConditionBuilder',
            'yii\db\conditions\LikeCondition' => 'yii\db\oci\conditions\LikeConditionBuilder',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildOrderByAndLimit(
        string $sql,
        array|null $orderBy,
        ExpressionInterface|int|null $limit,
        ExpressionInterface|int|null $offset,
    ): string {
        $orderByString = $this->buildOrderBy($orderBy);

        if ($orderByString !== '') {
            $sql .= $this->separator . $orderByString;
        }

        $filters = [];

        if ($this->hasOffset($offset)) {
            $filters[] = 'rowNumId > ' .
                ($offset instanceof ExpressionInterface ? $this->buildExpression($offset) : (string)$offset);
        }

        if ($this->hasLimit($limit)) {
            $filters[] = 'rownum <= ' .
                ($limit instanceof ExpressionInterface ? $this->buildExpression($limit) : (string)$limit);
        }

        if (empty($filters)) {
            return $sql;
        }

        $filter = implode(' AND ', $filters);

        return <<<SQL
        WITH USER_SQL AS ($sql), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
        SELECT * FROM PAGINATION WHERE $filter
        SQL;
    }

    /**
     * Creates an `SEQUENCE` SQL statement.
     *
     * @param string $sequence the name of the sequence.
     * The sequence name will be generated based on the suffix '_SEQ' if it is not provided.
     * For example sequence name for the table `customer` will be `customer_SEQ`.
     * The name will be properly quoted by the method.
     * @param int $start the starting value for the sequence. Defaults to `1`.
     * @param int $increment the increment value for the sequence. Defaults to `1`.
     * @param array $options the additional SQL fragment that will be appended to the generated SQL.
     * If enabled, the `CACHE` option will be used to cache sequence values for better performance, example
     * `cache` => `20`, will cache 20 sequence values. If `false` is provided, the `NOCACHE` option will be used.
     * If enabled, the `CYCLE` option will be used to allow the sequence to restart once the maximal value is reached.
     * If `false` is provided, the `NOCYCLE` option will be used.
     * If enabled, the `MAXVALUE` option will be used to set the maximal value for the sequence. If `false` is provided,
     * for default the `PHP_INT_MAX` value will be used.
     * If enabled, the `MINVALUE` option will be used to set the minimal value for the sequence. If `false` is provided,
     * for default the `start` value will be used.
     *
     * example:
     *
     * ```php
     * $sql = $queryBuilder->createSequence(
     *     'user',
     *      1,
     *      1,
     *      [
     *          'cache' => 20,
     *          'cycle' => true,
     *          'maxValue' => 1000,
     *      ]
     * );
     * ```
     *
     * @return string the SQL statement for creating the sequence.
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/23/sqlrf/CREATE-SEQUENCE.html
     */
    public function createSequence(string $sequence, int $start = 1, int $increment = 1, array $options = []): string
    {
        $minValue = isset($options['minValue']) && is_int($options['minValue'])
            ? 'MINVALUE ' . $options['minValue'] : 'MINVALUE ' . $start;
        $maxValue = isset($options['maxValue']) && is_int($options['maxValue'])
            ? 'MAXVALUE ' . $options['maxValue'] : 'MAXVALUE ' . PHP_INT_MAX;
        $cache = isset($options['cache']) && is_int($options['cache']) ? 'CACHE ' . $options['cache'] : 'NOCACHE';
        $cycle = isset($options['cycle']) ? 'CYCLE' : 'NOCYCLE';

        $sequence = SqlHelper::addSuffix($sequence, '_SEQ');

        $sql = <<<SQL
        CREATE SEQUENCE {$this->db->quoteTableName($sequence)}
            START WITH $start
            $minValue
            INCREMENT BY $increment
            $maxValue
            $cache
            $cycle
        SQL;

        return SqlHelper::cleanSql($sql);
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     *
     * @param string $table the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable($table, $newName)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' RENAME TO ' . $this->db->quoteTableName($newName);
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     *
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The [[getColumnType]] method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn($table, $column, $type)
    {
        $type = $this->getColumnType($type);

        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' MODIFY ' . $this->db->quoteColumnName($column) . ' ' . $this->getColumnType($type);
    }

    /**
     * Builds a SQL statement for dropping an index.
     *
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping an index.
     */
    public function dropIndex($name, $table)
    {
        return 'DROP INDEX ' . $this->db->quoteTableName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $sql = 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->db->quoteColumnName($name)
            . ' FOREIGN KEY (' . $this->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->db->quoteTableName($refTable)
            . ' (' . $this->buildColumns($refColumns) . ')';
        if ($delete !== null) {
            $sql .= ' ON DELETE ' . $delete;
        }
        if ($update !== null) {
            throw new Exception('Oracle does not support ON UPDATE clause.');
        }

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareInsertValues(
        TableSchema|null $tableSchema,
        array|QueryInterface $columns,
        array $params = []
    ): array {
        /**
         * @var array $names
         * @var array $placeholders
         */
        [$names, $placeholders, $values, $params] = parent::prepareInsertValues($tableSchema, $columns, $params);

        if (!$columns instanceof QueryInterface && empty($names)) {
            if ($tableSchema !== null) {
                $tableColumns = $tableSchema->columns ?? [];
                $columns = !empty($tableSchema->primaryKey) ? $tableSchema->primaryKey : [reset($tableColumns)->name];

                foreach ($columns as $name) {
                    /** @psalm-var mixed */
                    $names[] = $this->db->quoteColumnName($name);
                    $placeholders[] = 'DEFAULT';
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.oracle.com/cd/B28359_01/server.111/b28286/statements_9016.htm#SQLRF01606
     */
    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array &$params,
    ): string {
        $usingValues = null;
        $constraints = [];

        [$uniqueNames, $insertNames, $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints,
        );

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        if ($updateNames === []) {
            /** there are no columns to update */
            $updateColumns = false;
        }

        $onCondition = ['or'];
        $quotedTableName = $this->db->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $columnNames = $constraint->columnNames ?? [];
            $constraintCondition = ['and'];

            /** @psalm-var string[] $columnNames */
            foreach ($columnNames as $name) {
                if (null !== $name) {
                    $quotedName = $this->db->quoteColumnName($name);
                    $constraintCondition[] = "$quotedTableName.$quotedName=\"EXCLUDED\".$quotedName";
                }
            }

            $onCondition[] = $constraintCondition;
        }

        $on = $this->buildCondition($onCondition, $params);

        /** @psalm-var string[] $placeholders */
        [, $placeholders, $values, $params] = $this->prepareInsertValues(
            $this->db->getTableSchema($table),
            $insertColumns,
            $params,
        );

        if (!empty($placeholders)) {
            $usingSelectValues = [];
            /** @psalm-var string[] $insertNames */
            foreach ($insertNames as $index => $name) {
                $usingSelectValues[$name] = new Expression($placeholders[$index]);
            }

            /** @psalm-var array $params */
            $usingValues = $this->buildSelect($usingSelectValues, $params) . ' ' . $this->buildFrom(['DUAL'], $params);
        }

        $insertValues = [];
        $mergeSql = 'MERGE INTO '
            . $this->db->quoteTableName($table)
            . ' '
            . 'USING (' . ($usingValues ?? ltrim((string) $values, ' '))
            . ') "EXCLUDED" '
            . "ON ($on)";

        /** @psalm-var string[] $insertNames */
        foreach ($insertNames as $name) {
            $quotedName = $this->db->quoteColumnName($name);

            if (strrpos($quotedName, '.') === false) {
                $quotedName = '"EXCLUDED".' . $quotedName;
            }

            $insertValues[] = $quotedName;
        }

        $insertSql = 'INSERT (' . implode(', ', $insertNames) . ')' . ' VALUES (' . implode(', ', $insertValues) . ')';

        if ($updateColumns === false) {
            return "$mergeSql WHEN NOT MATCHED THEN $insertSql";
        }

        if ($updateColumns === true) {
            $updateColumns = [];
            /** @psalm-var string[] $updateNames */
            foreach ($updateNames as $name) {
                $quotedName = $this->db->quoteColumnName($name);

                if (strrpos($quotedName, '.') === false) {
                    $quotedName = '"EXCLUDED".' . $quotedName;
                }

                $updateColumns[$name] = new Expression($quotedName);
            }
        }

        /** @psalm-var string[] $updates */
        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, (array) $params);
        $updateSql = 'UPDATE SET ' . implode(', ', $updates);

        return "$mergeSql WHEN MATCHED THEN $updateSql WHEN NOT MATCHED THEN $insertSql";
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function selectExists($rawSql)
    {
        return 'SELECT CASE WHEN EXISTS(' . $rawSql . ') THEN 1 ELSE 0 END FROM DUAL';
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function dropCommentFromColumn($table, $column)
    {
        return 'COMMENT ON COLUMN ' . $this->db->quoteTableName($table) . '.' . $this->db->quoteColumnName($column) . " IS ''";
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function dropCommentFromTable($table)
    {
        return 'COMMENT ON TABLE ' . $this->db->quoteTableName($table) . " IS ''";
    }

    /**
     * {@inheritdoc}
     */
    public function insertWithReturningPks(
        string $table,
        QueryInterface|array $columns,
        array &$params = [],
        array &$returnParams = [],
    ): string {
        $tableSchema = $this->db->getTableSchema($table);

        $primaryKeys = $tableSchema->primaryKey ?? [];

        $sql = $this->insert($table, $columns, $params);

        if (empty($primaryKeys)) {
            return $sql;
        }

        $columnSchemas = $tableSchema?->columns ?? [];
        $returnColumns = array_intersect_key($columnSchemas, array_flip($primaryKeys));

        $returning = [];

        foreach ($returnColumns as $returnColumn) {
            $phName = static::PARAM_PREFIX . (count($params) + count($returnParams));

            $returnParams[$phName] = [
                'column' => $returnColumn->name,
                'value' => '',
                'dataType' => $returnColumn->phpType === Schema::TYPE_STRING ? PDO::PARAM_STR : PDO::PARAM_INT,
                'size' => $returnColumn->size ?? $returnColumn->size - 1,
            ];

            $returning[] = $this->db->quoteColumnName($returnColumn->name);
        }

        return $sql . ' RETURNING ' . implode(', ', $returning) . ' INTO ' . implode(', ', array_keys($returnParams));
    }

    /**
     * {@inheritdoc}
     */
    protected function buildBatchInsertSql(string $table, array $columns, array $values): string
    {
        $columns = match ($columns) {
            [] => '',
            default => ' (' . implode(', ', $columns) . ')',
        };
        $tableAndColumns = ' INTO ' . $this->db->quoteTableName($table) . $columns . ' VALUES ';

        return 'INSERT ALL' . $tableAndColumns . implode($tableAndColumns, $values) . ' SELECT 1 FROM SYS.DUAL';
    }
}
