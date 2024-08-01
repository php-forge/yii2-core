<?php

declare(strict_types=1);

namespace yiiunit\support;

use Yii;
use yii\db\Connection;

final class MssqlConnection
{
    public static string $driverName = 'mssql';

    public static function getConnection(bool $fixture = false): Connection
    {
        Yii::$app->set('db', self::getConfig());

        if ($fixture) {
            DbHelper::loadFixture(Yii::$app->getDb(), dirname(__DIR__) . '/data/mssql.sql');
        }

        return Yii::$app->getDb();
    }

    public static function getConfig(): array
    {
        return [
            '__class' => Connection::class,
            'dsn' => 'sqlsrv:Server=127.0.0.1,1433;Database=yiitest',
            'username' => 'SA',
            'password' => 'YourStrong!Passw0rd',
        ];
    }
}
