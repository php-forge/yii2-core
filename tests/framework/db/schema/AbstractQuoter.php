<?php

declare(strict_types=1);

namespace yiiunit\framework\db\schema;

use yii\db\Connection;
use yiiunit\TestCase;

use function array_reverse;

abstract class AbstractQuoter extends TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testEnsureColumnName(string $columnName, string $expected): void
    {
        $this->assertSame($expected, $this->db->getQuoter()->ensureColumnName($columnName));
    }

    public function testGetTableNameParts(string $tableName, string ...$expected): void
    {
        $this->assertSame($expected, array_reverse($this->db->getQuoter()->getTableNameParts($tableName)));
    }

    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        $this->assertSame($expected, $this->db->quoteColumnName($columnName));
    }

    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string $expectedUnQuotedColumnName
    ): void {
        $quoter = $this->db->getQuoter();

        // quote
        $quoted = $quoter->quoteSimpleColumnName($columnName);

        $this->assertSame($expectedQuotedColumnName, $quoted);

        // unquote
        $this->assertSame($expectedUnQuotedColumnName, $quoter->unquoteSimpleColumnName($quoted));
    }

    public function testQuoteSimpleColumnNameWithStartingCharacterEndingCharacterEquals(
        string $columnName,
        string $expected
    ): void {
        $quoter = $this->db->getQuoter();

        $this->assertSame($expected, $quoter->quoteSimpleColumnName($columnName));
    }

    public function testQuoteSimpleTableNameWithStartingCharacterEndingCharacterEquals(
        string $tableName,
        string $expected
    ): void {
        $quoter = $this->db->getQuoter();

        $this->assertSame($expected, $quoter->quoteSimpleTableName($tableName));
    }

    public function testQuoteTableName(string $tableName, string $expected): void
    {
        $quoter = $this->db->getQuoter();

        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteSimpleTableName($tableName));

        $this->assertSame($expected, $unQuoted);

        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteTableName($tableName));

        $this->assertSame($expected, $unQuoted);
    }

    public function testQuoteTableNameWithSchema(string $tableNamewithSchema, string $expected): void
    {
        $quoter = $this->db->getQuoter();

        $this->assertSame($expected, $quoter->quoteTableName($tableNamewithSchema));
    }

    public function testQuoteValue(string $value, string $expected): void
    {
        $quoter = $this->db->getQuoter();

        $this->assertSame($expected, $quoter->quoteValue($value));
    }

    public function testQuoteValueNotString(): void
    {
        $quoter = $this->db->getQuoter();

        $this->assertFalse($quoter->quoteValue(false));
        $this->assertTrue($quoter->quoteValue(true));
        $this->assertSame(1, $quoter->quoteValue(1));
        $this->assertSame([], $quoter->quoteValue([]));
    }
}
