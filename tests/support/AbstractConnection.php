<?php

declare(strict_types=1);

namespace yiiunit\support;

use Yii;
use yii\db\Connection;

use function dirname;

abstract class AbstractConnection
{
    public static string $dsn = '';
    public static string $driverName = '';
    public static string $fixture = '';
    public static string $password = '';
    public static string $username = '';

    public static function getConnection(
        bool $fixture = false,
        string|null $fixturePath = null,
        bool $enableSchemaCache = false,
    ): Connection {
        $config = [
            'dsn' => static::$dsn,
            'username' => static::$username,
            'password' => static::$password,
        ];

        if ($enableSchemaCache === false) {
            $config['schemaCache'] = null;
        }

        $connection = new Connection($config);

        if ($fixture) {
            $fixturePath ??= dirname(__DIR__) . '/data/' . static::$fixture;

            DbHelper::loadFixture($connection, $fixturePath);
        }

        if (Yii::$app !== null) {
            Yii::$app->set('db', $connection);
        }

        return $connection;
    }
}
