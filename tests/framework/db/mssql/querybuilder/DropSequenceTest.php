<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group query-builder
 * @group drop-sequence
 */
final class DropSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractDropSequence
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\QueryBuilderProvider::dropSequence
     */
    public function testGenerateSQL(string $sequence, string $expectedSQL): void
    {
        parent::testGenerateSQL($sequence, $expectedSQL);
    }
}
