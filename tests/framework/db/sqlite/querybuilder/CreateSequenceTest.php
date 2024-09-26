<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\querybuilder;

use yii\base\NotSupportedException;
use yii\db\Connection;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group query-builder
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    public function setup(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection();
    }

    public function testGenerateSQL(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('sqlite does not support creating sequences.');

        $this->db->getQueryBuilder()->createSequence('{{%T_create_sequence}}');
    }
}
