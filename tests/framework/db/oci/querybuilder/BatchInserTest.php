<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\querybuilder;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group query-builder
 * @group batch-insert
 */
final class BatchInserTest extends \yiiunit\framework\db\querybuilder\AbstractBatchInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::batchInsert
     */
    public function testBatchInsert(string $tableName, array $columns, iterable $rows, string $expected): void
    {
        parent::testBatchInsert($tableName, $columns, $rows, $expected);
    }
}
