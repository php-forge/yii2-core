<?php

declare(strict_types=1);

namespace yiiunit\support;

final class MysqlConnection extends AbstractConnection
{
    public static string $dsn = 'mysql:host=127.0.0.1;dbname=yiitest';
    public static string $fixture = 'mysql.sql';
    public static string $password = '';
    public static string $username = 'root';
}
