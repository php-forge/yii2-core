<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\schema;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group schema
 */
final class SchemaTest extends \yiiunit\framework\db\schema\AbstractSchema
{
    protected array $columnsSchema = [
        'id' => 'INT IDENTITY PRIMARY KEY',
        'name' => 'NVARCHAR(128)',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    public function testGetSequenceName(): void
    {
        $tableName = '{{%sequence_test}}';
        $sequenceName = 'sequence_test';

        if ($this->db->getSchema()->getSequenceName($sequenceName)) {
            $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

            $this->assertSame(0, $result);
        }

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->createSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $this->assertSame($sequenceName . '_SEQ', $this->db->getSchema()->getSequenceName($sequenceName));

        $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }

    public function testGetSequenceNameWithSuffixSEQ(): void
    {
        $tableName = '{{%sequence_test}}';
        $sequenceName = 'sequence_test_SEQ';

        if ($this->db->getSchema()->getSequenceName($sequenceName)) {
            $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

            $this->assertSame(0, $result);
        }

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->createSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $this->assertSame($sequenceName, $this->db->getSchema()->getSequenceName($sequenceName));

        $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\SchemaProvider::resetSequence
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
            'name' => 'NVARCHAR(128)',
        ];

        parent::testResetSequenceWithTableNotPrimaryKey();
    }

    public function testResetSequenceWithTablePrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id' => 'INT IDENTITY',
            'user_id' => 'INT',
            'name' => 'NVARCHAR(128)',
            'PRIMARY KEY (id, user_id)',
        ];

        parent::testResetSequenceWithTablePrimaryKeyComposite();
    }
}
