<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\querybuilder;

use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group querybuilder
 * @group batchinsert
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
    public function testBatchInsert(string $table, array $columns, iterable $rows, string $expected): void
    {
        parent::testBatchInsert($table, $columns, $rows, $expected);
    }
}
