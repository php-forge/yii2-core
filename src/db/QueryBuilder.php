<?php

declare(strict_types=1);

namespace yii\db;

use Generator;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\conditions\ConditionInterface;
use yii\db\conditions\HashCondition;

/**
 * QueryBuilder builds a SELECT SQL statement based on the specification given as a [[Query]] object.
 *
 * SQL statements are created from [[Query]] objects using the [[build()]]-method.
 *
 * QueryBuilder is also used by [[Command]] to build SQL statements such as INSERT, UPDATE, DELETE, CREATE TABLE.
 *
 * For more details and usage information on QueryBuilder, see the [guide article on query builders](guide:db-query-builder).
 *
 * @property-write string[] $conditionClasses Map of condition aliases to condition classes. For example:
 * ```php ['LIKE' => yii\db\condition\LikeCondition::class] ``` .
 * @property-write string[] $expressionBuilders Array of builders that should be merged with the pre-defined ones in
 * [[expressionBuilders]] property.
 */
class QueryBuilder extends \yii\base\BaseObject
{
    /**
     * The prefix for automatically generated query binding parameters.
     */
    const PARAM_PREFIX = ':qp';

    /**
     * @var Connection the database connection.
     */
    public $db;
    /**
     * @var string the separator between different fragments of a SQL statement.
     * Defaults to an empty space. This is mainly used by [[build()]] when generating a SQL statement.
     */
    public $separator = ' ';
    /**
     * @var array the abstract column types mapped to physical column types.
     * This is mainly used to support creating/modifying tables using DB-independent data type specifications.
     * Child classes should override this property to declare supported type mappings.
     */
    public $typeMap = [];

    /**
     * @var array map of condition aliases to condition classes. For example:
     *
     * ```php
     * return [
     *     'LIKE' => yii\db\condition\LikeCondition::class,
     * ];
     * ```
     *
     * This property is used by [[createConditionFromArray]] method.
     * See default condition classes list in [[defaultConditionClasses()]] method.
     *
     * In case you want to add custom conditions support, use the [[setConditionClasses()]] method.
     *
     * @see setConditionClasses()
     * @see defaultConditionClasses()
     * @since 2.0.14
     */
    protected $conditionClasses = [];
    /**
     * @var string[]|ExpressionBuilderInterface[] maps expression class to expression builder class.
     * For example:
     *
     * ```php
     * [
     *    yii\db\Expression::class => yii\db\ExpressionBuilder::class
     * ]
     * ```
     * This property is mainly used by [[buildExpression()]] to build SQL expressions form expression objects.
     * See default values in [[defaultExpressionBuilders()]] method.
     *
     *
     * To override existing builders or add custom, use [[setExpressionBuilder()]] method. New items will be added
     * to the end of this array.
     *
     * To find a builder, [[buildExpression()]] will check the expression class for its exact presence in this map.
     * In case it is NOT present, the array will be iterated in reverse direction, checking whether the expression
     * extends the class, defined in this map.
     *
     * @see setExpressionBuilders()
     * @see defaultExpressionBuilders()
     * @since 2.0.14
     */
    protected $expressionBuilders = [];


    /**
     * Constructor.
     * @param Connection $connection the database connection.
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($connection, $config = [])
    {
        $this->db = $connection;
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->expressionBuilders = array_merge($this->defaultExpressionBuilders(), $this->expressionBuilders);
        $this->conditionClasses = array_merge($this->defaultConditionClasses(), $this->conditionClasses);
    }

    /**
     * Contains array of default condition classes. Extend this method, if you want to change
     * default condition classes for the query builder. See [[conditionClasses]] docs for details.
     *
     * @return array
     * @see conditionClasses
     * @since 2.0.14
     */
    protected function defaultConditionClasses()
    {
        return [
            'NOT' => 'yii\db\conditions\NotCondition',
            'AND' => 'yii\db\conditions\AndCondition',
            'OR' => 'yii\db\conditions\OrCondition',
            'BETWEEN' => 'yii\db\conditions\BetweenCondition',
            'NOT BETWEEN' => 'yii\db\conditions\BetweenCondition',
            'IN' => 'yii\db\conditions\InCondition',
            'NOT IN' => 'yii\db\conditions\InCondition',
            'LIKE' => 'yii\db\conditions\LikeCondition',
            'NOT LIKE' => 'yii\db\conditions\LikeCondition',
            'OR LIKE' => 'yii\db\conditions\LikeCondition',
            'OR NOT LIKE' => 'yii\db\conditions\LikeCondition',
            'EXISTS' => 'yii\db\conditions\ExistsCondition',
            'NOT EXISTS' => 'yii\db\conditions\ExistsCondition',
        ];
    }

