<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\querybuilder;

use yii\base\NotSupportedException;
use yii\db\Connection;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group querybuilder
 * @group drop-sequence
 */
final class DropSequenceTest extends \yiiunit\TestCase
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
        $this->expectExceptionMessage('yii\db\sqlite\QueryBuilder::dropSequence is not supported by SQLite.');

        $this->db->getQueryBuilder()->dropSequence('test_sequence');
    }
}
