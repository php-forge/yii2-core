<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\querybuilder;

use yii\base\NotSupportedException;
use yii\db\Connection;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group query-builder
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    public function setup(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    public function testGenerateSQL(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('mysql does not support creating sequences.');

        $this->db->getQueryBuilder()->createSequence('{{%T_create_sequence}}');
    }
}
