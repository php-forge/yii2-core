<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\sqlite;

use yiiunit\framework\i18n\AbstractDbMessageSource;
use yiiunit\support\SqliteConnection;

/**
 * @group sqlite
 */
class DbMessageSourceTest extends AbstractDbMessageSource
{
    protected function setUp(): void
    {
        $this->mockApplication();

        $this->db = SqliteConnection::getConnection();

        parent::setUp();
    }
}
