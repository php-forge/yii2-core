<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\mssql;

use yiiunit\framework\i18n\AbstractDbMessageSource;
use yiiunit\support\MssqlConnection;

/**
 * @group mssql
 */
class DbMessageSourceTest extends AbstractDbMessageSource
{
    protected function setUp(): void
    {
        $this->mockApplication();

        $this->db = MssqlConnection::getConnection();

        parent::setUp();
    }
}
