<?php

declare(strict_types=1);

namespace yiiunit\support;

final class SqliteConnection extends AbstractConnection
{
    public static string $dsn = 'sqlite::memory:';
    public static string $driverName = 'sqlite';
    public static string $fixture = 'sqlite.sql';
}
