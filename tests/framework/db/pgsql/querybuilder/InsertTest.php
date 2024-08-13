<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\querybuilder;

use yii\db\QueryInterface;
use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group querybuilder
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\querybuilder\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection(true, dirname(__DIR__) . '/fixture/insert.sql');
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\QueryBuilderProvider::insert
     */
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\QueryBuilderProvider::insertWithReturningPks
     */
    public function testInsertWithReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsertWithReturningPks($table, $columns, $params, $expectedSQL, $expectedParams);
    }
}
