<?php

declare(strict_types=1);

namespace yiiunit\support;

use Yii;
use yii\db\Connection;

final class OciConnection
{
    public static string $driverName = 'oci';

    public static function getConnection(bool $fixture = false): Connection
    {
        Yii::$app->set('db', self::getConfig());

        if ($fixture) {
            DbHelper::loadFixture(Yii::$app->getDb(), dirname(__DIR__) . '/data/oci.sql');
        }

        return Yii::$app->getDb();
    }

    public static function getConfig(): array
    {
        return [
            '__class' => Connection::class,
            'dsn' => 'oci:dbname=localhost/XE;charset=AL32UTF8;',
            'username' => 'system',
            'password' => 'oracle',
        ];
    }
}
