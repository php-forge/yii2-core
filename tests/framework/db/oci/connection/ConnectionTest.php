<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\command;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group connection
 */
final class ConnectionTest extends \yiiunit\framework\db\connection\AbstractConnection
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }
}
