<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci;

use yii\db\Connection;
use yii\db\Transaction;

/**
 * @group db
 * @group oci
 */
class ConnectionTest extends \yiiunit\framework\db\ConnectionTest
{
    protected $driverName = 'oci';

    public function testSerialize()
    {
        $connection = $this->getConnection(false, false);
        $connection->open();
        $serialized = serialize($connection);
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf('yii\db\Connection', $unserialized);

        $this->assertEquals(123, $unserialized->createCommand('SELECT 123 FROM DUAL')->queryScalar());
    }

    public function testTransactionIsolation()
    {
        $connection = $this->getConnection(true);

        $transaction = $connection->beginTransaction(Transaction::READ_COMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction(Transaction::SERIALIZABLE);
        $transaction->commit();

        $this->assertTrue(true);
    }

    /**
     * Note: The READ UNCOMMITTED isolation level allows dirty reads. Oracle Database doesn't use dirty reads, nor does
     * it even allow them.
     *
     * Change Transaction::READ_UNCOMMITTED => Transaction::READ_COMMITTED.
     */
    public function testTransactionShortcutCustom()
    {
        $connection = $this->getConnection(true);

        $result = $connection->transaction(static function (Connection $db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            return true;
        }, Transaction::READ_COMMITTED);

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $connection->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'"
        )->queryScalar();
        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }
}
