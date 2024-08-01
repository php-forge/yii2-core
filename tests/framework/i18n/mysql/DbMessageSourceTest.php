<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\mysql;

use yiiunit\framework\i18n\AbstractDbMessageSource;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group i18n
 */
class DbMessageSourceTest extends AbstractDbMessageSource
{
    protected function setUp(): void
    {
        $this->mockApplication();

        $this->db = MysqlConnection::getConnection();

        parent::setUp();
    }
}
