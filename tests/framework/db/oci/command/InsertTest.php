<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\command;

use yii\db\Expression;
use yiiunit\support\{DbHelper, OciConnection};

/**
 * @group db
 * @group oci
 * @group command
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\command\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection(true);
    }

    public function testInsertUsingSequence(): void
    {
        $tableName = '{{%T_insert_using_sequence}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'NUMBER(10)',
                'name' => 'VARCHAR2(255)',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $sequence = 'T_insert_using_sequence';

        $result = $this->db->createCommand()->createSequence($sequence)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->insert(
            $tableName,
            ['id' => new Expression("{$this->db->quoteTableName($sequence .'_SEQ')}.nextval"), 'name' => 'test']
        )->execute();

        $this->assertSame(1, $result);

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT [[id]]
            FROM $tableName
            SQL
        )->queryColumn();

        $this->assertSame(['1'], $ids);

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
                'id' => 'NUMBER(10)',
                'name' => 'VARCHAR2(255)',
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

        $idValue = new Expression("{$this->db->quoteTableName($sequence .'_SEQ')}.nextval");

        $result = $this->db->createCommand()->insert($tableName, ['id' => $idValue, 'name' => 'test'])->execute();

        $this->assertSame(1, $result);

        $result = $this->db->createCommand()->insert($tableName, ['id' => $idValue, 'name' => 'test'])->execute();

        $this->assertSame(1, $result);

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT [[id]]
            FROM $tableName
            SQL
        )->queryColumn();

        $this->assertSame(['100', '102'], $ids);

        $result = $this->db->createCommand()->dropSequence($sequence)->execute();

        $this->assertSame(0, $result);

        DbHelper::ensureNoTable($this->db, $tableName);
    }
}
