<?php

declare(strict_types=1);

namespace yii\db;

use function preg_match;
use function preg_replace;
use function rtrim;
use function str_ends_with;
use function strtolower;

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
     * Adds a suffix to the given string if it doesn't already end with it.
     * The check is case-insensitive, but the suffix will be added with the original case.
     *
     * @param string $input the base string to which the suffix will be added.
     * @param string $suffix the suffix to append if not already present.
     *
     * @return string the string with the suffix added if it was not present.
     */
    public static function addSuffix(string $input, string $suffix): string
    {
        if (str_ends_with(strtolower($input), strtolower($suffix)) === false) {
            $input .= $suffix;
        }

        return $input;
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
