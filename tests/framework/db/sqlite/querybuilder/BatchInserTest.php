<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\querybuilder;

use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group querybuilder
 * @group batchinsert
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
    public function testBatchInsert(string $table, array $columns, iterable $rows, string $expected): void
    {
        parent::testBatchInsert($table, $columns, $rows, $expected);
    }
}
