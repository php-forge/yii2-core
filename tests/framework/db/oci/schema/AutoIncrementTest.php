<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\schema;

use yii\base\InvalidArgumentException;
use yiiunit\support\{DbHelper, OciConnection};

/**
 * @group db
 * @group oci
 * @group schema
 * @group auto-increment
 */
final class AutoIncrementTest extends \yiiunit\framework\db\schema\AbstractAutoIncrement
{
    protected array $columnsSchema = [
        'id' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY',
        'name' => 'VARCHAR2(128)',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\SchemaProvider::resetAutoIncrementPK
     */
    public function testResetAutoIncrementPK(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value = null
    ): void {
        parent::testResetAutoIncrementPK($tableName, $insertRows, $expectedIds, $value);
    }

    public function testResetAutoIncrementWithNotColumnAutoIncrementAndNotTrigger(): void
    {
        $tableName = '{{%T_reset_auto_increment_pk}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'NUMBER(10)',
                'name' => 'VARCHAR2(128)',
                'PRIMARY KEY ([[id]])',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Sequence name for table 'T_reset_auto_increment_pk' not found.");

        $this->db->getSchema()->resetAutoIncrementPK($tableName, 7);
    }

    public function testResetAutoIncrementPKWithTableNotPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'NUMBER(10)',
            'name' => 'VARCHAR2(128)',
        ];

        parent::testResetAutoIncrementPKWithTableNotPrimaryKey();
    }

    public function testResetAutoIncrementPKWithTablePrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL',
            'user_id' => 'NUMBER(10)',
            'name' => 'VARCHAR2(128)',
            'PRIMARY KEY ([[id]], [[user_id]])',
        ];

        parent::testResetAutoIncrementPKWithTablePrimaryKeyComposite();
    }

    public function testResetAutoIncrementPKWithTrigger(): void
    {
        $tableName = '{{%profile}}';

        $this->db = OciConnection::getConnection(true);
        $result = $this->db->getSchema()->resetAutoIncrementPK($tableName, 7);

        $this->assertSame(7, $result);

        foreach (range(8, 10) as $i) {
            $result = $this->db->createCommand()->insert($tableName, ['description' => 'description_' . $i])->execute();

            $this->assertSame(1, $result);
        }

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT [[id]] FROM {$tableName}
            SQL
        )->queryColumn();

        $this->assertEquals([1, 2, 7, 8, 9], $ids);

        DbHelper::ensureNoTable($this->db, $tableName);
    }
}