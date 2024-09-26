<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\command;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group command
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\framework\db\command\AbstractCreateSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\CommandProvider::createSequence
     */
    public function testCreateSequence(
        string $sequence,
        int $start,
        int $increment,
        array $options,
        array $expectedSequenceInfo
    ): void {
        parent::testCreateSequence($sequence, $start, $increment, $options, $expectedSequenceInfo);
    }
}
