<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\command;

use yii\db\Expression;
use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group command
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\command\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection(true);
    }

    public function testInsertUsingSequence(): void
    {
        $tableName = '{{%T_insert_using_sequence}}';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'INT',
                'name' => 'VARCHAR(128)',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $sequenceName = 'T_insert_using_sequence';

        $result = $this->db->createCommand()->createSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->insert(
            $tableName,
            ['id' => new Expression('NEXTVAL(\'"' . $sequenceName . '_SEQ"\')'), 'name' => 'test']
        )->execute();

        $this->assertSame(1, $result);

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT id
            FROM $tableName
            SQL
        )->queryColumn();

        $this->assertSame([1], $ids);

        $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $this->ensureNoTable($tableName);
    }

    public function testInsertUsingSequenceWithOptions(): void
    {
        $tableName = '{{%T_insert_using_sequence_options}}';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'INT',
                'name' => 'VARCHAR(128)',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $sequenceName = 'T_insert_using_sequence_options';

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

        $idValue = new Expression('NEXTVAL(\'"' . $sequenceName . '_SEQ"\')');

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

        $this->assertSame([100, 102], $ids);

        $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $this->ensureNoTable($tableName);
    }
}
