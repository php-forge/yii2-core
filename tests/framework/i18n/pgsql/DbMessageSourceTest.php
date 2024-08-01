<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\pgsql;

use yiiunit\framework\i18n\AbstractDbMessageSource;
use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group i18n
 */
class DbMessageSourceTest extends AbstractDbMessageSource
{
    protected function setUp(): void
    {
        $this->mockApplication();

        $this->db = PgsqlConnection::getConnection();

        parent::setUp();
    }
}
