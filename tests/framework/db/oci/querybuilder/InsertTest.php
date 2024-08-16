<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\querybuilder;

use yii\db\QueryInterface;
use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group querybuilder
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\querybuilder\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::insert
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
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::insertWithReturningPks
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
