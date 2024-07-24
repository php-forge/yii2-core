<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\mssql;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group mssql
 */
class DbSessionTest extends \yiiunit\framework\web\session\AbstractDbSessionTest
{
    protected function getDriverNames(): array
    {
        return ['mssql', 'sqlsrv', 'dblib'];
    }

    protected function buildObjectForSerialization(): object
    {
        $object = parent::buildObjectForSerialization();

        unset($object->binary);

        // Binary data produce error on insert:
        // `An error occurred translating string for input param 1 to UCS-2`
        // I failed to make it work either with `nvarchar(max)` or `varbinary(max)` column
        // in Microsoft SQL server. © SilverFire TODO: fix it
        return $object;
    }
}