    /**
     * Contains array of default expression builders. Extend this method and override it, if you want to change
     * default expression builders for this query builder. See [[expressionBuilders]] docs for details.
     *
     * @return array
     * @see expressionBuilders
     * @since 2.0.14
     */
    protected function defaultExpressionBuilders()
    {
        return [
            'yii\db\Query' => 'yii\db\QueryExpressionBuilder',
            'yii\db\PdoValue' => 'yii\db\PdoValueBuilder',
            'yii\db\Expression' => 'yii\db\ExpressionBuilder',
            'yii\db\conditions\ConjunctionCondition' => 'yii\db\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\NotCondition' => 'yii\db\conditions\NotConditionBuilder',
            'yii\db\conditions\AndCondition' => 'yii\db\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\OrCondition' => 'yii\db\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\BetweenCondition' => 'yii\db\conditions\BetweenConditionBuilder',
            'yii\db\conditions\InCondition' => 'yii\db\conditions\InConditionBuilder',
            'yii\db\conditions\LikeCondition' => 'yii\db\conditions\LikeConditionBuilder',
            'yii\db\conditions\ExistsCondition' => 'yii\db\conditions\ExistsConditionBuilder',
            'yii\db\conditions\SimpleCondition' => 'yii\db\conditions\SimpleConditionBuilder',
            'yii\db\conditions\HashCondition' => 'yii\db\conditions\HashConditionBuilder',
            'yii\db\conditions\BetweenColumnsCondition' => 'yii\db\conditions\BetweenColumnsConditionBuilder',
        ];
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
     *
     * @return string the SQL statement for creating the sequence.
     */
    public function createSequence(string $sequence, int $start = 1, int $increment = 1, array $options = []): string
    {
        throw new NotSupportedException($this->db->getDriverName() . ' does not support creating sequences.');
    }

    /**
     * Creates an `DROP SEQUENCE` SQL statement.
     *
     * @param string $sequence the name of the sequence.
     * The sequence name will be generated based on the suffix '_SEQ' if it is not provided.
     * For example sequence name for the table `customer` will be `customer_SEQ`.
     * The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping the sequence.
     */
    public function dropSequence(string $sequence): string
    {
        $sequence = SqlHelper::addSuffix($sequence, '_SEQ');

        return <<<SQL
        DROP SEQUENCE {$this->db->quoteTableName($sequence)}
        SQL;
    }

    /**
     * Setter for [[expressionBuilders]] property.
     *
     * @param string[] $builders array of builders that should be merged with the pre-defined ones
     * in [[expressionBuilders]] property.
     * @since 2.0.14
     * @see expressionBuilders
     */
    public function setExpressionBuilders($builders)
    {
        $this->expressionBuilders = array_merge($this->expressionBuilders, $builders);
    }

    /**
     * Setter for [[conditionClasses]] property.
     *
     * @param string[] $classes map of condition aliases to condition classes. For example:
     *
     * ```php
     * ['LIKE' => yii\db\condition\LikeCondition::class]
     * ```
     *
     * @since 2.0.14.2
     * @see conditionClasses
     */
    public function setConditionClasses($classes)
    {
        $this->conditionClasses = array_merge($this->conditionClasses, $classes);
    }

    /**
     * Generates a SELECT SQL statement from a [[Query]] object.
     *
     * @param Query $query the [[Query]] object from which the SQL statement will be generated.
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will
     * be included in the result with the additional parameters generated during the query building process.
     * @return array the generated SQL statement (the first array element) and the corresponding
     * parameters to be bound to the SQL statement (the second array element). The parameters returned
     * include those provided in `$params`.
     */
    public function build($query, $params = [])
    {
        $query = $query->prepare($this);

        $params = empty($params) ? $query->params : array_merge($params, $query->params);

        $clauses = [
            $this->buildSelect($query->select, $params, $query->distinct, $query->selectOption),
            $this->buildFrom($query->from, $params),
            $this->buildJoin($query->join, $params),
            $this->buildWhere($query->where, $params),
            $this->buildGroupBy($query->groupBy),
            $this->buildHaving($query->having, $params),
        ];

        $sql = implode($this->separator, array_filter($clauses));
        $sql = $this->buildOrderByAndLimit($sql, $query->orderBy, $query->limit, $query->offset);

        if (!empty($query->orderBy)) {
            foreach ($query->orderBy as $expression) {
                if ($expression instanceof ExpressionInterface) {
                    $this->buildExpression($expression, $params);
                }
            }
        }
        if (!empty($query->groupBy)) {
            foreach ($query->groupBy as $expression) {
                if ($expression instanceof ExpressionInterface) {
                    $this->buildExpression($expression, $params);
                }
            }
        }

        $union = $this->buildUnion($query->union, $params);
        if ($union !== '') {
            $sql = "($sql){$this->separator}$union";
        }

        $with = $this->buildWithQueries($query->withQueries, $params);
        if ($with !== '') {
            $sql = "$with{$this->separator}$sql";
        }

        return [$sql, $params];
    }

    /**
     * Builds given $expression
     *
     * @param ExpressionInterface $expression the expression to be built
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will
     * be included in the result with the additional parameters generated during the expression building process.
     * @return string the SQL statement that will not be neither quoted nor encoded before passing to DBMS
     * @throws InvalidArgumentException when $expression building is not supported by this QueryBuilder.
     * @see ExpressionBuilderInterface
     * @see expressionBuilders
     * @since 2.0.14
     * @see ExpressionInterface
     */
    public function buildExpression(ExpressionInterface $expression, &$params = [])
    {
        $builder = $this->getExpressionBuilder($expression);

        return $builder->build($expression, $params);
    }

    /**
     * Gets object of [[ExpressionBuilderInterface]] that is suitable for $expression.
     * Uses [[expressionBuilders]] array to find a suitable builder class.
     *
     * @param ExpressionInterface $expression
     * @return ExpressionBuilderInterface
     * @throws InvalidArgumentException when $expression building is not supported by this QueryBuilder.
     * @since 2.0.14
     * @see expressionBuilders
     */
    public function getExpressionBuilder(ExpressionInterface $expression)
    {
        $className = get_class($expression);

        if (!isset($this->expressionBuilders[$className])) {
            foreach (array_reverse($this->expressionBuilders) as $expressionClass => $builderClass) {
                if (is_subclass_of($expression, $expressionClass)) {
                    $this->expressionBuilders[$className] = $builderClass;
                    break;
                }
            }

            if (!isset($this->expressionBuilders[$className])) {
                throw new InvalidArgumentException('Expression of class ' . $className . ' can not be built in ' . get_class($this));
            }
        }

        if ($this->expressionBuilders[$className] === __CLASS__) {
            return $this;
        }

        if (!is_object($this->expressionBuilders[$className])) {
            $this->expressionBuilders[$className] = new $this->expressionBuilders[$className]($this);
        }

        return $this->expressionBuilders[$className];
    }

    /**
     * Creates an INSERT SQL statement.
     *
     * For example,
     * ```php
     * $sql = $queryBuilder->insert(
     *     'user',
     *     [
     *         'name' => 'Sam',
     *         'age' => 30,
     *     ],
     *     $params,
     * );
     * ```
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|QueryInterface $columns the column data (name => value) to be inserted into the table or instance
     * of [[yii\db\Query|QueryInterface]] to perform INSERT INTO ... SELECT SQL statement.
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @return string the INSERT SQL.
     */
    public function insert(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        /**
         * @psalm-var string[] $names
         * @psalm-var string[] $placeholders
         * @psalm-var string $values
         */
        [$names, $placeholders, $values, $params] = $this->prepareInsertValues(
            $this->db->getTableSchema($table),
            $columns,
            $params,
        );

        return 'INSERT INTO '
            . $this->db->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : $values);
    }

