<?php

declare(strict_types=1);

namespace yiiunit\support;

final class MssqlConnection extends AbstractConnection
{
    public static string $dsn = 'sqlsrv:Server=127.0.0.1,1433;Database=yiitest';
    public static string $fixture = 'mssql.sql';
    public static string $password = 'YourStrong!Passw0rd';
    public static string $username = 'SA';
}
