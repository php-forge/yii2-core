<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\command;

use yii\base\InvalidArgumentException;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group command
 * @group execute-reset-sequence
 */
final class ExecuteResetSequenceTest extends \yiiunit\framework\db\command\AbstractExecuteResetSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        SqliteConnection::$dsn = 'sqlite:@yiiunit/runtime/yii2test.sq3';

        $this->db = SqliteConnection::getConnection();
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
        $this->columnSchema = [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'name' => 'VARCHAR(128)',
        ];

        parent::testExecuteResetSequence($tableName, $insertRows, $expectedIds, $value);
    }

    public function testExecuteResetSequenceWithTableNotSequence(): void
    {
        $this->columnSchema = [
            'id' => 'INTEGER',
            'name' => 'VARCHAR(128)',
        ];

        parent::testExecuteResetSequenceWithTableNotSequence();
    }

    public function testExecuteResetSequenceWithTablePrimaryKeyComposite(): void
    {
        $this->columnSchema = [
            'id' => 'INTEGER',
            'category_id' => 'INTEGER',
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
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                'name' => 'VARCHAR(128)',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value must be greater than '0'.");

        $this->db->createCommand()->executeResetSequence($tableName, -1);
    }
}
