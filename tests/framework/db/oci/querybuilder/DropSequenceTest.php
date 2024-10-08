<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\querybuilder;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group query-builder
 * @group drop-sequence
 */
final class DropSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractDropSequence
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::dropSequence
     */
    public function testGenerateSQL(string $sequence, string $expectedSQL): void
    {
        parent::testGenerateSQL($sequence, $expectedSQL);
    }
}
