<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\mssql;

use PDO;
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

    public function testSerializedObjectSaving(): void
    {
        // Data is 8-bit characters as specified in the code page of the Windows locale that is set on the system.
        // Any multi-byte characters or characters that do not map into this code page are substituted with a
        // single-byte question mark (?) character.
        $this->db->getSlavePdo()->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);

        parent::testSerializedObjectSaving();
    }
}
