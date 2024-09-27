<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\command;

use yiiunit\support\PgsqlConnection;

use function version_compare;

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
        // `PostgreSQL` v. 9.6 and below does not support `CREATE SEQUENCE` with type option.
        if (version_compare($this->db->serverVersion, '10.0', '<') && isset($options['type'])) {
            $expectedSequenceInfo['type'] = 'bigint';
            $expectedSequenceInfo['maxValue'] = '9223372036854775807';
        }

        parent::testCreateSequence($sequence, $start, $increment, $options, $expectedSequenceInfo);
    }
}
