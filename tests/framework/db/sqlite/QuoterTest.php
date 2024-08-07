<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite;

use yii\db\Connection;
use yiiunit\framework\db\AbstractQuoter;
use yiiunit\support\SqliteConnection;

use function array_reverse;

/**
 * @group db
 * @group sqlite
 * @group quoter
 */
final class QuoterTest extends AbstractQuoter
{
    protected string $driverName = 'sqlite';

    public function getConnection(bool $fixture = false): Connection
    {
        return SqliteConnection::getConnection($fixture);
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QuoterProvider::ensureColumnName
     */
    public function testEnsureColumnName(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->ensureColumnName($columnName));
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QuoterProvider::tableNameParts
     */
    public function testGetTableNameParts(string $tableName, string ...$expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, array_reverse($db->getQuoter()->getTableNameParts($tableName)));
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QuoterProvider::columnNames
     */
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->quoteColumnName($columnName));
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QuoterProvider::simpleColumnNames
     */
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string $expectedUnQuotedColumnName
    ): void {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $quoted = $quoter->quoteSimpleColumnName($columnName);

        $this->assertSame($expectedQuotedColumnName, $quoted);

        $unQuoted = $quoter->unquoteSimpleColumnName($quoted);

        $this->assertSame($expectedUnQuotedColumnName, $unQuoted);
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QuoterProvider::columnNameWithStartingEndingCharacter
     */
    public function testQuoteSimpleColumnNameWithStartingCharacterEndingCharacterEquals(
        string $columnName,
        string $expected
    ): void {
        $quoter = $this->getConnection()->getQuoter();

        $this->assertSame($expected, $quoter->quoteSimpleColumnName($columnName));
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QuoterProvider::tableNameWithStartingEndingCharacter
     */
    public function testQuoteSimpleTableNameWithStartingCharacterEndingCharacterEquals(
        string $tableName,
        string $expected
    ): void {
        $quoter = $this->getConnection()->getQuoter();

        $this->assertSame($expected, $quoter->quoteSimpleTableName($tableName));
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QuoterProvider::simpleTableNames
     */
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteSimpleTableName($tableName));

        $this->assertSame($expected, $unQuoted);

        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteTableName($tableName));

        $this->assertSame($expected, $unQuoted);
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QuoterProvider::tableNameWithSchema
     */
    public function testQuoteTableNameWithSchema(string $tableNamewithSchema, string $expected): void
    {
        $quoter = $this->getConnection()->getQuoter();

        $this->assertSame($expected, $quoter->quoteTableName($tableNamewithSchema));
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QuoterProvider::stringValues
     */
    public function testQuoteValue(string $value, string $expected): void
    {
        $quoter = $this->getConnection()->getQuoter();

        $this->assertSame($expected, $quoter->quoteValue($value));
    }
}