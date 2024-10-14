<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\command;

use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group command
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\framework\db\command\AbstractCreateSequence
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\CommandProvider::createSequence
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
