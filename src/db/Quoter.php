<?php

declare(strict_types=1);

namespace yii\db;

use function addcslashes;
use function explode;
use function implode;
use function is_string;
use function preg_replace_callback;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strrpos;
use function substr;

/**
 * The Quoter is a class used to quote table and column names for use in SQL statements.
 *
 * It provides a set of methods for quoting different types of names, such as table names, column names, and schema
 * names.
 *
 * The Quoter class is used by {@see \yii\db\QueryBuilder} to quote names.
 *
 * It's also used by {@see \yii\db\Command} to quote names in SQL statements before passing them to database servers.
 */
class Quoter
{
    public function __construct(
        /** @psalm-var string[]|string */
        private readonly array|string $columnQuoteCharacter,
        /** @psalm-var string[]|string */
        private readonly array|string $tableQuoteCharacter,
        private readonly string $tablePrefix = ''
    ) {
    }

    /**
     * Returns the table prefix to be used for table names.
     *
     * @return string the table prefix to be used for table names.
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Splits full table name into parts.
     *
     * @param string $name the full name of the table.
     *
     * @return array the table name parts.
     *
     * @psalm-return string[] the table name parts.
     */
    public function getTableNameParts(string $tableName, bool $withColumn = false): array
    {
        $parts = explode('.', $tableName);

        return $this->unquoteParts($parts, $withColumn);
    }

    /**
     * Returns the actual name of a given table name.
     *
     * This method strips off curly brackets from the given table name and replaces the percentage character '%' with
     * [[Connection::tablePrefix]].
     *
     * @param string $tableName the table name to be converted.
     *
     * @return string the real name of the given table name.
     */
    public function getRawTableName(string $tableName): string
    {
        return preg_replace_callback(
            '/\{\{(.*?)\}\}/',
            function ($matches) {
                return strtr($matches[1], ['%' => $this->tablePrefix]);
            },
            $tableName
        );
    }

    /**
     * Ensures name of the column is wrapped with `[[ and ]]`.
     *
     * @param string $columnName the name to quote.
     *
     * @return string the quoted name.
     */
    public function ensureColumnName(string $columnName): string
    {
        if (strrpos($columnName, '.') !== false) {
            $parts = explode('.', $columnName);
            $columnName = $parts[count($parts) - 1];
        }

        return preg_replace('|^\[\[([_\w\-. ]+)\]\]$|', '\1', $columnName);
    }

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name has a prefix, it quotes the prefix.
     * If the column name is already quoted or has '(', '[[' or '{{', then this method does nothing.
     *
     * @param string $columnName the column name to quote.
     *
     * @return string the quoted column name.
     *
     * {@see quoteSimpleColumnName()}
     */
    public function quoteColumnName(string $columnName): string
    {
        if (str_contains($columnName, '(') || str_contains($columnName, '[[')) {
            return $columnName;
        }

        if (($pos = strrpos($columnName, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($columnName, 0, $pos)) . '.';
            $columnName = substr($columnName, $pos + 1);
        } else {
            $prefix = '';
        }

        if (str_contains($columnName, '{{')) {
            return $columnName;
        }

        return $prefix . $this->quoteSimpleColumnName($columnName);
    }

    /**
     * Quotes a simple column name for use in a query.
     *
     * A simple column name should contain the column name only without any prefix. If the column name is already quoted
     * or is the asterisk character '*', this method will do nothing.
     *
     * @param string $columnName the column name to quote.
     *
     * @return string the quoted column name.
     */
    public function quoteSimpleColumnName(string $columnName): string
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->columnQuoteCharacter;
        } else {
            [$startingCharacter, $endingCharacter] = $this->columnQuoteCharacter;
        }

