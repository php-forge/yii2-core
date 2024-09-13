<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\querybuilder;

use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group querybuilder
 * @group reset-sequence
 */
final class ResetSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractResetSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QueryBuilderProvider::resetSequence
     */
    public function testResetSequence(string $tableName, string $columnPK, int|null $value, string $expected): void
    {
        parent::testResetSequence($tableName, $columnPK, $value, $expected);
    }
}
