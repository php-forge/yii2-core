<?php

declare(strict_types=1);

namespace yiiunit\support;

use Yii;
use yii\db\Connection;

final class MysqlConnection
{
    public static $driverName = 'mysql';

    public static function getConnection(bool $fixture = false): Connection
    {
        Yii::$app->set('db', self::getConfig());

        if ($fixture) {
            DbHelper::loadFixture(Yii::$app->getDb(), dirname(__DIR__) . '/data/mysql.sql');
        }

        return Yii::$app->getDb();
    }

    public static function getConfig(): array
    {
        return [
            '__class' => Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;dbname=yiitest',
            'username' => 'root',
            'password' => 'root',
        ];
    }
}
