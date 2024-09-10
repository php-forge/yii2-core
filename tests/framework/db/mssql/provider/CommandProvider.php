<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider;

final class CommandProvider extends \yiiunit\framework\db\provider\AbstractCommandProvider
{
    protected static string $driverName = 'sqlsrv';

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $batchInsert['multirow']['expectedParams'][':qp3'] = 1;
        $batchInsert['multirow']['expectedParams'][':qp7'] = 0;
        $batchInsert['issue11242']['expectedParams'][':qp3'] = 1;
        $batchInsert['wrongBehavior']['expectedParams'][':qp3'] = 0;
        $batchInsert['batchInsert binds params from expression']['expectedParams'][':qp3'] = 0;

        return $batchInsert;
    }
}
