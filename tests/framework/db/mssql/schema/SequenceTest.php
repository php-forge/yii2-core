<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\schema;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group schema
 * @group sequence
 */
final class SequenceTest extends \yiiunit\framework\db\schema\AbstractSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }
}
