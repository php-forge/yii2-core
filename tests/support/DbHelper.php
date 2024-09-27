<?php

declare(strict_types=1);

namespace yiiunit\support;

use yii\db\Connection;

use function array_merge;
use function assert;
use function explode;
use function file_get_contents;
use function preg_replace;
use function str_replace;
use function trim;

final class DbHelper
{
    /**
     * Changes the SQL query for Oracle batch insert.
     *
     * @param string $str string SQL query to change.
     */
    public static function changeSqlForOracleBatchInsert(string &$str): void
    {
        $str = str_replace('INSERT INTO', 'INSERT ALL INTO', $str) . ' SELECT 1 FROM SYS.DUAL';
    }

    /**
     * Ensures that a table does not exist in the database.
     * If the table exists, it will be dropped.
     *
     * @param Connection $db the database connection.
     * @param string $tableName the name of the table to ensure does not exist.
     *
     * @return void
     */
    public static function ensureNoTable(Connection $db, string $tableName): void
    {
        if ($db->hasTable($tableName)) {
            $db->createCommand()->dropTable($tableName)->execute();
            assert($db->hasTable($tableName) === false, "Table {$tableName} should not exist.");
        }
    }

    /**
     * Loads the fixture into the database.
     */
    public static function loadFixture(Connection $db, string $fixture): void
    {
        $db->open();

        if ($db->getDriverName() === 'oci') {
            [$drops, $creates] = explode('/* STATEMENTS */', file_get_contents($fixture), 2);
            [$statements, $triggers, $data] = explode('/* TRIGGERS */', $creates, 3);
            $lines = array_merge(
                explode('--', $drops),
                explode(';', $statements),
                explode('/', $triggers),
                explode(';', $data)
            );
        } else {
            $lines = explode(';', file_get_contents($fixture));
        }

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->pdo?->exec($line);
            }
        }
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param string $sql string SQL statement to adjust.
     * @param string $driverName string DBMS name.
     *
     * @return mixed
     */
    public static function replaceQuotes(string $sql, string $driverName): string
    {
        return match ($driverName) {
            'mysql', 'sqlite' => str_replace(['[[', ']]'], '`', $sql),
            'oci' => str_replace(['[[', ']]'], '"', $sql),
            'pgsql' => str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))]])/', '"', $sql)),
            'db', 'sqlsrv' => str_replace(['[[', ']]'], ['[', ']'], $sql),
            default => $sql,
        };
    }
}
