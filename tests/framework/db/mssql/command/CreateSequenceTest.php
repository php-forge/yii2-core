<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\command;

use yii\db\Expression;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group command
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\framework\db\command\AbstractCreateSequence
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\CommandProvider::createSequence
     */
    public function testCreateSequence(string $table, int $start, int $increment, array $options): void
    {
        parent::testCreateSequence($table, $start, $increment, $options);
    }

    public function testUseSequence(): void
    {
        $tableName = '{{%T_create_sequence}}';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'INT',
                'name' => 'NVARCHAR(128)',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $sequenceName = 'T_create_sequence';

        $result = $this->db->createCommand()->createSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->insert(
            $tableName,
            ['id' => new Expression("NEXT VALUE FOR {$sequenceName}_SEQ"), 'name' => 'test']
        )->execute();

        $this->assertSame(1, $result);

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT id
            FROM $tableName
            SQL
        )->queryColumn();

        $this->assertSame(['1'], $ids);

        $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $this->ensureNoTable($tableName);
    }

    public function testUseSequenceWithOptions(): void
    {
        $tableName = '{{%T_create_sequence_options}}';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'INT',
                'name' => 'NVARCHAR(128)',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $sequenceName = 'T_create_sequence_options';

        $result = $this->db->createCommand()->createSequence(
            $sequenceName,
            100,
            2,
            [
                'cache' => 10,
                'cycle' => true,
            ]
        )->execute();

        $this->assertSame(0, $result);

        $idValue = new Expression("NEXT VALUE FOR {$sequenceName}_SEQ");

        $result = $this->db->createCommand()->insert($tableName, ['id' => $idValue, 'name' => 'test'])->execute();

        $this->assertSame(1, $result);

        $result = $this->db->createCommand()->insert($tableName, ['id' => $idValue, 'name' => 'test'])->execute();

        $this->assertSame(1, $result);

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT id
            FROM $tableName
            SQL
        )->queryColumn();

        $this->assertSame(['100', '102'], $ids);

        $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $this->ensureNoTable($tableName);
    }
}
