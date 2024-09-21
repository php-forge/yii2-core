<?php

declare(strict_types=1);

namespace yiiunit\framework\db\querybuilder;

use yii\db\Connection;

abstract class AbstractCreateSequence extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testGenerateSQL(
        string $sequenceName,
        int $start,
        int $increment,
        array $options,
        string $expectedSQL
    ): void {
        $qb = $this->db->getQueryBuilder();

        $this->assertSame($expectedSQL, $qb->createSequence($sequenceName, $start, $increment, $options));
    }
}
