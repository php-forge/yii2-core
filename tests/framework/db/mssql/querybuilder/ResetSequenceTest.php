<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group querybuilder
 * @group reset-sequence
 */
final class ResetSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractResetSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\QueryBuilderProvider::resetSequence
     */
    public function testResetSequence(string $tableName, string $columnPK, int|null $value, string $expected): void
    {
        parent::testResetSequence($tableName, $columnPK, $value, $expected);
    }
}
