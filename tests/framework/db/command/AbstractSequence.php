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

    public function testExecuteResetSequence(): void
    {
        $table = 'customer';
        $sequenceName = '';

        if ($this->db->driverName === 'oci') {
            $sequenceName = $this->db->quoteTableName($table . '_SEQ');
        }

        // reset the sequence to 10.
        // if `ORACLE` is used, the sequence will be reset to 10.
        // if `SQLSRV` is used, the sequence will be reset to 9. This is because the `IDENTITY` column starts from 1.
        $result = match ($this->db->driverName) {
            'sqlsrv' => $this->db->createCommand()->executeResetSequence($table, 9),
            default => $this->db->createCommand()->executeResetSequence($table, 10),
        };

        $this->assertSame(0, $result);

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

        $this->assertSame('10', $lastInsertID);
    }

    public function testExecuteResetSequenceTo1(): void
    {
        $table = 'item';
        $sequenceName = '';

        $driverName = $this->db->driverName;

        if ($driverName === 'oci') {
            $sequenceName = $this->db->quoteTableName($table . '_SEQ');
        }

        // remove all rows
        // if `SQLSRV` is used, the sequence will be reset to 1, if truncate table its executed.
        // if `SQLSRV` is used, the sequence will be reset to 6, if delete from table its executed.
        $result = match ($driverName) {
            'sqlsrv' => $this->db->createCommand()->truncateTable($table)->execute(),
            default => $this->db->createCommand()->delete($table)->execute(),
        };

        match ($driverName) {
            'sqlsrv' => $this->assertSame(0, $result),
            default => $this->assertSame(5, $result),
        };

        // reset sequence to 1
        $result = $this->db->createCommand()->executeResetSequence($table, 1);

        $this->assertSame(0, $result);

        // insert a new row
        $result = $this->db
            ->createCommand()
            ->insert(
                $table,
                ['name' => 'name1', 'category_id' => 1]
            )->execute();

        $this->assertSame(1, $result);

        // retrieve the last insert ID
        $lastInsertID = $this->db->getLastInsertID($sequenceName);

        $this->assertSame('1', $lastInsertID);
    }

    public function testExecuteResetIdentityWithValueEqualToCurrent(): void
    {
        $table = 'item';

        $sequenceName = '';

        $driverName = $this->db->driverName;

        if ($driverName === 'oci') {
            $sequenceName = $this->db->quoteTableName($table . '_SEQ');
        }

        // reset sequence to current value
        $result = $this->db->createCommand()->executeResetSequence($table, 5);

        $this->assertSame(0, $result);

        // retrieve the last insert ID
        $lastInsertID = $this->db->getLastInsertID($sequenceName);

        match ($driverName) {
            'oci' => $this->assertSame('5', $lastInsertID),
            default => $this->assertSame('6', $lastInsertID),
        };
    }

    public function testExecuteResetIdentityWithEmptyValue(): void
    {
        $table = 'item';

        $sequenceName = '';

        $driverName = $this->db->driverName;

        if ($driverName === 'oci') {
            $sequenceName = $this->db->quoteTableName($table . '_SEQ');
        }

        // reset sequence to max value and return next value of the sequence
        $result = $this->db->createCommand()->executeResetSequence($table);

        $this->assertSame(0, $result);

        // retrieve the last insert ID
        $lastInsertID = $this->db->getLastInsertID($sequenceName);

        match ($driverName) {
            'oci' => $this->assertSame('5', $lastInsertID),
            default => $this->assertSame('6', $lastInsertID),
        };
    }
}
