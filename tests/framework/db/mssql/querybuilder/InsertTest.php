<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use yii\db\QueryInterface;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group query-builder
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\querybuilder\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\QueryBuilderProvider::insert
     */
    public function testInsert(
        string $tableName,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsert($tableName, $columns, $params, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\QueryBuilderProvider::insertWithReturningPks
     */
    public function testInsertWithReturningPks(
        string $tableName,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsertWithReturningPks($tableName, $columns, $params, $expectedSQL, $expectedParams);
    }
}
