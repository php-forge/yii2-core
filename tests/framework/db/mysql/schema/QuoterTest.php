<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql;

use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group schema
 * @group quoter
 */
final class QuoterTest extends \yiiunit\framework\db\schema\AbstractQuoter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QuoterProvider::ensureColumnName
     */
    public function testEnsureColumnName(string $columnName, string $expected): void
    {
        parent::testEnsureColumnName($columnName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QuoterProvider::tableNameParts
     */
    public function testGetTableNameParts(string $tableName, string ...$expected): void
    {
        parent::testGetTableNameParts($tableName, ...$expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QuoterProvider::columnNames
     */
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        parent::testQuoteColumnName($columnName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QuoterProvider::simpleColumnNames
     */
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string $expectedUnQuotedColumnName
    ): void {
        parent::testQuoteSimpleColumnName($columnName, $expectedQuotedColumnName, $expectedUnQuotedColumnName);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QuoterProvider::columnNameWithStartingEndingCharacter
     */
    public function testQuoteSimpleColumnNameWithStartingCharacterEndingCharacterEquals(
        string $columnName,
        string $expected
    ): void {
        parent::testQuoteSimpleColumnNameWithStartingCharacterEndingCharacterEquals($columnName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QuoterProvider::tableNameWithStartingEndingCharacter
     */
    public function testQuoteSimpleTableNameWithStartingCharacterEndingCharacterEquals(
        string $tableName,
        string $expected
    ): void {
        parent::testQuoteSimpleTableNameWithStartingCharacterEndingCharacterEquals($tableName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QuoterProvider::simpleTableNames
     */
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        parent::testQuoteTableName($tableName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QuoterProvider::tableNameWithSchema
     */
    public function testQuoteTableNameWithSchema(string $tableNamewithSchema, string $expected): void
    {
        parent::testQuoteTableNameWithSchema($tableNamewithSchema, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QuoterProvider::stringValues
     */
    public function testQuoteValue(string $value, string $expected): void
    {
        parent::testQuoteValue($value, $expected);
    }
}