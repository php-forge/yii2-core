<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\querybuilder;

use yii\base\NotSupportedException;
use yii\db\QueryInterface;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group query-builder
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\querybuilder\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QueryBuilderProvider::insert
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\QueryBuilderProvider::insertWithReturningPks
     */
    public function testInsertWithReturningPks(
        string $tableName,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'yii\db\mysql\QueryBuilder::insertWithReturningPks is not supported by MySQL/MariaDB.'
        );

        parent::testInsertWithReturningPks($tableName, $columns, $params, $expectedSQL, $expectedParams);
    }
}
