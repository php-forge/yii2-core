<?php

declare(strict_types=1);

namespace yiiunit\support;

use Yii;
use yii\db\Connection;

abstract class AbstractConnection
{
    public static string $dsn = '';
    public static string $driverName = '';
    public static string $fixture = '';
    public static string $password = '';
    public static string $username = '';

    public static function getConnection(bool $fixture = false): Connection
    {
        $connection = new Connection(
            [
                'dsn' => static::$dsn,
                'username' => static::$username,
                'password' => static::$password,
            ]
        );

        if ($fixture) {
            DbHelper::loadFixture($connection, dirname(__DIR__) . '/data/' . static::$fixture);
        }

        if (Yii::$app !== null) {
            Yii::$app->set('db', $connection);
        }

        return $connection;
    }
}
