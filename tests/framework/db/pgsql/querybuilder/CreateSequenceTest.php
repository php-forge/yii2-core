<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\querybuilder;

use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group query-builder
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractCreateSequence
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\QueryBuilderProvider::createSequence
     */
    public function testGenerateSQL(
        string $sequence,
        int $start,
        int $increment,
        array $options,
        string $expectedSQL
    ): void {
        parent::testGenerateSQL($sequence, $start, $increment, $options, $expectedSQL);
    }
}
