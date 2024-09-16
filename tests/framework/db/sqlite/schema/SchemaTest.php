<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\schema;

use yii\base\InvalidArgumentException;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group schema
 */
final class SchemaTest extends \yiiunit\framework\db\schema\AbstractSchema
{
    protected array $columnsSchema = [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'name' => 'TEXT',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\SchemaProvider::resetSequence
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
            'id' => 'INTEGER',
            'name' => 'TEXT',
        ];

        parent::testResetSequenceWithTableNotPrimaryKey();
    }

    public function testResetSequenceWithValueNegative(): void
    {
        $tableName = '{{%reset_sequence}}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value must be greater than '0'.");

        $this->db->getSchema()->resetSequence($tableName, -1);
    }
}