    /**
     * Prepares a `VALUES` part for an `INSERT` SQL statement.
     *
     * @param TableSchema|null $tableSchema the table schema.
     * @param array|QueryInterface $columns the column data (name => value) to be inserted into the table or instance
     * of [[yii\db\Query|QueryInterface]] to perform INSERT INTO ... SELECT SQL statement.
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @return array array of column names, placeholders, values and params.
     */
    protected function prepareInsertValues(
        TableSchema|null $tableSchema,
        array|QueryInterface $columns,
        array $params = []
    ): array {
        $columnSchemas = $tableSchema !== null ? $tableSchema->columns : [];
        $names = [];
        $placeholders = [];
        $values = ' DEFAULT VALUES';

        if ($columns instanceof QueryInterface) {
            [$names, $values, $params] = $this->prepareInsertSelectSubQuery($columns, $params);
        } else {
            $columns = $this->normalizeColumnNames($tableSchema->name, $columns);

            /**
             * @psalm-var mixed $value
             * @psalm-var array<string, mixed> $columns
             */
            foreach ($columns as $name => $value) {
                $names[] = $this->db->quoteColumnName($name);

                /** @var mixed $value */
                $value = $this->getTypecastValue($value, $columnSchemas[$name] ?? null);

                if ($value instanceof ExpressionInterface) {
                    $placeholders[] = $this->buildExpression($value, $params);
                } else {
                    $placeholders[] = $this->bindParam($value, $params);
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    /**
     * Prepare select-subquery and field names for INSERT INTO ... SELECT SQL statement.
     *
     * @param Query $columns Object, which represents select query.
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will
     * be included in the result with the additional parameters generated during the query building process.
     * @return array array of column names, values and params.
     *
     * @throws InvalidArgumentException if query's select does not contain named parameters only.
     */
    protected function prepareInsertSelectSubQuery(Query $columns, array $params = [])
    {
        if (!is_array($columns->select) || empty($columns->select) || in_array('*', $columns->select)) {
            throw new InvalidArgumentException('Expected select query object with enumerated (named) parameters');
        }

        [$values, $params] = $this->build($columns, $params);

        $names = [];
        $values = ' ' . $values;

        /** @psalm-var string[] $select */
        $select = $columns->select;

        foreach ($select as $title => $field) {
            if (is_string($title)) {
                $names[] = $this->db->quoteColumnName($title);
            } else {
                if ($field instanceof ExpressionInterface) {
                    $field = $this->buildExpression($field, $params);
                }

                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $field, $matches)) {
                    $names[] = $this->db->quoteColumnName($matches[2]);
                } else {
                    $names[] = $this->db->quoteColumnName($field);
                }
            }
        }

        return [$names, $values, $params];
    }

    /**
     * Generates a batch INSERT SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->batchInsert(
     *     'user',
     *     ['name', 'age'],
     *     [
     *         ['Tom', 30],
     *         ['Jane', 20],
     *         ['Linda', 25],
     *     ],
     * );
     * ```
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * The method will properly escape the column names, and quote the values to be inserted.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|Generator $columns the column names
     * @param iterable $rows the rows to be batch inserted into the table
     * @param array $params the binding parameters.
     *
     * @return string the batch INSERT SQL statement.
     */
    public function batchInsert(string $table, array $columns, iterable|Generator $rows, array &$params = []): string
    {
        if (empty($rows)) {
            return '';
        }

        $table = $this->db->quoteSql($table);

        [$columns, $values] = $this->prepareBatchInsertColumnsAndValues($table, $columns, $rows, $params);

        if (empty($values)) {
            return '';
        }

        return $this->buildBatchInsertSql($table, $columns, $values);
    }

    /**
     * Creates an SQL statement to insert rows into a database table if they do not already exist (matching unique
     * constraints), or update them if they do.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->upsert(
     *     'pages',
     *     [
     *         'name' => 'Front page',
     *         'url' => 'https://example.com/', // url is unique
     *         'visits' => 0,
     *     ],
     *     [
     *         'visits' => new \yii\db\Expression('visits + 1'),
     *     ],
     *     $params,
     * );
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|QueryInterface $insertColumns the column data (name => value) to be inserted into the table or
     * instance of [[QueryInterface]] to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exists.
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @return string the resulting SQL.
     *
     * @throws NotSupportedException if this is not supported by the underlying DBMS.
     */
    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array &$params,
    ): string {
        throw new NotSupportedException($this->db->getDriverName() . ' does not support upsert statements.');
    }

    /**
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|QueryInterface $insertColumns the column data (name => value) to be inserted into the table or
     * instance of [[QueryInterface]] to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist.
     * @param Constraint[] $constraints this parameter recieves a matched constraint list.
     * The constraints will be unique by their column names.
     *
     * @return array array of unique column names, insert column names and update column names.
     */
    protected function prepareUpsertColumns(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array &$constraints = [],
    ): array {
        $insertNames = [];

        if (!$insertColumns instanceof QueryInterface) {
            $insertColumns = $this->normalizeColumnNames($table, $insertColumns);
        }

        if (is_array($updateColumns)) {
            $updateColumns = $this->normalizeColumnNames($table, $updateColumns);
        }

        if ($insertColumns instanceof QueryInterface) {
            /** @psalm-var list<string> $insertNames */
            [$insertNames] = $this->prepareInsertSelectSubQuery($insertColumns);
        } else {
            /** @psalm-var array<string, string> $insertColumns */
            foreach ($insertColumns as $key => $_value) {
                $insertNames[] = $this->db->quoteColumnName($key);
            }
        }

        /** @psalm-var string[] $uniqueNames */
        $uniqueNames = $this->getTableUniqueColumnNames($table, $insertNames, $constraints);

        foreach ($uniqueNames as $key => $name) {
            $insertNames[$key] = $this->db->quoteColumnName($name);
        }

        if ($updateColumns !== true) {
            return [$uniqueNames, $insertNames, null];
        }

        return [$uniqueNames, $insertNames, array_diff($insertNames, $uniqueNames)];
    }

    /**
     * Returns all column names belonging to constraints enforcing uniqueness (`PRIMARY KEY`, `UNIQUE INDEX`, etc.)
     * for the named table removing constraints which did not cover the specified column list.
     * The column list will be unique by column names.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param string[] $columns source column list.
     * @param Constraint[] $constraints this parameter optionally recieves a matched constraint list.
     * The constraints will be unique by their column names.
     * @return string[] column list.
     */
    private function getTableUniqueColumnNames($name, $columns, &$constraints = [])
    {
        $schema = $this->db->getSchema();

        if (!$schema instanceof ConstraintFinderInterface) {
            return [];
        }

        $constraints = [];
        $primaryKey = $schema->getTablePrimaryKey($name);

        if ($primaryKey !== null) {
            $constraints[] = $primaryKey;
        }

        foreach ($schema->getTableIndexes($name) as $constraint) {
            if ($constraint->isUnique) {
                $constraints[] = $constraint;
            }
        }

        $constraints = array_merge($constraints, $schema->getTableUniques($name));

        // Remove duplicates
        $constraints = array_combine(array_map(function (Constraint $constraint) {
            $columns = $constraint->columnNames;
            sort($columns, SORT_STRING);
            return json_encode($columns);
        }, $constraints), $constraints);

        $columnNames = [];

        // Remove all constraints which do not cover the specified column list
        $constraints = array_values(
            array_filter(
                $constraints,
                static function (Constraint $constraint) use ($schema, $columns, &$columnNames) {
                    $getColumnNames = $constraint->columnNames ?? [];
                    $constraintColumnNames = [];

                    if (is_array($getColumnNames)) {
                        foreach ($getColumnNames as $columnName) {
                            if ($columnName !== null) {
                                $constraintColumnNames[] = $schema->quoteColumnName($columnName);
                            }
                        }
                    }

                    $result = !array_diff($constraintColumnNames, $columns);

                    if ($result) {
                        $columnNames = array_merge((array) $columnNames, $constraintColumnNames);
                    }

                    return $result;
                }
            )
        );

        return array_unique($columnNames);
    }

    /**
     * Creates an UPDATE SQL statement.
     *
     * For example,
     *
     * ```php
     * $params = [];
     * $sql = $queryBuilder->update('user', ['status' => 1], 'age > 30', $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param array|string $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the binding parameters that will be modified by this method
     * so that they can be bound to the DB command later.
     * @return string the UPDATE SQL
     */
    public function update($table, $columns, $condition, &$params)
    {
        list($lines, $params) = $this->prepareUpdateSets($table, $columns, $params);
        $sql = 'UPDATE ' . $this->db->quoteTableName($table) . ' SET ' . implode(', ', $lines);
        $where = $this->buildWhere($condition, $params);
        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /**
     * Prepares a `SET` parts for an `UPDATE` SQL statement.
     *
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param array $params the binding parameters that will be modified by this method
     * so that they can be bound to the DB command later.
     *
     * @return array an array `SET` parts for an `UPDATE` SQL statement (the first array element) and params
     * (the second array element).
     */
    protected function prepareUpdateSets(string $table, array $columns, array $params = []): array
    {
        $sets = [];

        $tableSchema = $this->db->getTableSchema($table);
        $columnSchemas = $tableSchema !== null ? $tableSchema->columns : [];

        $columns = $this->normalizeColumnNames($table, $columns);

        /**
         * @psalm-var array<string, mixed> $columns
         * @psalm-var mixed $value
         */
        foreach ($columns as $name => $value) {
            /** @psalm-var mixed $value */
            $value = isset($columnSchemas[$name]) ? $columnSchemas[$name]->dbTypecast($value) : $value;

            if ($value instanceof ExpressionInterface) {
                $placeholder = $this->buildExpression($value, $params);
            } else {
                $placeholder = $this->bindParam($value, $params);
            }

            $sets[] = $this->db->quoteColumnName($name) . '=' . $placeholder;
        }

        return [$sets, $params];
    }

    /**
     * Creates a DELETE SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->delete('user', 'status = 0');
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table where the data will be deleted from.
     * @param array|string $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the binding parameters that will be modified by this method
     * so that they can be bound to the DB command later.
     * @return string the DELETE SQL
     */
    public function delete($table, $condition, &$params)
    {
        $sql = 'DELETE FROM ' . $this->db->quoteTableName($table);
        $where = $this->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /**
     * Builds a SQL statement for creating a new DB table.
     *
     * The columns in the new table should be specified as name-definition pairs (e.g. 'name' => 'string'),
     * where name stands for a column name which will be properly quoted by the method, and definition
     * stands for the column type which must contain an abstract DB type.
     * The [[getColumnType()]] method will be invoked to convert any abstract type into a physical one.
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly
     * inserted into the generated SQL.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->createTable('user', [
     *  'id' => 'pk',
     *  'name' => 'string',
     *  'age' => 'integer',
     *  'column_name double precision null default null', # definition only example
     * ]);
     * ```
     *
     * @param string $table the name of the table to be created. The name will be properly quoted by the method.
     * @param array $columns the columns (name => definition) in the new table.
     * @param string|null $options additional SQL fragment that will be appended to the generated SQL.
     * @return string the SQL statement for creating a new DB table.
     */
    public function createTable($table, $columns, $options = null)
    {
        $cols = [];
        foreach ($columns as $name => $type) {
            if (is_string($name)) {
                $cols[] = "\t" . $this->db->quoteColumnName($name) . ' ' . $this->getColumnType($type);
            } else {
                $cols[] = "\t" . $type;
            }
        }
        $sql = 'CREATE TABLE ' . $this->db->quoteTableName($table) . " (\n" . implode(",\n", $cols) . "\n)";

        return $options === null ? $sql : $sql . ' ' . $options;
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     * @param string $oldName the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable($oldName, $newName)
    {
        return 'RENAME TABLE ' . $this->db->quoteTableName($oldName) . ' TO ' . $this->db->quoteTableName($newName);
    }

    /**
     * Builds a SQL statement for dropping a DB table.
     * @param string $table the table to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping a DB table.
     */
    public function dropTable($table)
    {
        return 'DROP TABLE ' . $this->db->quoteTableName($table);
    }

    /**
     * Builds a SQL statement for adding a primary key constraint to an existing table.
     * @param string $name the name of the primary key constraint.
     * @param string $table the table that the primary key constraint will be added to.
     * @param string|array $columns comma separated string or array of columns that the primary key will consist of.
     * @return string the SQL statement for adding a primary key constraint to an existing table.
     */
    public function addPrimaryKey($name, $table, $columns)
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = $this->db->quoteColumnName($col);
        }

        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' ADD CONSTRAINT '
            . $this->db->quoteColumnName($name) . ' PRIMARY KEY ('
            . implode(', ', $columns) . ')';
    }

    /**
     * Builds a SQL statement for removing a primary key constraint to an existing table.
     * @param string $name the name of the primary key constraint to be removed.
     * @param string $table the table that the primary key constraint will be removed from.
     * @return string the SQL statement for removing a primary key constraint from an existing table.
     */
    public function dropPrimaryKey($name, $table)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Builds a SQL statement for truncating a DB table.
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     * @return string the SQL statement for truncating a DB table.
     */
    public function truncateTable($table)
    {
        return 'TRUNCATE TABLE ' . $this->db->quoteTableName($table);
    }

    /**
     * Builds a SQL statement for adding a new DB column.
     * @param string $table the table that the new column will be added to. The table name will be properly quoted by the method.
     * @param string $column the name of the new column. The name will be properly quoted by the method.
     * @param string $type the column type. The [[getColumnType()]] method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     * @return string the SQL statement for adding a new column.
     */
    public function addColumn($table, $column, $type)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' ADD ' . $this->db->quoteColumnName($column) . ' '
            . $this->getColumnType($type);
    }

    /**
     * Builds a SQL statement for dropping a DB column.
     * @param string $table the table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column the name of the column to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping a DB column.
     */
    public function dropColumn($table, $column)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP COLUMN ' . $this->db->quoteColumnName($column);
    }

