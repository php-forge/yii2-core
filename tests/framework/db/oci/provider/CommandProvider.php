<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\provider;

use yiiunit\support\DbHelper;

final class CommandProvider extends \yiiunit\framework\db\provider\AbstractCommandProvider
{
    protected static string $driverName = 'oci';

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $batchInsert['multirow']['expected'] = <<<SQL
        INSERT ALL INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3) INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (:qp4, :qp5, :qp6, :qp7) SELECT 1 FROM SYS.DUAL
        SQL;
        $batchInsert['multirow']['expectedParams'][':qp3'] = '1';
        $batchInsert['multirow']['expectedParams'][':qp7'] = '0';

        DbHelper::changeSqlForOracleBatchInsert($batchInsert['issue11242']['expected']);
        $batchInsert['issue11242']['expectedParams'][':qp3'] = '1';

        DbHelper::changeSqlForOracleBatchInsert($batchInsert['wrongBehavior']['expected']);
        $batchInsert['wrongBehavior']['expectedParams'][':qp3'] = '0';

        DbHelper::changeSqlForOracleBatchInsert($batchInsert['batchInsert binds params from expression']['expected']);
        $batchInsert['batchInsert binds params from expression']['expectedParams'][':qp3'] = '0';

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
