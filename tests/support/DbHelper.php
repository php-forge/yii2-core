<?php

declare(strict_types=1);

namespace yiiunit\support;

use yii\db\Connection;

use function explode;
use function file_get_contents;
use function preg_replace;
use function str_replace;
use function trim;

final class DbHelper
{
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
