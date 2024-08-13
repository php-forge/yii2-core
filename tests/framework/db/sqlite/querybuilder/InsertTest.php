<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\querybuilder;

use yii\base\NotSupportedException;
use yii\db\QueryInterface;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group querybuilder
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\querybuilder\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QueryBuilderProvider::insert
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
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QueryBuilderProvider::insertWithReturningPks
     */
    public function testInsertWithReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'yii\db\sqlite\QueryBuilder::insertWithReturningPks() is not supported by SQLite.'
        );

        parent::testInsertWithReturningPks($table, $columns, $params, $expectedSQL, $expectedParams);
    }
}
