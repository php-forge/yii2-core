<?php

declare(strict_types=1);

namespace yiiunit\support;

final class OciConnection extends AbstractConnection
{
    public static string $dsn = 'oci:dbname=//localhost:1521/XEPDB1;charset=AL32UTF8;';
    public static string $fixture = 'oci.sql';
    public static string $password = 'root';
    public static string $username = 'yiitest';
}
