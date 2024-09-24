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

    public static function createSequence(): array
    {
        return [
            'simple' => [
                'T_sequence',
                1,
                1,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '1',
                    'min' => '1',
                    'max' => '9223372036854775807',
                    'increment' => '1',
                    'last_number' => '1',
                    'cycle' => false,
                    'cache' => false,
                ],
            ],
            'with suffix _SEQ' => [
                'T_sequence_SEQ',
                1,
                1,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '1',
                    'min' => '1',
                    'max' => '9223372036854775807',
                    'increment' => '1',
                    'last_number' => '1',
                    'cycle' => false,
                    'cache' => false,
                ],
            ],
            'as start' => [
                'T_sequence',
                5,
                1,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '5',
                    'min' => '5',
                    'max' => '9223372036854775807',
                    'increment' => '1',
                    'last_number' => '5',
                    'cycle' => false,
                    'cache' => false,
                ],
            ],
            'as start with value negative' => [
                'T_sequence',
                -5,
                1,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '-5',
                    'min' => '-5',
                    'max' => '9223372036854775807',
                    'increment' => '1',
                    'last_number' => '-5',
                    'cycle' => false,
                    'cache' => false,
                ],
            ],
            'as start with value 0' => [
                'T_sequence',
                0,
                1,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '0',
                    'min' => '0',
                    'max' => '9223372036854775807',
                    'increment' => '1',
                    'last_number' => '0',
                    'cycle' => false,
                    'cache' => false,
                ],
            ],
            'as increment' => [
                'T_sequence',
                1,
                2,
                ['increment' => 2],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '1',
                    'min' => '1',
                    'max' => '9223372036854775807',
                    'increment' => '2',
                    'last_number' => '1',
                    'cycle' => false,
                    'cache' => false,
                ],
            ],
            'as increment with value negative' => [
                'T_sequence',
                1,
                -2,
                ['increment' => -2],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '1',
                    'min' => '1',
                    'max' => '9223372036854775807',
                    'increment' => '-2',
                    'last_number' => '1',
                    'cycle' => false,
                    'cache' => false,
                ],
            ],
            'as minvalue' => [
                'T_sequence',
                12,
                1,
                ['minValue' => 10],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '12',
                    'min' => '10',
                    'max' => '9223372036854775807',
                    'increment' => '1',
                    'last_number' => '12',
                    'cycle' => false,
                    'cache' => false,
                ],
            ],
            'as maxvalue' => [
                'T_sequence',
                1,
                1,
                ['maxValue' => 100],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '1',
                    'min' => '1',
                    'max' => '100',
                    'increment' => '1',
                    'last_number' => '1',
                    'cycle' => false,
                    'cache' => false,
                ],
            ],
            'as cycle' => [
                'T_sequence',
                1,
                1,
                ['cycle' => true],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '1',
                    'min' => '1',
                    'max' => '9223372036854775807',
                    'increment' => '1',
                    'last_number' => '1',
                    'cycle' => true,
                    'cache' => false,
                ],
            ],
            'as cache' => [
                'T_sequence',
                1,
                2,
                ['cache' => 50],
                [
                    'name' => 'T_sequence_SEQ',
                    'start' => '1',
                    'min' => '1',
                    'max' => '9223372036854775807',
                    'increment' => '2',
                    'last_number' => '1',
                    'cycle' => false,
                    'cache' => 50,
                ],
            ],
        ];
    }
}
