<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group schema
 * @group quoter
 */
final class QuoterTest extends \yiiunit\framework\db\schema\AbstractQuoter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QuoterProvider::ensureColumnName
     */
    public function testEnsureColumnName(string $columnName, string $expected): void
    {
        parent::testEnsureColumnName($columnName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QuoterProvider::tableNameParts
     */
    public function testGetTableNameParts(string $tableName, string ...$expected): void
    {
        parent::testGetTableNameParts($tableName, ...$expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QuoterProvider::columnNames
     */
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        parent::testQuoteColumnName($columnName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QuoterProvider::simpleColumnNames
     */
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string $expectedUnQuotedColumnName
    ): void {
        parent::testQuoteSimpleColumnName($columnName, $expectedQuotedColumnName, $expectedUnQuotedColumnName);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QuoterProvider::columnNameWithStartingEndingCharacter
     */
    public function testQuoteSimpleColumnNameWithStartingCharacterEndingCharacterEquals(
        string $columnName,
        string $expected
    ): void {
        parent::testQuoteSimpleColumnNameWithStartingCharacterEndingCharacterEquals($columnName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QuoterProvider::tableNameWithStartingEndingCharacter
     */
    public function testQuoteSimpleTableNameWithStartingCharacterEndingCharacterEquals(
        string $tableName,
        string $expected
    ): void {
        parent::testQuoteSimpleTableNameWithStartingCharacterEndingCharacterEquals($tableName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QuoterProvider::simpleTableNames
     */
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        parent::testQuoteTableName($tableName, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QuoterProvider::tableNameWithSchema
     */
    public function testQuoteTableNameWithSchema(string $tableNamewithSchema, string $expected): void
    {
        parent::testQuoteTableNameWithSchema($tableNamewithSchema, $expected);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QuoterProvider::stringValues
     */
    public function testQuoteValue(string $value, string $expected): void
    {
        parent::testQuoteValue($value, $expected);
    }
}
