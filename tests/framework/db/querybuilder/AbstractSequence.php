<?php

declare(strict_types=1);

namespace yiiunit\framework\db\querybuilder;

use yii\db\Connection;

abstract class AbstractSequence extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    protected function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testCreateSequence(
        string $table,
        int $start,
        int $increment,
        array $options,
        string $expectedSQL
    ): void {
        $sql = $this->db->queryBuilder->createSequence($table, $start, $increment, $options);

        $this->assertSame($expectedSQL, $sql);
    }
}
