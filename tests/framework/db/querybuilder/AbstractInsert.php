<?php

declare(strict_types=1);

namespace yiiunit\framework\db\querybuilder;

use yii\db\{Connection, QueryInterface};

abstract class AbstractInsert extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testInsert(
        string $tableName,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $qb = $this->db->getQueryBuilder();

        $this->assertSame($expectedSQL, $qb->insert($tableName, $columns, $params));
        $this->assertSame($expectedParams, $params);
    }

    public function testInsertWithReturningPks(
        string $tableName,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $qb = $this->db->getQueryBuilder();

        $this->assertSame($expectedSQL, $qb->insertWithReturningPks($tableName, $columns, $params));
        $this->assertSame($expectedParams, $params);
    }
}
