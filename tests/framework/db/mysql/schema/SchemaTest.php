<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\schema;

use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group schema
 */
final class SchemaTest extends \yiiunit\framework\db\schema\AbstractSchema
{
    protected array $columnsSchema = [
        'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
        'name' => 'VARCHAR(128)',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    public function testGetSequenceInfo(): void
    {
        $tableName = '{{%sequence_test}}';

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('mysql does not support getting sequence name.');

        $this->db->getSchema()->getSequenceInfo($tableName);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\SchemaProvider::resetAutoIncrementPK
     */
    public function testResetAutoIncrementPK(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value = null
    ): void {
        parent::testResetAutoIncrementPK($tableName, $insertRows, $expectedIds, $value);
    }

    public function testResetAutoIncrementPKWithTableNotPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'INT',
            'name' => 'VARCHAR(128)',
        ];

        parent::testResetAutoIncrementPKWithTableNotPrimaryKey();
    }

    public function testResetAutoIncrementPKWithTablePrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id' => 'INT NOT NULL AUTO_INCREMENT',
            'user_id' => 'INT NOT NULL',
            'name' => 'VARCHAR(128)',
            'PRIMARY KEY (id, user_id)',
        ];

        parent::testResetAutoIncrementPKWithTablePrimaryKeyComposite();
    }

    public function testResetAutoIncrementPKWithValueNegative(): void
    {
        $tableName = '{{%reset_auto_increment_pk}}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value must be greater than '0'.");

        $this->db->getSchema()->resetAutoIncrementPK($tableName, -1);
    }
}
