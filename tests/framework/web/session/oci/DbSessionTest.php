<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\oci;

use yiiunit\framework\web\session\AbstractDbSession;
use yiiunit\support\OciConnection;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group oci
 * @group session-db
 */
class DbSessionTest extends AbstractDbSession
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        $this->db = OciConnection::getConnection();

        parent::setUp();
    }
}
