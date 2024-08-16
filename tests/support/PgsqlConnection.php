<?php

declare(strict_types=1);

namespace yiiunit\support;

final class PgsqlConnection extends AbstractConnection
{
    public static string $dsn = 'pgsql:host=localhost;dbname=yiitest;port=5432;';
    public static string $fixture = 'postgres.sql';
    public static string $password = 'root';
    public static string $username = 'root';
}
