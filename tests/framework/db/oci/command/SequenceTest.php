<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\command;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group command
 * @group sequence
 */
final class SequenceTest extends \yiiunit\framework\db\command\AbstractSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\CommandProvider::createSequence
     */
    public function testExecuteCreateSequence(
        string $table,
        int $start,
        int $increment,
        array $options
    ): void {
        parent::testExecuteCreateSequence($table, $start, $increment, $options);
    }
}
