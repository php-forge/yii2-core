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

    public static function executeResetSequence(): array
    {
        $rows = parent::executeResetSequence();

        $rows['value with zero'] = [
            '{{%reset_sequence}}',
            [
                ['name' => 'name1'],
                ['name' => 'name2'],
                ['name' => 'name3'],
            ],
            [0, 1, 2],
            0,
        ];

        $rows['value negative'] = [
            '{{%reset_sequence}}',
            [
                ['name' => 'name1'],
                ['name' => 'name2'],
                ['name' => 'name3'],
            ],
            [-5, -4, -3],
            -5,
        ];

        return $rows;
    }
}
