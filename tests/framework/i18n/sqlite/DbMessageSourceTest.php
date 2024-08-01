<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\sqlite;

use yiiunit\framework\i18n\AbstractDbMessageSource;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group i18n
 */
class DbMessageSourceTest extends AbstractDbMessageSource
{
    public static function setUpBeforeClass(): void
    {
        static::$db = SqliteConnection::getConnection();

        parent::setUpBeforeClass();
    }
}
