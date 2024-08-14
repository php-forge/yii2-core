<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use yii\db\Connection;
use yii\db\Query;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group querybuilder
 * @group build
 */
final class BuildTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection(false);
    }

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testBuildOrderByAndLimit(): void
    {
        $expectedQueryParams = [];
        $expectedQuery = <<<SQL
        SELECT [id] FROM [example] ORDER BY (SELECT NULL) OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY
        SQL;

        $qb = $this->db->getQueryBuilder();

        $query = new Query();
        $query->select('id')->from('example')->limit(10)->offset(5);
        [$actualQuery, $actualQueryParams] = $this->db->getQueryBuilder()->build($query);

        $this->assertSame($expectedQuery, $actualQuery);
        $this->assertSame($expectedQueryParams, $actualQueryParams);
    }

    public function testBuildOrderByAndLimitWithLimit()
    {
        $expectedQueryParams = [];
        $expectedQuery = <<<SQL
        SELECT [id] FROM [example] ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
        SQL;

        $qb = $this->db->getQueryBuilder();

        $query = new Query();
        $query->select('id')->from('example')->limit(10);

        [$actualQuery, $actualQueryParams] = $qb->build($query);

        $this->assertSame($expectedQuery, $actualQuery);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testBuildOrderByAndLimitWithOffset()
    {
        $expectedQueryParams = [];
        $expectedQuery = <<<SQL
        SELECT [id] FROM [example] ORDER BY (SELECT NULL) OFFSET 10 ROWS
        SQL;

        $query = new Query();
        $query->select('id')->from('example')->offset(10);

        $qb = $this->db->getQueryBuilder();

        [$actualQuery, $actualQueryParams] = $qb->build($query);

        $this->assertSame($expectedQuery, $actualQuery);
        $this->assertSame($expectedQueryParams, $actualQueryParams);
    }
}
