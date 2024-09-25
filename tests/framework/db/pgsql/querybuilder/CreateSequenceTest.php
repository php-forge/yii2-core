<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\querybuilder;

use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group querybuilder
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
        string $sequenceName,
        int $start,
        int $increment,
        array $options,
        string $expectedSQL
    ): void {
        // `PostgreSQL` v. 9.6 and below does not support `CREATE SEQUENCE` with type option.
        if (version_compare($this->db->serverVersion, '10.0', '<')) {
            $expectedSQL = preg_replace('/\s*AS\s+\w+\s*\n/', "\n", $expectedSQL);
        }

        parent::testGenerateSQL($sequenceName, $start, $increment, $options, $expectedSQL);
    }
}
