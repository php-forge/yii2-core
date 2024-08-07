<?php

declare(strict_types=1);

namespace yiiunit\support;

final class OciConnection extends AbstractConnection
{
    public static string $dsn = 'oci:dbname=localhost/XE;charset=AL32UTF8;';
    public static string $fixture = 'oci.sql';
    public static string $password = 'oracle';
    public static string $username = 'system';
}
