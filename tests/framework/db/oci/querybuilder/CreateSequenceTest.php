<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\querybuilder;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group query-builder
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractCreateSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::createSequence
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
