<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\schema;

use yii\base\InvalidArgumentException;
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

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\SchemaProvider::resetSequence
     */
    public function testResetSequence(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value = null
    ): void {
        parent::testResetSequence($tableName, $insertRows, $expectedIds, $value);
    }

    public function testResetSequenceWithTableNotPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'INT',
            'name' => 'VARCHAR(128)',
        ];

        parent::testResetSequenceWithTableNotPrimaryKey();
    }

    public function testResetSequenceWithTablePrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id' => 'INT NOT NULL AUTO_INCREMENT',
            'user_id' => 'INT NOT NULL',
            'name' => 'VARCHAR(128)',
            'PRIMARY KEY (id, user_id)',
        ];

        parent::testResetSequenceWithTablePrimaryKeyComposite();
    }

    public function testResetSequenceWithValueNegative(): void
    {
        $tableName = '{{%reset_sequence}}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value must be greater than '0'.");

        $this->db->getSchema()->resetSequence($tableName, -1);
    }
}
