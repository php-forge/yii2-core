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

    public function getTableNameParts(string $name, bool $withColumn = false): array
    {
        $parts = explode('.', $name);

        return $this->unquoteParts($parts, $withColumn);
    }

    public function ensureColumnName(string $name): string
    {
        if (strrpos($name, '.') !== false) {
            $parts = explode('.', $name);
            $name = $parts[count($parts) - 1];
        }

        return preg_replace('|^\[\[([_\w\-. ]+)\]\]$|', '\1', $name);
    }

    public function quoteColumnName(string $name): string
    {
        if (str_contains($name, '(') || str_contains($name, '[[')) {
            return $name;
        }

        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }

        if (str_contains($name, '{{')) {
            return $name;
        }

        return $prefix . $this->quoteSimpleColumnName($name);
    }

    public function quoteSimpleColumnName(string $name): string
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->columnQuoteCharacter;
        } else {
            [$startingCharacter, $endingCharacter] = $this->columnQuoteCharacter;
        }

        return $name === '*' || str_contains($name, $startingCharacter) ? $name : $startingCharacter . $name
            . $endingCharacter;
    }

    public function quoteSimpleTableName(string $name): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->tableQuoteCharacter;
        } else {
            [$startingCharacter, $endingCharacter] = $this->tableQuoteCharacter;
        }

        return str_contains($name, $startingCharacter) ? $name : $startingCharacter . $name . $endingCharacter;
    }

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

    public function quoteTableName(string $name): string
    {
        if (str_starts_with($name, '(') && str_ends_with($name, ')')) {
            return $name;
        }

        if (str_contains($name, '{{')) {
            return $name;
        }

        if (!str_contains($name, '.')) {
            return $this->quoteSimpleTableName($name);
        }

        $parts = $this->getTableNameParts($name);

        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }

        return implode('.', $parts);
    }

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

    public function unquoteSimpleColumnName(string $name): string
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $this->columnQuoteCharacter;
        } else {
            $startingCharacter = $this->columnQuoteCharacter[0];
        }

        return !str_contains($name, $startingCharacter) ? $name : substr($name, 1, -1);
    }

    public function unquoteSimpleTableName(string $name): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $this->tableQuoteCharacter;
        } else {
            $startingCharacter = $this->tableQuoteCharacter[0];
        }

        return !str_contains($name, $startingCharacter) ? $name : substr($name, 1, -1);
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