<?php

declare(strict_types=1);

namespace yiiunit\framework\db\querybuilder;

use yii\db\Connection;
use yii\db\QueryInterface;
use yiiunit\TestCase;

abstract class AbstractInsert extends TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $qb = $this->db->getQueryBuilder();

        $this->assertSame($expectedSQL, $qb->insert($table, $columns, $params));
        $this->assertSame($expectedParams, $params);
    }

    public function testInsertWithReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $qb = $this->db->getQueryBuilder();

        $this->assertSame($expectedSQL, $qb->insertWithReturningPks($table, $columns, $params));
        $this->assertSame($expectedParams, $params);
    }
}
