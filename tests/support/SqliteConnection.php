<?php

declare(strict_types=1);

namespace yiiunit\support;

use Yii;
use yii\db\Connection;

final class SqliteConnection
{
    public static string $driverName = 'sqlite';

    public static function getConnection(bool $fixture = false): Connection
    {
        Yii::$app->set('db', self::getConfig());

        if ($fixture) {
            DbHelper::loadFixture(Yii::$app->getDb(), dirname(__DIR__) . '/data/sqlite.sql');
        }

        return Yii::$app->getDb();
    }

    public static function getConfig(): array
    {
        return [
            '__class' => Connection::class,
            'dsn' => 'sqlite::memory:',
        ];
    }
}
