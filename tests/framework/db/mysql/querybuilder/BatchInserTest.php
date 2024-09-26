<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\querybuilder;

use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group query-builder
 * @group batch-insert
 */
final class BatchInserTest extends \yiiunit\framework\db\querybuilder\AbstractBatchInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QueryBuilderProvider::batchInsert
     */
    public function testBatchInsert(string $tableName, array $columns, iterable $rows, string $expected): void
    {
        parent::testBatchInsert($tableName, $columns, $rows, $expected);
    }
}
