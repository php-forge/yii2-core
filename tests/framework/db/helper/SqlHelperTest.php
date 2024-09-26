<?php

declare(strict_types=1);

namespace yiiunit\framework\db\helper;

use yii\db\SqlHelper;

/**
 * @group sql-helper
 */
final class SqlHelperTest extends \yiiunit\TestCase
{
    public function testAddSuffix(): void
    {
        $this->assertSame('example_SEQ', SqlHelper::addSuffix('example', '_SEQ'));
    }

    public function testAddSuffixWithAlreadyPresent(): void
    {
        $result = SqlHelper::addSuffix('example_SEQ', '_SEQ');

        $this->assertSame('example_SEQ', $result);

        $resultCaseInsensitive = SqlHelper::addSuffix('example_seq', '_SEQ');

        $this->assertSame('example_SEQ', $resultCaseInsensitive);
    }

    public function testAddSuffixWithEmpty(): void
    {
        $this->assertEquals('example', SqlHelper::addSuffix('example', ''));
    }

    public function testCleanSql(): void
    {
        $dirtySQL = <<<SQL

        SELECT *
          FROM users

         WHERE id = 1

        SQL;

        $expectedCleanSQL = "SELECT *\n  FROM users\n WHERE id = 1";

        $this->assertEquals($expectedCleanSQL, SqlHelper::cleanSql($dirtySQL));
    }

    /**
     * @dataProvider yiiunit\framework\db\provider\SqlHelperProvider::readQuery
     */
    public function testIsReadQuery(string $sql, bool $expected)
    {
        $this->assertEquals($expected, SqlHelper::isReadQuery($sql));
    }
}
