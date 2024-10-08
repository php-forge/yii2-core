<?php

declare(strict_types=1);

namespace yiiunit\framework\rbac;

/**
 * PgSQLManagerTest.
 * @group db
 * @group rbac
 * @group pgsql
 */
class PgSQLManagerTest extends DbManagerTestCase
{
    protected static $driverName = 'pgsql';
}
