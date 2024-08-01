<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\mssql;

use yiiunit\framework\i18n\AbstractDbMessageSource;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group i18n
 */
class DbMessageSourceTest extends AbstractDbMessageSource
{
    public static function setUpBeforeClass(): void
    {
        static::$db = MssqlConnection::getConnection();

        parent::setUpBeforeClass();
    }
}
