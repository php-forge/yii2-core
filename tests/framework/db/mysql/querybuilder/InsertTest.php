<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\querybuilder;

use yii\base\NotSupportedException;
use yii\db\QueryInterface;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group querybuilder
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\querybuilder\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection(true, dirname(__DIR__) . '/fixture/insert.sql');
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QueryBuilderProvider::insert
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\QueryBuilderProvider::insertWithReturningPks
     */
    public function testInsertWithReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('yii\db\mysql\QueryBuilder::insertWithReturningPks is not supported by Mysql.');

        parent::testInsertWithReturningPks($table, $columns, $params, $expectedSQL, $expectedParams);
    }
}
