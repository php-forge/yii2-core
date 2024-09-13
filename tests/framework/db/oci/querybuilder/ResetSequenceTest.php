<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\querybuilder;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group querybuilder
 * @group reset-sequence
 */
final class ResetSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractResetSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::resetSequence
     */
    public function testResetSequence(string $tableName, string $columnPK, int|null $value, string $expected): void
    {
        parent::testResetSequence($tableName, $columnPK, $value, $expected);
    }
}
