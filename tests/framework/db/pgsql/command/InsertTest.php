<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\command;

use yii\db\Expression;
use yiiunit\support\{DbHelper, PgsqlConnection};

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

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'INT',
                'name' => 'VARCHAR(128)',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $sequence = 'T_insert_using_sequence';

        $result = $this->db->createCommand()->createSequence($sequence)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->insert(
            $tableName,
            ['id' => new Expression('NEXTVAL(\'"' . $sequence . '_SEQ"\')'), 'name' => 'test']
        )->execute();

        $this->assertSame(1, $result);

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT id
            FROM $tableName
            SQL
        )->queryColumn();

        $this->assertSame([1], $ids);

        $result = $this->db->createCommand()->dropSequence($sequence)->execute();

        $this->assertSame(0, $result);

        DbHelper::ensureNoTable($this->db, $tableName);
    }

    public function testInsertUsingSequenceWithOptions(): void
    {
        $tableName = '{{%T_insert_using_sequence_options}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'INT',
                'name' => 'VARCHAR(128)',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $sequence = 'T_insert_using_sequence_options';

        $result = $this->db->createCommand()->createSequence(
            $sequence,
            100,
            2,
            [
                'cache' => 10,
                'cycle' => true,
            ]
        )->execute();

        $this->assertSame(0, $result);

        $idValue = new Expression('NEXTVAL(\'"' . $sequence . '_SEQ"\')');

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

        $result = $this->db->createCommand()->dropSequence($sequence)->execute();

        $this->assertSame(0, $result);

        DbHelper::ensureNoTable($this->db, $tableName);
    }
}
