<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\command;

use yii\db\Connection;
use yii\db\Expression;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group command
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    public function setup(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection(true);
    }

    public function testCreateSequence(): void
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

    private function ensureNoTable(string $tableName): void
    {
        if ($this->db->hasTable($tableName)) {
            $this->db->createCommand()->dropTable($tableName)->execute();
            $this->assertFalse($this->db->hasTable($tableName));
        }
    }
}
