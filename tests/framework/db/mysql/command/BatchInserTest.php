<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\command;

use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group command
 * @group batchinsert
 */
final class BatchInserTest extends \yiiunit\framework\db\command\AbstractBatchInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\CommandProvider::batchInsert
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        parent::testBatchInsert($table, $columns, $values, $expected, $expectedParams, $insertedRow);
    }
}
