<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\schema;

use yii\base\NotSupportedException;
use yii\db\Connection;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mssql
 * @group schema
 * @group sequence
 */
final class SequenceTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    public function testGetSequenceInfo(): void
    {
        $sequence = '{{%T_sequence}}';

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('mysql does not support getting sequence information.');

        $this->db->getSchema()->getSequenceInfo($sequence);
    }
}
