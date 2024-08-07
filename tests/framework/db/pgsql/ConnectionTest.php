<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

use yii\db\Transaction;

/**
 * @group db
 * @group pgsql
 */
class ConnectionTest extends \yiiunit\framework\db\ConnectionTest
{
    protected $driverName = 'pgsql';

    public function testConnection()
    {
        $this->assertIsObject($this->getConnection(true));
    }

    public function testTransactionIsolation()
    {
        $connection = $this->getConnection(true);

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::READ_COMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::SERIALIZABLE);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::SERIALIZABLE . ' READ ONLY DEFERRABLE');
        $transaction->commit();

        $this->assertTrue(true); // No error occurred – assert passed.
    }
}
