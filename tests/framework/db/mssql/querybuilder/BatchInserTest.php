<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group query-builder
 * @group batch-insert
 */
final class BatchInserTest extends \yiiunit\framework\db\querybuilder\AbstractBatchInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\QueryBuilderProvider::batchInsert
     */
    public function testBatchInsert(string $tableName, array $columns, iterable $rows, string $expected): void
    {
        parent::testBatchInsert($tableName, $columns, $rows, $expected);
    }
}
