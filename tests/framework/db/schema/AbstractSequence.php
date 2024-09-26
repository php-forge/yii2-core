<?php

declare(strict_types=1);

namespace yiiunit\framework\db\schema;

use yii\db\Connection;

/**
 * @group db
 * @group schema
 * @group sequence
 */
abstract class AbstractSequence extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testGetSequenceInfoWithSequenceNotExist(): void
    {
        $this->assertFalse($this->db->getSchema()->getSequenceInfo('{{%T_not_exists}}'));
    }
}
