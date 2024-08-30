<?php

declare(strict_types=1);

namespace yii\db\mssql;

use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\db\QueryInterface;

/**
 * QueryBuilder is the query builder for MS SQL Server databases (version 2008 and above).
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    public $typeMap = [
        Schema::TYPE_PK => 'int IDENTITY PRIMARY KEY',
        Schema::TYPE_UPK => 'int IDENTITY PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigint IDENTITY PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigint IDENTITY PRIMARY KEY',
        Schema::TYPE_CHAR => 'nchar(1)',
        Schema::TYPE_STRING => 'nvarchar(255)',
        Schema::TYPE_TEXT => 'nvarchar(max)',
        Schema::TYPE_TINYINT => 'tinyint',
        Schema::TYPE_SMALLINT => 'smallint',
        Schema::TYPE_INTEGER => 'int',
        Schema::TYPE_BIGINT => 'bigint',
        Schema::TYPE_FLOAT => 'float',
        Schema::TYPE_DOUBLE => 'float',
        Schema::TYPE_DECIMAL => 'decimal(18,0)',
        Schema::TYPE_DATETIME => 'datetime',
        Schema::TYPE_TIMESTAMP => 'datetime',
        Schema::TYPE_TIME => 'time',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'varbinary(max)',
        Schema::TYPE_BOOLEAN => 'bit',
        Schema::TYPE_MONEY => 'decimal(19,4)',
    ];

    /**
     * {@inheritdoc}
     */
    protected function defaultExpressionBuilders()
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            'yii\db\conditions\InCondition' => 'yii\db\mssql\conditions\InConditionBuilder',
            'yii\db\conditions\LikeCondition' => 'yii\db\mssql\conditions\LikeConditionBuilder',
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
        $orderBy = $this->buildOrderBy($orderBy);

        if (!$this->hasOffset($offset) && !$this->hasLimit($limit)) {
            return $orderBy === '' ? $sql : $sql . $this->separator . $orderBy;
        }

        if ($orderBy === '') {
            // ORDER BY clause is required when FETCH and OFFSET are in the SQL.
            $orderBy = 'ORDER BY (SELECT NULL)';
        }

        $sql .= $this->separator . $orderBy;

        /**
         * @link http://technet.microsoft.com/en-us/library/gg699618.aspx
         */
        $offsetString = $this->hasOffset($offset) ?
            ($offset instanceof ExpressionInterface ? $this->buildExpression($offset) : (string)$offset) : '0';

        $sql .= $this->separator . 'OFFSET ' . $offsetString . ' ROWS';

        if ($this->hasLimit($limit)) {
            $sql .= $this->separator . 'FETCH NEXT ' .
                ($limit instanceof ExpressionInterface
                    ? $this->buildExpression($limit) : (string) $limit) . ' ROWS ONLY';
        }

        return $sql;
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     * @param string $oldName the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable($oldName, $newName)
    {
        return 'sp_rename ' . $this->db->quoteTableName($oldName) . ', ' . $this->db->quoteTableName($newName);
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
        $table = $this->db->quoteTableName($table);
        $oldName = $this->db->quoteColumnName($oldName);
        $newName = $this->db->quoteColumnName($newName);
        return "sp_rename '{$table}.{$oldName}', {$newName}, 'COLUMN'";
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The [[getColumnType]] method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     * @return string the SQL statement for changing the definition of a column.
     * @throws NotSupportedException if this is not supported by the underlying DBMS.
     */
    public function alterColumn($table, $column, $type)
    {
        $sqlAfter = [$this->dropConstraintsForColumn($table, $column, 'D')];

        $columnName = $this->db->quoteColumnName($column);
        $tableName = $this->db->quoteTableName($table);
        $constraintBase = preg_replace('/[^a-z0-9_]/i', '', $table . '_' . $column);

        if ($type instanceof \yii\db\mssql\ColumnSchemaBuilder) {
            $type->setAlterColumnFormat();


            $defaultValue = $type->getDefaultValue();
            if ($defaultValue !== null) {
                $sqlAfter[] = $this->addDefaultValue(
                    "DF_{$constraintBase}",
                    $table,
                    $column,
                    $defaultValue instanceof Expression ? $defaultValue : new Expression($defaultValue)
                );
            }

            $checkValue = $type->getCheckValue();
            if ($checkValue !== null) {
                $sqlAfter[] = "ALTER TABLE {$tableName} ADD CONSTRAINT " .
                    $this->db->quoteColumnName("CK_{$constraintBase}") .
                    ' CHECK (' . ($defaultValue instanceof Expression ?  $checkValue : new Expression($checkValue)) . ')';
            }

            if ($type->isUnique()) {
                $sqlAfter[] = "ALTER TABLE {$tableName} ADD CONSTRAINT " . $this->db->quoteColumnName("UQ_{$constraintBase}") . " UNIQUE ({$columnName})";
            }
        }

        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN '
            . $columnName . ' '
            . $this->getColumnType($type) . "\n"
            . implode("\n", $sqlAfter);
    }

    /**
     * {@inheritdoc}
     */
    public function addDefaultValue($name, $table, $column, $value)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' ADD CONSTRAINT '
            . $this->db->quoteColumnName($name) . ' DEFAULT ' . $this->db->quoteValue($value) . ' FOR '
            . $this->db->quoteColumnName($column);
    }

    /**
     * {@inheritdoc}
     */
    public function dropDefaultValue($name, $table)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentAutoIncrementValue(string $table, string $pk): string
    {
        return "SELECT MAX({$this->db->quoteColumnName($pk)}) FROM {$this->db->quoteTableName($table)}";
    }

    /**
     * Resets the identity column for a specified table in MSSQL.
     *
     * @param string $table the name of the table whose identity column is to be reset.
     * The name will be properly quoted by the method.
     * @param int|null $value the value to reset the identity to, minus 1. If null, reseeds to the last used identity
     * value.
     * To reset to a specific value N, pass N-1. For example, to reset to `10`, pass `9`.
     * This is because MSSQL will use the next value `(value + 1)` for the next insert.
     * The value will be properly quoted by the method.
     * @param array $options Additional options (not used in MSSQL implementation, kept for consistency with other DB
     * drivers).
     *
     * @return string the SQL statement to reset the identity column.
     *
     * @throws \yii\db\Exception if there's an error in generating the SQL statement.
     *
     * @see \yii\db\Connection::quoteTableName()
     */
    public function resetSequence(string $table, int|null $value = null, array $options = []): string
    {
        $tableName = $this->db->quoteTableName($table);

        if ($value === null) {
            return "DBCC CHECKIDENT ({$tableName}, RESEED, 0) WITH NO_INFOMSGS;DBCC CHECKIDENT ({$tableName}, RESEED)";
        }

        return "DBCC CHECKIDENT ({$tableName}, RESEED, {$value})";
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
        $enable = $check ? 'CHECK' : 'NOCHECK';
        $schema = $schema ?: $this->db->getSchema()->defaultSchema;
        $tableNames = $this->db->getTableSchema($table) ? [$table] : $this->db->getSchema()->getTableNames($schema);
        $viewNames = $this->db->getSchema()->getViewNames($schema);
        $tableNames = array_diff($tableNames, $viewNames);
        $command = '';

        foreach ($tableNames as $tableName) {
            $tableName = $this->db->quoteTableName("{$schema}.{$tableName}");
            $command .= "ALTER TABLE $tableName $enable CONSTRAINT ALL; ";
        }

        return $command;
    }

     /**
      * Builds a SQL command for adding or updating a comment to a table or a column. The command built will check if a
      * comment already exists. If so, it will be updated, otherwise, it will be added.
      *
      * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
      * @param string $table the table to be commented or whose column is to be commented. The table name will be
      * properly quoted by the method.
      * @param string|null $column optional. The name of the column to be commented. If empty, the command will add the
      * comment to the table instead. The column name will be properly quoted by the method.

      * @return string the SQL statement for adding a comment.

      * @throws InvalidArgumentException if the table does not exist.
      */
    protected function buildAddCommentSql($comment, $table, $column = null)
    {
        $tableSchema = $this->db->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: $table");
        }

        $schemaName = $tableSchema->schemaName ? "N'" . $tableSchema->schemaName . "'" : 'SCHEMA_NAME()';
        $tableName = 'N' . $this->db->quoteValue($tableSchema->name);
        $columnName = $column ? 'N' . $this->db->quoteValue($column) : null;
        $comment = 'N' . $this->db->quoteValue($comment);

        $functionParams = "
            @name = N'MS_description',
            @value = $comment,
            @level0type = N'SCHEMA', @level0name = $schemaName,
            @level1type = N'TABLE', @level1name = $tableName"
            . ($column ? ", @level2type = N'COLUMN', @level2name = $columnName" : '') . ';';

        return "
            IF NOT EXISTS (
                    SELECT 1
                    FROM fn_listextendedproperty (
                        N'MS_description',
                        'SCHEMA', $schemaName,
                        'TABLE', $tableName,
                        " . ($column ? "'COLUMN', $columnName " : ' DEFAULT, DEFAULT ') . "
                    )
            )
                EXEC sys.sp_addextendedproperty $functionParams
            ELSE
                EXEC sys.sp_updateextendedproperty $functionParams
        ";
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function addCommentOnColumn($table, $column, $comment)
    {
        return $this->buildAddCommentSql($comment, $table, $column);
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function addCommentOnTable($table, $comment)
    {
        return $this->buildAddCommentSql($comment, $table);
    }

    /**
     * Builds a SQL command for removing a comment from a table or a column. The command built will check if a comment
     * already exists before trying to perform the removal.
     *
     * @param string $table the table that will have the comment removed or whose column will have the comment removed.
     * The table name will be properly quoted by the method.
     * @param string|null $column optional. The name of the column whose comment will be removed. If empty, the command
     * will remove the comment from the table instead. The column name will be properly quoted by the method.
     * @return string the SQL statement for removing the comment.
     * @throws InvalidArgumentException if the table does not exist.
     * @since 2.0.24
     */
    protected function buildRemoveCommentSql($table, $column = null)
    {
        $tableSchema = $this->db->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: $table");
        }

        $schemaName = $tableSchema->schemaName ? "N'" . $tableSchema->schemaName . "'" : 'SCHEMA_NAME()';
        $tableName = 'N' . $this->db->quoteValue($tableSchema->name);
        $columnName = $column ? 'N' . $this->db->quoteValue($column) : null;

        return "
            IF EXISTS (
                    SELECT 1
                    FROM fn_listextendedproperty (
                        N'MS_description',
                        'SCHEMA', $schemaName,
                        'TABLE', $tableName,
                        " . ($column ? "'COLUMN', $columnName " : ' DEFAULT, DEFAULT ') . "
                    )
            )
                EXEC sys.sp_dropextendedproperty
                    @name = N'MS_description',
                    @level0type = N'SCHEMA', @level0name = $schemaName,
                    @level1type = N'TABLE', @level1name = $tableName"
                    . ($column ? ", @level2type = N'COLUMN', @level2name = $columnName" : '') . ';';
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function dropCommentFromColumn($table, $column)
    {
        return $this->buildRemoveCommentSql($table, $column);
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function dropCommentFromTable($table)
    {
        return $this->buildRemoveCommentSql($table);
    }

    /**
     * Returns an array of column names given model name.
     *
     * @param string|null $modelClass name of the model class
     * @return array|null array of column names
     */
    protected function getAllColumnNames($modelClass = null)
    {
        if (!$modelClass) {
            return null;
        }
        /* @var $modelClass \yii\db\ActiveRecord */
        $schema = $modelClass::getTableSchema();
        return array_keys($schema->columns);
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function selectExists($rawSql)
    {
        return 'SELECT CASE WHEN EXISTS(' . $rawSql . ') THEN 1 ELSE 0 END';
    }

    /**
     * Normalizes data to be saved into the table, performing extra preparations and type converting, if necessary.
     * @param string $table the table that data will be saved into.
     * @param array $columns the column data (name => value) to be saved into the table.
     * @return array normalized columns
     */
    private function normalizeTableRowData($table, $columns, &$params)
    {
        if (($tableSchema = $this->db->getSchema()->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->columns;
            foreach ($columns as $name => $value) {
                // @see https://github.com/yiisoft/yii2/issues/12599
                if (isset($columnSchemas[$name]) && $columnSchemas[$name]->type === Schema::TYPE_BINARY && $columnSchemas[$name]->dbType === 'varbinary' && (is_string($value))) {
                    // @see https://github.com/yiisoft/yii2/issues/12599
                    $columns[$name] = new Expression('CONVERT(VARBINARY(MAX), ' . ('0x' . bin2hex($value)) . ')');
                }
            }
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function insertWithReturningPks(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        $tableSchema = $this->db->getTableSchema($table);
        $primaryKeys = $tableSchema->primaryKey ?? [];

        if (empty($primaryKeys)) {
            return $this->insert($table, $columns, $params);
        }

        $createdCols = [];
        $insertedCols = [];
        $returnColumns = array_intersect_key($tableSchema?->columns ?? [], array_flip($primaryKeys));

        foreach ($returnColumns as $returnColumn) {
            $dbType = $returnColumn->dbType;

            if (in_array($dbType, ['char', 'varchar', 'nchar', 'nvarchar', 'binary', 'varbinary'], true)) {
                $dbType .= '(MAX)';
            } elseif ($dbType === 'timestamp') {
                $dbType = $returnColumn->allowNull ? 'varbinary(8)' : 'binary(8)';
            }

            $quotedName = $this->db->quoteColumnName($returnColumn->name);
            $createdCols[] = $quotedName . ' ' . (string) $dbType . ' ' . ($returnColumn->allowNull ? 'NULL' : '');

            $insertedCols[] = 'INSERTED.' . $quotedName;
        }

        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($tableSchema, $columns, $params);

        $sql = 'INSERT INTO ' . $this->db->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . ' OUTPUT ' . implode(',', $insertedCols) . ' INTO @temporary_inserted'
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : ' ' . $values);

        return 'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE (' . implode(', ', $createdCols) . ');'
            . $sql . ';SELECT * FROM @temporary_inserted;';
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.microsoft.com/en-us/sql/t-sql/statements/merge-transact-sql
     * @see https://weblogs.sqlteam.com/dang/2009/01/31/upsert-race-condition-with-merge/
     */
    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns,
        array &$params = []
    ): string {
        /** @psalm-var \yii\db\Constraint[] $constraints */
        $constraints = [];

        /** @psalm-var string[] $insertNames */
        [$uniqueNames, $insertNames, $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints
        );

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        $onCondition = ['or'];
        $quotedTableName = $this->db->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $constraintCondition = ['and'];

            $columnNames = $constraint->columnNames ?? [];

            if (is_array($columnNames)) {
                /** @psalm-var string[] $columnNames */
                foreach ($columnNames as $name) {
                    $quotedName = $this->db->quoteColumnName($name);
                    $constraintCondition[] = "$quotedTableName.$quotedName=[EXCLUDED].$quotedName";
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

        $mergeSql = 'MERGE '
            . $this->db->quoteTableName($table)
            . ' WITH (HOLDLOCK) '
            . 'USING (' . (!empty($placeholders)
            ? 'VALUES (' . implode(', ', $placeholders) . ')'
            : ltrim((string) $values, ' ')) . ') AS [EXCLUDED] (' . implode(', ', $insertNames) . ') ' . "ON ($on)";

        $insertValues = [];

        foreach ($insertNames as $name) {
            $quotedName = $this->db->quoteColumnName($name);

            if (strrpos($quotedName, '.') === false) {
                $quotedName = '[EXCLUDED].' . $quotedName;
            }

            $insertValues[] = $quotedName;
        }

        $insertSql = 'INSERT (' . implode(', ', $insertNames) . ')' . ' VALUES (' . implode(', ', $insertValues) . ')';

        if ($updateNames === []) {
            /** there are no columns to update */
            $updateColumns = false;
        }

        if ($updateColumns === false) {
            return "$mergeSql WHEN NOT MATCHED THEN $insertSql;";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            /** @psalm-var string[] $updateNames */
            foreach ($updateNames as $name) {
                $quotedName = $this->db->quoteColumnName($name);
                if (strrpos($quotedName, '.') === false) {
                    $quotedName = '[EXCLUDED].' . $quotedName;
                }

                $updateColumns[$name] = new Expression($quotedName);
            }
        }

        /**
         * @var array $params
         *
         * @psalm-var string[] $updates
         * @psalm-var array<string, ExpressionInterface|string> $updateColumns
         */
        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        $updateSql = 'UPDATE SET ' . implode(', ', $updates);

        return "$mergeSql WHEN MATCHED THEN $updateSql WHEN NOT MATCHED THEN $insertSql;";
    }

    /**
     * {@inheritdoc}
     */
    public function update($table, $columns, $condition, &$params)
    {
        return parent::update($table, $this->normalizeTableRowData($table, $columns, $params), $condition, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType($type)
    {
        $columnType = parent::getColumnType($type);
        // remove unsupported keywords
        $columnType = preg_replace("/\s*comment '.*'/i", '', $columnType);
        $columnType = preg_replace('/ first$/i', '', $columnType);

        return $columnType;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractAlias($table)
    {
        if (preg_match('/^\[.*\]$/', $table)) {
            return false;
        }

        return parent::extractAlias($table);
    }

    /**
     * Builds a SQL statement for dropping constraints for column of table.
     *
     * @param string $table the table whose constraint is to be dropped. The name will be properly quoted by the method.
     * @param string $column the column whose constraint is to be dropped. The name will be properly quoted by the method.
     * @param string $type type of constraint, leave empty for all type of constraints(for example: D - default, 'UQ' - unique, 'C' - check)
     * @see https://docs.microsoft.com/sql/relational-databases/system-catalog-views/sys-objects-transact-sql
     * @return string the DROP CONSTRAINTS SQL
     */
    private function dropConstraintsForColumn($table, $column, $type = '')
    {
        return "DECLARE @tableName VARCHAR(MAX) = '" . $this->db->quoteTableName($table) . "'
DECLARE @columnName VARCHAR(MAX) = '{$column}'

WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
        " . (!empty($type) ? " WHERE so.[type]='{$type}'" : '') . ")
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END";
    }

    /**
     * Drop all constraints before column delete
     * {@inheritdoc}
     */
    public function dropColumn($table, $column)
    {
        return $this->dropConstraintsForColumn($table, $column) . "\nALTER TABLE " . $this->db->quoteTableName($table)
            . ' DROP COLUMN ' . $this->db->quoteColumnName($column);
    }
}
