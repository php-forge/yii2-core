<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\command;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group command
 * @group create-sequence
 */
final class CreateSequenceTest extends \yiiunit\framework\db\command\AbstractCreateSequence
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\CommandProvider::createSequence
     */
    public function testCreateSequence(
        string $table,
        int $start,
        int $increment,
        array $options,
        array $expectedSequenceInfo
    ): void {
        parent::testCreateSequence($table, $start, $increment, $options, $expectedSequenceInfo);
    }
}
