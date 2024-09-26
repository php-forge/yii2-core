<?php

declare(strict_types=1);

namespace yii\db;

use function preg_match;
use function preg_replace;
use function rtrim;
use function str_ends_with;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;

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
     * If the suffix is already present in lowercase, it will be replaced with the uppercase version.
     * The check is case-insensitive.
     *
     * @param string $input the base string to which the suffix will be added.
     * @param string $suffix the suffix to append or replace with if necessary.
     *
     * @return string the string with the suffix added or replaced in uppercase.
     */
    public static function addSuffix(string $input, string $suffix): string
    {
        if ($suffix === '') {
            return $input;
        }

        $suffixUpper = strtoupper($suffix);

        if (str_ends_with(strtolower($input), strtolower($suffix))) {
            return substr($input, 0, - strlen($suffix)) . $suffixUpper;
        }

        return $input . $suffixUpper;
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
