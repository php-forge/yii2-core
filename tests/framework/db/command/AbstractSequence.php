<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command;

use yii\db\Connection;

abstract class AbstractSequence extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    protected function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testExecuteResetIdentity(): void
    {
        $table = 'customer';
        $sequenceName = '';

        if ($this->db->driverName === 'oci') {
            $sequenceName = $this->db->quoteTableName($table . '_SEQ');
        }

        // retrieve the next value of auto-increment (identity/sequence) column
        $nextSequenceValue = $this->db->createCommand()->getNextAutoIncrementValue($table);

        $this->assertSame(4, $nextSequenceValue);

        // reset the sequence to 10 and return next value of the sequence
        $result = $this->db->createCommand()->executeResetSequence($table, 10);

        $this->assertSame(10, $result);

        // insert a new row
        $result = $this->db
            ->createCommand()
            ->insert(
                $table,
                ['email' => 'user4@example.com', 'name' => 'user4', 'address' => 'address4']
            )->execute();

        $this->assertSame(1, $result);

        // retrieve the last insert ID
        $lastInsertID = $this->db->getLastInsertID($sequenceName);

        $this->assertSame('11', $lastInsertID);
    }

    public function testExecuteResetIdentityWithValueEqualToCurrent(): void
    {
        $table = 'item';

        // reset sequence to 6 and return next value of the sequence
        $result = $this->db->createCommand()->executeResetSequence($table, 6);

        $this->assertSame(6, $result);

        // retrieve the next value of auto-increment (identity/sequence) column
        $nextSequenceValue = $this->db->createCommand()->getNextAutoIncrementValue($table);

        $this->assertSame(7, $nextSequenceValue);
    }

    public function testExecuteResetIdentityWithEmptyValue(): void
    {
        $table = 'item';

        // reset sequence to max value and return next value of the sequence
        $result = $this->db->createCommand()->executeResetSequence($table);

        $this->assertSame(6, $result);

        // retrieve the next value of auto-increment (identity/sequence) column
        $nextSequenceValue = $this->db->createCommand()->getNextAutoIncrementValue($table);

        $this->assertSame(7, $nextSequenceValue);
    }
}
