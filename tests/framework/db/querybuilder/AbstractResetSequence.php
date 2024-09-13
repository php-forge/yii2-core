<?php

declare(strict_types=1);

namespace yiiunit\framework\db\querybuilder;

use yii\db\Connection;

abstract class AbstractResetSequence extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testResetSequence(string $table, string $columnPK, int|null $value, string $expected): void
    {
        $qb = $this->db->getQueryBuilder();

        $this->assertSame($expected, $qb->resetSequence($table, $columnPK, $value));
    }
}
