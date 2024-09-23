<?php

declare(strict_types=1);

namespace yii\db;

class SqlHelper
{
    /**
     * Removes leading whitespace, empty lines, and trailing newline from a SQL string.
     *
     * @param string $sql the SQL string to clean.
     *
     * @return string the cleaned SQL string.
     */
    public static function cleanSql(string $sql): string
    {
        $sql = preg_replace('/^\h*\v+/m', '', $sql);

        return rtrim($sql);
    }

    /**
     * Returns a value indicating whether a SQL statement is for read purpose.
     *
     * @param string $sql the SQL statement.
     *
     * @return bool whether a SQL statement is for read purpose.
     */
    public static function isReadQuery(string $sql): bool
    {
        $pattern = '/^\s*(SELECT|SHOW|DESCRIBE)\b/i';

        return preg_match($pattern, $sql) > 0;
    }
}
