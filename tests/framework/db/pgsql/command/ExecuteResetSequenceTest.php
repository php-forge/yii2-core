<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\command;

use yii\base\InvalidArgumentException;
use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group command
 * @group execute-reset-sequence
 */
final class ExecuteResetSequenceTest extends \yiiunit\framework\db\command\AbstractExecuteResetSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\CommandProvider::executeResetSequence
     */
    public function testExecuteResetSequence(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value = null
    ): void {
        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY',
            default => 'SERIAL PRIMARY KEY',
        };

        $this->columnSchema = [
            'id' => $columnId,
            'name' => 'VARCHAR(128)',
        ];

        parent::testExecuteResetSequence($tableName, $insertRows, $expectedIds, $value);
    }

    public function testExecuteResetSequenceWithTableNotSequence(): void
    {
        $this->columnSchema = [
            'id' => 'INT PRIMARY KEY',
            'name' => 'VARCHAR(128)',
        ];

        parent::testExecuteResetSequenceWithTableNotSequence();
    }

    public function testExecuteResetSequenceWithTablePrimaryKeyComposite(): void
    {
        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };

        $this->columnSchema = [
            'id' => $columnId,
            'category_id' => 'INT',
            'name' => 'VARCHAR(128)',
            'PRIMARY KEY (id, category_id)',
        ];

        parent::testExecuteResetSequenceWithTablePrimaryKeyComposite();
    }

    public function testExecuteResetSequenceWithValueNegative(): void
    {
        $tableName = '{{%reset_sequence}}';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY',
                'name' => 'VARCHAR(128)',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value must be greater than '0'.");

        $this->db->createCommand()->executeResetSequence($tableName, -1);
    }
}