        return $columnName === '*' || str_contains($columnName, $startingCharacter)
            ? $columnName : $startingCharacter . $columnName . $endingCharacter;
    }

    /**
     * Quotes a simple table name for use in a query.
     *
     * A simple table name should contain the table name only without any schema prefix. If the table name is already
     * quoted, this method will do nothing.
     *
     * @param string $tableName the table name to quote.
     *
     * @return string the quoted table name.
     */
    public function quoteSimpleTableName(string $tableName): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->tableQuoteCharacter;
        } else {
            [$startingCharacter, $endingCharacter] = $this->tableQuoteCharacter;
        }

        return str_contains($tableName, $startingCharacter)
            ? $tableName : $startingCharacter . $tableName . $endingCharacter;
    }

    /**
     * Processes an SQL statement by quoting table and column names that are inside within double brackets.
     *
     * Tokens inside within double curly brackets are treated as table names, while tokens inside within double square
     * brackets are column names. They will be quoted as such.
     *
     * Also, the percentage character "%" at the beginning or ending of a table name will be replaced with
     * [[Connection::tablePrefix]].
     *
     * @param string $sql the SQL statement to quote.
     *
     * @return string the quoted SQL statement.
     */
    public function quoteSql(string $sql): string
    {
        return preg_replace_callback(
            '/({{(%?[\w\-. ]+%?)}}|\\[\\[([\w\-. ]+)]])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                }

                return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
            },
            $sql
        );
    }

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name has a schema prefix, then it will also quote the prefix.
     *
     * If the table name is already quoted or has `(` or `{{`, then this method will do nothing.
     *
     * @param string $tableName the table name to quote.
     *
     * @return string The quoted table name.
     *
     * {@see quoteSimpleTableName()}
     */
    public function quoteTableName(string $tableName): string
    {
        if (str_starts_with($tableName, '(') && str_ends_with($tableName, ')')) {
            return $tableName;
        }

        if (str_contains($tableName, '{{')) {
            return $tableName;
        }

        if (!str_contains($tableName, '.')) {
            return $this->quoteSimpleTableName($tableName);
        }

        $parts = $this->getTableNameParts($tableName);

        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }

        return implode('.', $parts);
    }

    /**
     * Quotes a string value for use in a query.
     *
     * Note: That if the parameter isn't a string, it will be returned without change.
     * Attention: The usage of this method isn't safe.
     * Use prepared statements.
     *
     * @param mixed $value the value to quote.
     *
     * @return mixed the quoted value.
     */
    public function quoteValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (preg_match('/^\[.*]$/', $value)) {
            return $value;
        }

        return '\'' . str_replace('\'', '\'\'', addcslashes($value, "\000\032")) . '\'';
    }

    /**
     * Unquotes a simple column name.
     *
     * A simple column name should contain the column name only without any prefix.
     *
     * If the column name isn't quoted or is the asterisk character '*', this method will do nothing.
     *
     * @param string $columnName the column name to unquote.
     *
     * @return string The unquoted column name.
     */
    public function unquoteSimpleColumnName(string $columnName): string
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $this->columnQuoteCharacter;
        } else {
            $startingCharacter = $this->columnQuoteCharacter[0];
        }

        return !str_contains($columnName, $startingCharacter) ? $columnName : substr($columnName, 1, -1);
    }

    /**
     * Unquotes a simple table name.
     *
     * A simple table name should contain the table name only without any schema prefix.
     *
     * If the table name isn't quoted, this method will do nothing.
     *
     * @param string $tableName the table name to unquote.
     *
     * @return string the unquoted table name.
     */
    public function unquoteSimpleTableName(string $tableName): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $this->tableQuoteCharacter;
        } else {
            $startingCharacter = $this->tableQuoteCharacter[0];
        }

        return !str_contains($tableName, $startingCharacter) ? $tableName : substr($tableName, 1, -1);
    }

    /**
     * @psalm-param string[] $parts Parts of table name
     *
     * @psalm-return string[]
     */
    protected function unquoteParts(array $parts, bool $withColumn): array
    {
        $lastKey = count($parts) - 1;

        foreach ($parts as $k => &$part) {
            $part = ($withColumn || $lastKey === $k) ?
                $this->unquoteSimpleColumnName($part) :
                $this->unquoteSimpleTableName($part);
        }

        return $parts;
    }
}
