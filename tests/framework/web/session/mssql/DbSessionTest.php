<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\mssql;

use yiiunit\support\MssqlConnection;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group mssql
 * @group session-db-mssql
 */
class DbSessionTest extends \yiiunit\framework\web\session\AbstractDbSessionTest
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        $this->db = MssqlConnection::getConnection();

        parent::setUp();
    }

    protected function buildObjectForSerialization(): object
    {
        $object = parent::buildObjectForSerialization();

        unset($object->binary);

        return $object;
    }
}
