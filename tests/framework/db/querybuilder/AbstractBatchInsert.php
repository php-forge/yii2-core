<?php

declare(strict_types=1);

namespace yiiunit\framework\db\querybuilder;

use Generator;
use yii\db\Connection;

abstract class AbstractBatchInsert extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testBatchInsert(string $tableName, array $columns, iterable|Generator $rows, string $expected): void
    {
        $qb = $this->db->getQueryBuilder();

        $this->assertSame($expected, $qb->batchInsert($tableName, $columns, $rows));
    }
}
