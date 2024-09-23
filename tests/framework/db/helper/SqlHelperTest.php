<?php

declare(strict_types=1);

namespace yiiunit\framework\db\helper;

use yii\db\SqlHelper;

/**
 * @group sqlhelper
 */
final class SqlHelperTest extends \yiiunit\TestCase
{
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
