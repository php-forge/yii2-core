<?php

declare(strict_types=1);

namespace yiiunit\support;

use Yii;
use yii\db\Connection;

final class PgsqlConnection
{
    public static string $driverName = 'pgsql';
    public static string $fixture = 'pgsql.sql';

    public static function getConnection(bool $fixture = false): Connection
    {
        Yii::$app->set('db', self::getConfig());

        if ($fixture) {
            DbHelper::loadFixture(Yii::$app->getDb(), dirname(__DIR__) . '/data/' . self::$fixture);
        }

        return Yii::$app->getDb();
    }

    public static function getConfig(): array
    {
        return [
            '__class' => Connection::class,
            'dsn' => 'pgsql:host=localhost;dbname=yiitest;port=5432;',
            'username' => 'root',
            'password' => 'root',
        ];
    }
}
