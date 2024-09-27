<?php

declare(strict_types=1);

namespace yiiunit\framework\db\querybuilder;

use yii\db\{Connection, QueryInterface};

abstract class AbstractUpsert extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testUpsert(
        string $tableName,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSQL,
        array $expectedParams,
    ): void {
        $actualParams = [];

        $qb = $this->db->getQueryBuilder();
        $actualSQL = $qb->upsert($tableName, $insertColumns, $updateColumns, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }
}
