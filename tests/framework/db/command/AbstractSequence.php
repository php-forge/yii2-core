<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command;

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

    public function testExecuteCreateSequence(
        string $table,
        int $start,
        int $increment,
        array $options
    ): void {
        $sequenceName = $table . '_SEQ';

        if ($this->db->getSchema()->getSequenceName($sequenceName) !== null) {
            $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createSequence($table, $start, $increment, $options)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->dropSequence($sequenceName)->execute();

        $this->assertSame(0, $result);
    }
}
