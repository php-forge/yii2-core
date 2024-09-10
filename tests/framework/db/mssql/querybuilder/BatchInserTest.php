<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group querybuilder
 * @group batchinsert
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
    public function testBatchInsert(string $table, array $columns, iterable $rows, string $expected): void
    {
        parent::testBatchInsert($table, $columns, $rows, $expected);
    }
}
