<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\querybuilder;

use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group query-builder
 * @group batch-insert
 */
final class BatchInserTest extends \yiiunit\framework\db\querybuilder\AbstractBatchInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QueryBuilderProvider::batchInsert
     */
    public function testBatchInsert(string $tableName, array $columns, iterable $rows, string $expected): void
    {
        parent::testBatchInsert($tableName, $columns, $rows, $expected);
    }
}
