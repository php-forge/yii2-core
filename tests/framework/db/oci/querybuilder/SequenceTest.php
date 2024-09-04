<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\querybuilder;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group querybuilder
 * @group sequence
 */
final class SequenceTest extends \yiiunit\framework\db\querybuilder\AbstractSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::createSequence
     */
    public function testCreateSequence(
        string $table,
        int $start,
        int $increment,
        array $options,
        string $expectedSQL
    ): void {
        parent::testCreateSequence($table, $start, $increment, $options, $expectedSQL);
    }
}
