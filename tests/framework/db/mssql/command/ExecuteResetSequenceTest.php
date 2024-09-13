<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\command;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group command
 * @group execute-reset-sequence
 */
final class ExecuteResetSequenceTest extends \yiiunit\framework\db\command\AbstractExecuteResetSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\CommandProvider::executeResetSequence
     */
    public function testExecuteResetSequence(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value = null
    ): void {
        $this->columnSchema = [
            'id' => 'INT IDENTITY PRIMARY KEY',
            'name' => 'NVARCHAR(128)',
        ];

        parent::testExecuteResetSequence($tableName, $insertRows, $expectedIds, $value);
    }

    public function testExecuteResetSequenceWithTableNotSequence(): void
    {
        $this->columnSchema = [
            'id' => 'INT PRIMARY KEY',
            'name' => 'NVARCHAR(128)',
        ];

        parent::testExecuteResetSequenceWithTableNotSequence();
    }

    public function testExecuteResetSequenceWithTablePrimaryKeyComposite(): void
    {
        $this->columnSchema = [
            'id' => 'INT IDENTITY',
            'category_id' => 'INT',
            'name' => 'NVARCHAR(128)',
            'PRIMARY KEY (id, category_id)',
        ];

        parent::testExecuteResetSequenceWithTablePrimaryKeyComposite();
    }
}
