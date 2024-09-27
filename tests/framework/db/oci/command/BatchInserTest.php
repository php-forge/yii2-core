<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\command;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group command
 * @group batch-insert
 */
final class BatchInserTest extends \yiiunit\framework\db\command\AbstractBatchInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\CommandProvider::batchInsert
     */
    public function testBatchInsert(
        string $tableName,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        parent::testBatchInsert($tableName, $columns, $values, $expected, $expectedParams, $insertedRow);
    }
}
