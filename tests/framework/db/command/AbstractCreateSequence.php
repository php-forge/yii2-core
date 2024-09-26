<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command;

use yii\db\Connection;
use yiiunit\support\DbHelper;

abstract class AbstractCreateSequence extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    protected function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testCreateSequence(
        string $sequence,
        int $start,
        int $increment,
        array $options,
        array $expectedSequenceInfo
    ): void {
        DbHelper::ensureNoTable($this->db, $sequence);

        $result = $this->db->createCommand()->createSequence($sequence, $start, $increment, $options)->execute();

        $this->assertSame(0, $result);

        $sequenceInfo = $this->db->getSchema()->getSequenceInfo($sequence);

        $this->assertSame($expectedSequenceInfo, $sequenceInfo);

        $result = $this->db->createCommand()->dropSequence($sequence)->execute();

        $this->assertSame(0, $result);

        DbHelper::ensureNoTable($this->db, $sequence);
    }
}
