<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\schema;

use yii\base\NotSupportedException;
use yii\db\Connection;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group schema
 * @group sequence
 */
final class SequenceTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection();
    }

    public function testGetSequenceInfo(): void
    {
        $sequence = '{{%T_sequence}}';

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('sqlite does not support getting sequence information.');

        $this->db->getSchema()->getSequenceInfo($sequence);
    }
}
