<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group querybuilder
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractCreateSequence
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\QueryBuilderProvider::createSequence
     */
    public function testGenerateSQL(
        string $sequenceName,
        int $start,
        int $increment,
        array $options,
        string $expectedSQL
    ): void {
        parent::testGenerateSQL($sequenceName, $start, $increment, $options, $expectedSQL);
    }
}
