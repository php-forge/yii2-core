<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\querybuilder;

use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group query-builder
 * @group batch-insert
 */
final class BatchInserTest extends \yiiunit\framework\db\querybuilder\AbstractBatchInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\QueryBuilderProvider::batchInsert
     */
    public function testBatchInsert(string $tableName, array $columns, iterable $rows, string $expected): void
    {
        parent::testBatchInsert($tableName, $columns, $rows, $expected);
    }
}
