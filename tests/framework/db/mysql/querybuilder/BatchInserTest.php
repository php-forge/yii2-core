<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\querybuilder;

use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group querybuilder
 * @group batchinsert
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
    public function testBatchInsert(string $table, array $columns, iterable $rows, string $expected): void
    {
        parent::testBatchInsert($table, $columns, $rows, $expected);
    }
}
