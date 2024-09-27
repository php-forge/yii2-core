<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\querybuilder;

use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group query-builder
 * @group drop-sequence
 */
final class DropSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractDropSequence
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\QueryBuilderProvider::dropSequence
     */
    public function testGenerateSQL(string $sequence, string $expectedSQL): void
    {
        parent::testGenerateSQL($sequence, $expectedSQL);
    }
}