    /**
     * Builds a SQL statement for renaming a column.
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB column.
     */
    public function renameColumn($table, $oldName, $newName)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' RENAME COLUMN ' . $this->db->quoteColumnName($oldName)
            . ' TO ' . $this->db->quoteColumnName($newName);
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The [[getColumnType()]] method will be invoked to convert abstract
     * column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept
     * in the generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null'
     * will become 'varchar(255) not null'.
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn($table, $column, $type)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' CHANGE '
            . $this->db->quoteColumnName($column) . ' '
            . $this->db->quoteColumnName($column) . ' '
            . $this->getColumnType($type);
    }

    /**
     * Builds a SQL statement for adding a foreign key constraint to an existing table.
     * The method will properly quote the table and column names.
     * @param string $name the name of the foreign key constraint.
     * @param string $table the table that the foreign key constraint will be added to.
     * @param string|array $columns the name of the column to that the constraint will be added on.
     * If there are multiple columns, separate them with commas or use an array to represent them.
     * @param string $refTable the table that the foreign key references to.
     * @param string|array $refColumns the name of the column that the foreign key references to.
     * If there are multiple columns, separate them with commas or use an array to represent them.
     * @param string|null $delete the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     * @param string|null $update the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     * @return string the SQL statement for adding a foreign key constraint to an existing table.
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
            $sql .= ' ON UPDATE ' . $update;
        }

        return $sql;
    }

    /**
     * Builds a SQL statement for dropping a foreign key constraint.
     * @param string $name the name of the foreign key constraint to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose foreign is to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping a foreign key constraint.
     */
    public function dropForeignKey($name, $table)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Builds a SQL statement for creating a new index.
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by the method.
     * @param string|array $columns the column(s) that should be included in the index. If there are multiple columns,
     * separate them with commas or use an array to represent them. Each column name will be properly quoted
     * by the method, unless a parenthesis is found in the name.
     * @param bool $unique whether to add UNIQUE constraint on the created index.
     * @return string the SQL statement for creating a new index.
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ')
            . $this->db->quoteTableName($name) . ' ON '
            . $this->db->quoteTableName($table)
            . ' (' . $this->buildColumns($columns) . ')';
    }

    /**
     * Builds a SQL statement for dropping an index.
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping an index.
     */
    public function dropIndex($name, $table)
    {
        return 'DROP INDEX ' . $this->db->quoteTableName($name) . ' ON ' . $this->db->quoteTableName($table);
    }

    /**
     * Creates a SQL command for adding an unique constraint to an existing table.
     * @param string $name the name of the unique constraint.
     * The name will be properly quoted by the method.
     * @param string $table the table that the unique constraint will be added to.
     * The name will be properly quoted by the method.
     * @param string|array $columns the name of the column to that the constraint will be added on.
     * If there are multiple columns, separate them with commas.
     * The name will be properly quoted by the method.
     * @return string the SQL statement for adding an unique constraint to an existing table.
     * @since 2.0.13
     */
    public function addUnique($name, $table, $columns)
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach ($columns as $i => $col) {
            $columns[$i] = $this->db->quoteColumnName($col);
        }

        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' ADD CONSTRAINT '
            . $this->db->quoteColumnName($name) . ' UNIQUE ('
            . implode(', ', $columns) . ')';
    }

    /**
     * Creates a SQL command for dropping an unique constraint.
     * @param string $name the name of the unique constraint to be dropped.
     * The name will be properly quoted by the method.
     * @param string $table the table whose unique constraint is to be dropped.
     * The name will be properly quoted by the method.
     * @return string the SQL statement for dropping an unique constraint.
     * @since 2.0.13
     */
    public function dropUnique($name, $table)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Creates a SQL command for adding a check constraint to an existing table.
     * @param string $name the name of the check constraint.
     * The name will be properly quoted by the method.
     * @param string $table the table that the check constraint will be added to.
     * The name will be properly quoted by the method.
     * @param string $expression the SQL of the `CHECK` constraint.
     * @return string the SQL statement for adding a check constraint to an existing table.
     * @since 2.0.13
     */
    public function addCheck($name, $table, $expression)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' ADD CONSTRAINT '
            . $this->db->quoteColumnName($name) . ' CHECK (' . $this->db->quoteSql($expression) . ')';
    }

    /**
     * Creates a SQL command for dropping a check constraint.
     * @param string $name the name of the check constraint to be dropped.
     * The name will be properly quoted by the method.
     * @param string $table the table whose check constraint is to be dropped.
     * The name will be properly quoted by the method.
     * @return string the SQL statement for dropping a check constraint.
     * @since 2.0.13
     */
    public function dropCheck($name, $table)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Creates a SQL command for adding a default value constraint to an existing table.
     * @param string $name the name of the default value constraint.
     * The name will be properly quoted by the method.
     * @param string $table the table that the default value constraint will be added to.
     * The name will be properly quoted by the method.
     * @param string $column the name of the column to that the constraint will be added on.
     * The name will be properly quoted by the method.
     * @param mixed $value default value.
     * @return string the SQL statement for adding a default value constraint to an existing table.
     * @throws NotSupportedException if this is not supported by the underlying DBMS.
     * @since 2.0.13
     */
    public function addDefaultValue($name, $table, $column, $value)
    {
        throw new NotSupportedException($this->db->getDriverName() . ' does not support adding default value constraints.');
    }

    /**
     * Creates a SQL command for dropping a default value constraint.
     * @param string $name the name of the default value constraint to be dropped.
     * The name will be properly quoted by the method.
     * @param string $table the table whose default value constraint is to be dropped.
     * The name will be properly quoted by the method.
     * @return string the SQL statement for dropping a default value constraint.
     * @throws NotSupportedException if this is not supported by the underlying DBMS.
     * @since 2.0.13
     */
    public function dropDefaultValue($name, $table)
    {
        throw new NotSupportedException($this->db->getDriverName() . ' does not support dropping default value constraints.');
    }

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     * @param bool $check whether to turn on or off the integrity check.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * @param string $table the table name. Defaults to empty string, meaning that no table will be changed.
     * @return string the SQL statement for checking integrity
     * @throws NotSupportedException if this is not supported by the underlying DBMS
     */
    public function checkIntegrity($check = true, $schema = '', $table = '')
    {
        throw new NotSupportedException($this->db->getDriverName() . ' does not support enabling/disabling integrity check.');
    }

    /**
     * Builds a SQL command for adding comment to column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     * @return string the SQL statement for adding comment on column
     * @since 2.0.8
     */
    public function addCommentOnColumn($table, $column, $comment)
    {
        return 'COMMENT ON COLUMN ' . $this->db->quoteTableName($table) . '.' . $this->db->quoteColumnName($column) . ' IS ' . $this->db->quoteValue($comment);
    }

    /**
     * Builds a SQL command for adding comment to table.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     * @return string the SQL statement for adding comment on table
     * @since 2.0.8
     */
    public function addCommentOnTable($table, $comment)
    {
        return 'COMMENT ON TABLE ' . $this->db->quoteTableName($table) . ' IS ' . $this->db->quoteValue($comment);
    }

    /**
     * Builds a SQL command for adding comment to column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the method.
     * @return string the SQL statement for adding comment on column
     * @since 2.0.8
     */
    public function dropCommentFromColumn($table, $column)
    {
        return 'COMMENT ON COLUMN ' . $this->db->quoteTableName($table) . '.' . $this->db->quoteColumnName($column) . ' IS NULL';
    }

    /**
     * Builds a SQL command for adding comment to table.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the method.
     * @return string the SQL statement for adding comment on column
     * @since 2.0.8
     */
    public function dropCommentFromTable($table)
    {
        return 'COMMENT ON TABLE ' . $this->db->quoteTableName($table) . ' IS NULL';
    }

    /**
     * Creates a SQL View.
     *
     * @param string $viewName the name of the view to be created.
     * @param string|Query $subQuery the select statement which defines the view.
     * This can be either a string or a [[Query]] object.
     * @return string the `CREATE VIEW` SQL statement.
     * @since 2.0.14
     */
    public function createView($viewName, $subQuery)
    {
        if ($subQuery instanceof Query) {
            list($rawQuery, $params) = $this->build($subQuery);
            array_walk(
                $params,
                function (&$param) {
                    $param = $this->db->quoteValue($param);
                }
            );
            $subQuery = strtr($rawQuery, $params);
        }

        return 'CREATE VIEW ' . $this->db->quoteTableName($viewName) . ' AS ' . $subQuery;
    }

    /**
     * Drops a SQL View.
     *
     * @param string $viewName the name of the view to be dropped.
     * @return string the `DROP VIEW` SQL statement.
     * @since 2.0.14
     */
    public function dropView($viewName)
    {
        return 'DROP VIEW ' . $this->db->quoteTableName($viewName);
    }

    /**
     * Converts an abstract column type into a physical column type.
     *
     * The conversion is done using the type map specified in [[typeMap]].
     * The following abstract column types are supported (using MySQL as an example to explain the corresponding
     * physical types):
     *
     * - `pk`: an auto-incremental primary key type, will be converted into "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY"
     * - `bigpk`: an auto-incremental primary key type, will be converted into "bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY"
     * - `upk`: an unsigned auto-incremental primary key type, will be converted into "int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
     * - `char`: char type, will be converted into "char(1)"
     * - `string`: string type, will be converted into "varchar(255)"
     * - `text`: a long string type, will be converted into "text"
     * - `smallint`: a small integer type, will be converted into "smallint(6)"
     * - `integer`: integer type, will be converted into "int(11)"
     * - `bigint`: a big integer type, will be converted into "bigint(20)"
     * - `boolean`: boolean type, will be converted into "tinyint(1)"
     * - `float``: float number type, will be converted into "float"
     * - `decimal`: decimal number type, will be converted into "decimal"
     * - `datetime`: datetime type, will be converted into "datetime"
     * - `timestamp`: timestamp type, will be converted into "timestamp"
     * - `time`: time type, will be converted into "time"
     * - `date`: date type, will be converted into "date"
     * - `money`: money type, will be converted into "decimal(19,4)"
     * - `binary`: binary data type, will be converted into "blob"
     *
     * If the abstract type contains two or more parts separated by spaces (e.g. "string NOT NULL"), then only
     * the first part will be converted, and the rest of the parts will be appended to the converted result.
     * For example, 'string NOT NULL' is converted to 'varchar(255) NOT NULL'.
     *
     * For some of the abstract types you can also specify a length or precision constraint
     * by appending it in round brackets directly to the type.
     * For example `string(32)` will be converted into "varchar(32)" on a MySQL database.
     * If the underlying DBMS does not support these kind of constraints for a type it will
     * be ignored.
     *
     * If a type cannot be found in [[typeMap]], it will be returned without any change.
     * @param string|ColumnSchemaBuilder $type abstract column type
     * @return string physical column type.
     */
    public function getColumnType($type)
    {
        if ($type instanceof ColumnSchemaBuilder) {
            $type = $type->__toString();
        }

        if (isset($this->typeMap[$type])) {
            return $this->typeMap[$type];
        } elseif (preg_match('/^(\w+)\((.+?)\)(.*)$/', $type, $matches)) {
            if (isset($this->typeMap[$matches[1]])) {
                return preg_replace('/\(.+\)/', '(' . $matches[2] . ')', $this->typeMap[$matches[1]]) . $matches[3];
            }
        } elseif (preg_match('/^(\w+)\s+/', $type, $matches)) {
            if (isset($this->typeMap[$matches[1]])) {
                return preg_replace('/^\w+/', $this->typeMap[$matches[1]], $type);
            }
        }

        return $type;
    }

    /**
     * @param array $columns
     * @param array $params the binding parameters to be populated
     * @param bool $distinct
     * @param string|null $selectOption
     * @return string the SELECT clause built from [[Query::$select]].
     */
    public function buildSelect($columns, &$params, $distinct = false, $selectOption = null)
    {
        $select = $distinct ? 'SELECT DISTINCT' : 'SELECT';
        if ($selectOption !== null) {
            $select .= ' ' . $selectOption;
        }

        if (empty($columns)) {
            return $select . ' *';
        }

        foreach ($columns as $i => $column) {
            if ($column instanceof ExpressionInterface) {
                if (is_int($i)) {
                    $columns[$i] = $this->buildExpression($column, $params);
                } else {
                    $columns[$i] = $this->buildExpression($column, $params) . ' AS ' . $this->db->quoteColumnName($i);
                }
            } elseif ($column instanceof Query) {
                list($sql, $params) = $this->build($column, $params);
                $columns[$i] = "($sql) AS " . $this->db->quoteColumnName($i);
            } elseif (is_string($i) && $i !== $column) {
                if (strpos($column, '(') === false) {
                    $column = $this->db->quoteColumnName($column);
                }
                $columns[$i] = "$column AS " . $this->db->quoteColumnName($i);
            } elseif (strpos($column, '(') === false) {
                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $column, $matches)) {
                    $columns[$i] = $this->db->quoteColumnName($matches[1]) . ' AS ' . $this->db->quoteColumnName($matches[2]);
                } else {
                    $columns[$i] = $this->db->quoteColumnName($column);
                }
            }
        }

        return $select . ' ' . implode(', ', $columns);
    }

    /**
     * @param array $tables
     * @param array $params the binding parameters to be populated
     * @return string the FROM clause built from [[Query::$from]].
     */
    public function buildFrom($tables, &$params)
    {
        if (empty($tables)) {
            return '';
        }

        $tables = $this->quoteTableNames($tables, $params);

        return 'FROM ' . implode(', ', $tables);
    }

    /**
     * @param array $joins
     * @param array $params the binding parameters to be populated
     * @return string the JOIN clause built from [[Query::$join]].
     * @throws Exception if the $joins parameter is not in proper format
     */
    public function buildJoin($joins, &$params)
    {
        if (empty($joins)) {
            return '';
        }

        foreach ($joins as $i => $join) {
            if (!is_array($join) || !isset($join[0], $join[1])) {
                throw new Exception('A join clause must be specified as an array of join type, join table, and optionally join condition.');
            }
            // 0:join type, 1:join table, 2:on-condition (optional)
            list($joinType, $table) = $join;
            $tables = $this->quoteTableNames((array)$table, $params);
            $table = reset($tables);
            $joins[$i] = "$joinType $table";
            if (isset($join[2])) {
                $condition = $this->buildCondition($join[2], $params);
                if ($condition !== '') {
                    $joins[$i] .= ' ON ' . $condition;
                }
            }
        }

        return implode($this->separator, $joins);
    }

    /**
     * Quotes table names passed.
     *
     * @param array $tables
     * @param array $params
     * @return array
     */
    private function quoteTableNames($tables, &$params)
    {
        foreach ($tables as $i => $table) {
            if ($table instanceof Query) {
                list($sql, $params) = $this->build($table, $params);
                $tables[$i] = "($sql) " . $this->db->quoteTableName((string) $i);
            } elseif (is_string($i)) {
                if (strpos($table, '(') === false) {
                    $table = $this->db->quoteTableName($table);
                }
                $tables[$i] = "$table " . $this->db->quoteTableName($i);
            } elseif (strpos((string) $table, '(') === false) {
                if ($tableWithAlias = $this->extractAlias($table)) { // with alias
                    $tables[$i] = $this->db->quoteTableName($tableWithAlias[1]) . ' ' . $this->db->quoteTableName($tableWithAlias[2]);
                } else {
                    $tables[$i] = $this->db->quoteTableName($table);
                }
            }
        }

        return $tables;
    }

    /**
     * @param string|array $condition
     * @param array $params the binding parameters to be populated
     * @return string the WHERE clause built from [[Query::$where]].
     */
    public function buildWhere($condition, &$params)
    {
        $where = $this->buildCondition($condition, $params);

        return $where === '' ? '' : 'WHERE ' . $where;
    }

    /**
     * @param array $columns
     * @return string the GROUP BY clause
     */
    public function buildGroupBy($columns)
    {
        if (empty($columns)) {
            return '';
        }
        foreach ($columns as $i => $column) {
            if ($column instanceof ExpressionInterface) {
                $columns[$i] = $this->buildExpression($column);
            } elseif (strpos($column, '(') === false) {
                $columns[$i] = $this->db->quoteColumnName($column);
            }
        }

        return 'GROUP BY ' . implode(', ', $columns);
    }

    /**
     * @param string|array $condition
     * @param array $params the binding parameters to be populated
     * @return string the HAVING clause built from [[Query::$having]].
     */
    public function buildHaving($condition, &$params)
    {
        $having = $this->buildCondition($condition, $params);

        return $having === '' ? '' : 'HAVING ' . $having;
    }

    /**
     * Builds the ORDER BY and LIMIT/OFFSET clauses and appends them to the given SQL.
     *
     * @param string $sql the existing SQL (without ORDER BY/LIMIT/OFFSET)
     * @param array|null $orderBy the order by columns. See [[Query::orderBy]] for more details on how to specify this
     * parameter.
     * @param ExpressionInterface|int|null $limit the limit number. See [[Query::limit]] for more details.
     * @param ExpressionInterface|int|null $offset the offset number. See [[Query::offset]] for more details.
     *
     * @return string the SQL completed with ORDER BY/LIMIT/OFFSET (if any).
     */
    public function buildOrderByAndLimit(
        string $sql,
        array|null $orderBy,
        ExpressionInterface|int|null $limit,
        ExpressionInterface|int|null $offset,
    ): string {
        $orderBy = $this->buildOrderBy($orderBy);

        if ($orderBy !== '') {
            $sql .= $this->separator . $orderBy;
        }

        $limit = $this->buildLimit($limit, $offset);

        if ($limit !== '') {
            $sql .= $this->separator . $limit;
        }

        return $sql;
    }

    /**
     * @param array $columns
     * @return string the ORDER BY clause built from [[Query::$orderBy]].
     */
    public function buildOrderBy($columns)
    {
        if (empty($columns)) {
            return '';
        }
        $orders = [];
        foreach ($columns as $name => $direction) {
            if ($direction instanceof ExpressionInterface) {
                $orders[] = $this->buildExpression($direction);
            } else {
                $orders[] = $this->db->quoteColumnName($name) . ($direction === SORT_DESC ? ' DESC' : '');
            }
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return string the LIMIT and OFFSET clauses
     */
    public function buildLimit($limit, $offset)
    {
        $sql = '';
        if ($this->hasLimit($limit)) {
            $sql = 'LIMIT ' . $limit;
        }
        if ($this->hasOffset($offset)) {
            $sql .= ' OFFSET ' . $offset;
        }

        return ltrim($sql);
    }

    /**
     * Checks to see if the given limit is effective.
     * @param mixed $limit the given limit
     * @return bool whether the limit is effective
     */
    protected function hasLimit($limit)
    {
        return ($limit instanceof ExpressionInterface) || ctype_digit((string)$limit);
    }

    /**
     * Checks to see if the given offset is effective.
     * @param mixed $offset the given offset
     * @return bool whether the offset is effective
     */
    protected function hasOffset($offset)
    {
        return ($offset instanceof ExpressionInterface) || ctype_digit((string)$offset) && (string)$offset !== '0';
    }

    /**
     * @param array $unions
     * @param array $params the binding parameters to be populated
     * @return string the UNION clause built from [[Query::$union]].
     */
    public function buildUnion($unions, &$params)
    {
        if (empty($unions)) {
            return '';
        }

        $result = '';

        foreach ($unions as $i => $union) {
            $query = $union['query'];
            if ($query instanceof Query) {
                list($unions[$i]['query'], $params) = $this->build($query, $params);
            }

            $result .= 'UNION ' . ($union['all'] ? 'ALL ' : '') . '( ' . $unions[$i]['query'] . ' ) ';
        }

        return trim($result);
    }

    /**
     * @param array $withs of configurations for each WITH query
     * @param array $params the binding parameters to be populated
     * @return string compiled WITH prefix of query including nested queries
     * @see Query::withQuery()
     * @since 2.0.35
     */
    public function buildWithQueries($withs, &$params)
    {
        if (empty($withs)) {
            return '';
        }

        $recursive = false;
        $result = [];

        foreach ($withs as $i => $with) {
            if ($with['recursive']) {
                $recursive = true;
            }

            $query = $with['query'];
            if ($query instanceof Query) {
                list($with['query'], $params) = $this->build($query, $params);
            }

            $result[] = $with['alias'] . ' AS (' . $with['query'] . ')';
        }

        return 'WITH ' . ($recursive ? 'RECURSIVE ' : '') . implode(', ', $result);
    }

    /**
     * Processes columns and properly quotes them if necessary.
     * It will join all columns into a string with comma as separators.
     * @param string|array $columns the columns to be processed
     * @return string the processing result
     */
    public function buildColumns($columns)
    {
        if (!is_array($columns)) {
            if (strpos($columns, '(') !== false) {
                return $columns;
            }

            $rawColumns = $columns;
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
            if ($columns === false) {
                throw new InvalidArgumentException("$rawColumns is not valid columns.");
            }
        }
        foreach ($columns as $i => $column) {
            if ($column instanceof ExpressionInterface) {
                $columns[$i] = $this->buildExpression($column);
            } elseif (strpos($column, '(') === false) {
                $columns[$i] = $this->db->quoteColumnName($column);
            }
        }

        return implode(', ', $columns);
    }

    /**
     * Parses the condition specification and generates the corresponding SQL expression.
     * @param string|array|ExpressionInterface $condition the condition specification. Please refer to [[Query::where()]]
     * on how to specify a condition.
     * @param array $params the binding parameters to be populated
     * @return string the generated SQL expression
     */
    public function buildCondition($condition, &$params)
    {
        if (is_array($condition)) {
            if (empty($condition)) {
                return '';
            }

            $condition = $this->createConditionFromArray($condition);
        }

        if ($condition instanceof ExpressionInterface) {
            return $this->buildExpression($condition, $params);
        }

        return (string)$condition;
    }

    /**
     * Transforms $condition defined in array format (as described in [[Query::where()]]
     * to instance of [[yii\db\condition\ConditionInterface|ConditionInterface]] according to
     * [[conditionClasses]] map.
     *
     * @param string|array $condition
     * @return ConditionInterface
     * @see conditionClasses
     * @since 2.0.14
     */
    public function createConditionFromArray($condition)
    {
        if (isset($condition[0])) { // operator format: operator, operand 1, operand 2, ...
            $operator = strtoupper(array_shift($condition));
            if (isset($this->conditionClasses[$operator])) {
                $className = $this->conditionClasses[$operator];
            } else {
                $className = 'yii\db\conditions\SimpleCondition';
            }
            /** @var ConditionInterface $className */
            return $className::fromArrayDefinition($operator, $condition);
        }

        // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
        return new HashCondition($condition);
    }

    /**
     * Creates a SELECT EXISTS() SQL statement.
     * @param string $rawSql the subquery in a raw form to select from.
     * @return string the SELECT EXISTS() SQL statement.
     * @since 2.0.8
     */
    public function selectExists($rawSql)
    {
        return 'SELECT EXISTS(' . $rawSql . ')';
    }

    /**
     * Helper method to add $value to $params array using [[PARAM_PREFIX]].
     *
     * @param string|null $value
     * @param array $params passed by reference
     * @return string the placeholder name in $params array
     *
     * @since 2.0.14
     */
    public function bindParam($value, &$params)
    {
        $phName = self::PARAM_PREFIX . count($params);
        $params[$phName] = $value;

        return $phName;
    }

    /**
     * Extracts table alias.
     *
     * @param string $tableName The table name.
     *
     * @return bool|array The table name and alias. False if no alias is found.
     */
    public function extractAlias(string $tableName): bool|array
    {
        return $this->db->getQuoter()->extractAlias($tableName);
    }

    /**
     * Normalizes the column names for the given table.
     *
     * @param string $table The table to save the data into.
     * @param array $columns The column data (name => value) to save into the table or instance of {@see QueryInterface}
     * to perform `INSERT INTO ... SELECT` SQL statement. Passing of {@see QueryInterface}.
     *
     * @return array The normalized column names (name => value).
     */
    protected function normalizeColumnNames(string $table, array $columns): array
    {
        /** @var string[] $columnList */
        $columnList = array_keys($columns);
        $mappedNames = $this->getNormalizeColumnNames($table, $columnList);

        /** @psalm-var array $normalizedColumns */
        $normalizedColumns = [];

        /**
         * @psalm-var string $name
         * @psalm-var mixed $value
         */
        foreach ($columns as $name => $value) {
            $mappedName = $mappedNames[$name] ?? $name;
            /** @psalm-var mixed */
            $normalizedColumns[$mappedName] = $value;
        }

        return $normalizedColumns;
    }

    /**
     * Get a map of normalized columns
     *
     * @param string $table The table to save the data into.
     * @param string[] $columns The column data (name => value) to save into the table or instance of
     * {@see QueryInterface} to perform `INSERT INTO ... SELECT` SQL statement. Passing of {@see QueryInterface}.
     *
     * @return string[] Map of normalized columns.
     */
    protected function getNormalizeColumnNames(string $table, array $columns): array
    {
        $normalizedNames = [];
        $schema = $this->db->getSchema();
        $rawTableName = $schema->getRawTableName($table);

        foreach ($columns as $name) {
            $parts = $schema->getTableNameParts($name, true);

            if (count($parts) === 2 && $schema->getRawTableName($parts[0]) === $rawTableName) {
                $normalizedName = $parts[count($parts) - 1];
            } else {
                $normalizedName = $name;
            }

            $normalizedName = $this->db->getQuoter()->ensureColumnName($normalizedName);

            $normalizedNames[$name] = $normalizedName;
        }

        return $normalizedNames;
    }

    /**
     * @return mixed The typecast value of the given column.
     */
    protected function getTypecastValue(mixed $value, ColumnSchema $columnSchema = null): mixed
    {
        if ($columnSchema) {
            return $columnSchema->dbTypecast($value);
        }

        return $value;
    }

    /**
     * Prepares column names and values for batch INSERT SQL statement.
     *
     * This method processes the input columns and rows to generate properly formatted and escaped column names and
     * values for a batch `INSERT` statement.
     *
     * @param string $table the name of the table to insert data into.
     * @param array $columns list of column names.
     * @param iterable|Generator $rows the rows of data to be inserted.
     * @param array &$params the binding parameters that will be generated for the `INSERT` statement.
     *
     * @return array an array containing two elements:
     * - The first element is an array of quoted column names.
     * - The second element is an array of value placeholders for the INSERT statement.
     */
    protected function prepareBatchInsertColumnsAndValues(
        string $table,
        array $columns,
        array|Generator $rows,
        array &$params
    ): array {
        $tableSchema = $this->db->getTableSchema($table);

        $columnSchemas = $tableSchema !== null ? $tableSchema->columns : [];

        $mappedNames = $this->getNormalizeColumnNames($table, $columns);

        $values = [];

        foreach ($rows as $row) {
            if (empty($row)) {
                continue;
            }

            $placeholders = [];

            /** @psalm-var array<array-key, array<array-key, string>> $rows */
            foreach ($row as $index => $value) {
                if (
                    isset(
                        $columns[$index],
                        $mappedNames[$columns[$index]],
                        $columnSchemas[$mappedNames[$columns[$index]]]
                    )
                ) {
                    $value = $this->getTypecastValue($value, $columnSchemas[$mappedNames[$columns[$index]]]);
                }

                $placeholders[] = match ($value instanceof ExpressionInterface) {
                    true => $this->buildExpression($value, $params),
                    default => $this->bindParam($value, $params),
                };
            }

            $values[] = '(' . implode(', ', $placeholders) . ')';
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $this->db->quoteColumnName($mappedNames[$name]);
        }

        return [$columns, $values];
    }

    /**
     * Constructs the SQL statement for a batch `INSERT` operation.
     *
     * This method is responsible for generating the SQL for inserting multiple rows into a table.
     * It quotes the table name and columns, and formats the values for the SQL statement.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column names.
     * @param array $values the rows to be inserted, formatted as an array of value strings.
     *
     * @return string the batch `INSERT` SQL statement.
     */
    protected function buildBatchInsertSql(string $table, array $columns, array $values): string
    {
        $columns = match ($columns) {
            [] => '',
            default => ' (' . implode(', ', $columns) . ')',
        };

        return 'INSERT INTO ' . $this->db->quoteTableName($table) . $columns . ' VALUES ' . implode(', ', $values);
    }
}
