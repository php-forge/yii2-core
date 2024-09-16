<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci;

use yii\db\oci\QueryBuilder;
use yii\db\oci\Schema;
use yii\helpers\ArrayHelper;
use yiiunit\data\base\TraversableObject;

/**
 * @group db
 * @group oci
 */
class QueryBuilderTest extends \yiiunit\framework\db\QueryBuilderTest
{
    public $driverName = 'oci';

    protected $likeEscapeCharSql = " ESCAPE '!'";
    protected $likeParameterReplacements = [
        '\%' => '!%',
        '\_' => '!_',
        '!' => '!!',
    ];

    /**
     * This is not used as a dataprovider for testGetColumnType to speed up the test
     * when used as dataprovider every single line will cause a reconnect with the database which is not needed here.
     */
    public function columnTypes()
    {
        return array_merge(parent::columnTypes(), [
            [
                Schema::TYPE_BOOLEAN . ' DEFAULT 1 NOT NULL',
                $this->boolean()->notNull()->defaultValue(1),
                'NUMBER(1) DEFAULT 1 NOT NULL',
            ],
        ]);
    }

    public function foreignKeysProvider()
    {
        $tableName = 'T_constraints_3';
        $name = 'CN_constraints_3';
        $pkTableName = 'T_constraints_2';
        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->dropForeignKey($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]]) ON DELETE CASCADE",
                function (QueryBuilder $qb) use ($tableName, $name, $pkTableName) {
                    return $qb->addForeignKey($name, $tableName, 'C_fk_id_1', $pkTableName, 'C_id_1', 'CASCADE');
                },
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]], [[C_fk_id_2]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]], [[C_id_2]]) ON DELETE CASCADE",
                function (QueryBuilder $qb) use ($tableName, $name, $pkTableName) {
                    return $qb->addForeignKey($name, $tableName, 'C_fk_id_1, C_fk_id_2', $pkTableName, 'C_id_1, C_id_2', 'CASCADE');
                },
            ],
        ];
    }

    public function indexesProvider()
    {
        $result = parent::indexesProvider();
        $result['drop'][0] = 'DROP INDEX [[CN_constraints_2_single]]';
        return $result;
    }

    /**
     * @dataProvider defaultValuesProvider
     * @param string $sql
     */
    public function testAddDropDefaultValue($sql, \Closure $builder)
    {
        $this->markTestSkipped('Adding/dropping default constraints is not supported in Oracle.');
    }

    public function testCommentColumn()
    {
        $qb = $this->getQueryBuilder();

        $expected = "COMMENT ON COLUMN [[comment]].[[text]] IS 'This is my column.'";
        $sql = $qb->addCommentOnColumn('comment', 'text', 'This is my column.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = "COMMENT ON COLUMN [[comment]].[[text]] IS ''";
        $sql = $qb->dropCommentFromColumn('comment', 'text');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    public function testCommentTable()
    {
        $qb = $this->getQueryBuilder();

        $expected = "COMMENT ON TABLE [[comment]] IS 'This is my table.'";
        $sql = $qb->addCommentOnTable('comment', 'This is my table.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = "COMMENT ON TABLE [[comment]] IS ''";
        $sql = $qb->dropCommentFromTable('comment');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    public function likeConditionProvider()
    {
        /*
         * Different pdo_oci8 versions may or may not implement PDO::quote(), so
         * yii\db\Schema::quoteValue() may or may not quote \.
         */
        try {
            $encodedBackslash = substr($this->getDb()->quoteValue('\\'), 1, -1);
            $this->likeParameterReplacements[$encodedBackslash] = '\\';
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not execute Connection::quoteValue() method: ' . $e->getMessage());
        }

        return parent::likeConditionProvider();
    }

    public function conditionProvidertmp()
    {
        // test bc with commit
        // {@see https://github.com/yiisoft/yii2/commit/d16586334d7bea226a67aa8db28982848b5c92dd#diff-ae95e8cbf4e036860dd6b41011f9f8035a616a8f45d3c3167b3705d39879c95c}
        // should be fixed.
        return array_merge([], [
            [
                ['in', '[[id]]', range(0, 2500)],

                ' ('
                . '([[id]] IN (' . implode(', ', $this->generateSprintfSeries(':qp%d', 0, 999)) . '))'
                . ' OR ([[id]] IN (' . implode(', ', $this->generateSprintfSeries(':qp%d', 1000, 1999)) . '))'
                . ' OR ([[id]] IN (' . implode(', ', $this->generateSprintfSeries(':qp%d', 2000, 2500)) . '))'
                . ')',

                array_flip($this->generateSprintfSeries(':qp%d', 0, 2500)),
            ],
            [
                ['not in', '[[id]]', range(0, 2500)],

                '('
                . '([[id]] NOT IN (' . implode(', ', $this->generateSprintfSeries(':qp%d', 0, 999)) . '))'
                . ' AND ([[id]] NOT IN (' . implode(', ', $this->generateSprintfSeries(':qp%d', 1000, 1999)) . '))'
                . ' AND ([[id]] NOT IN (' . implode(', ', $this->generateSprintfSeries(':qp%d', 2000, 2500)) . '))'
                . ')',

                array_flip($this->generateSprintfSeries(':qp%d', 0, 2500)),
            ],
            [
                ['not in', '[[id]]', new TraversableObject(range(0, 2500))],

                '('
                . '([[id]] NOT IN (' . implode(', ', $this->generateSprintfSeries(':qp%d', 0, 999)) . '))'
                . ' AND ([[id]] NOT IN (' . implode(', ', $this->generateSprintfSeries(':qp%d', 1000, 1999)) . '))'
                . ' AND ([[id]] NOT IN (' . implode(', ', $this->generateSprintfSeries(':qp%d', 2000, 2500)) . '))'
                . ')',

                array_flip($this->generateSprintfSeries(':qp%d', 0, 2500)),
            ],
        ]);
    }

    protected function generateSprintfSeries($pattern, $from, $to)
    {
        $items = [];
        for ($i = $from; $i <= $to; $i++) {
            $items[] = sprintf($pattern, $i);
        }

        return $items;
    }

    public function batchInsertProvider()
    {
        $data = parent::batchInsertProvider();

        $data[0][3] = 'INSERT ALL  INTO "customer" ("email", "name", "address") ' .
            "VALUES ('test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine') SELECT 1 FROM SYS.DUAL";

        $data['escape-danger-chars']['expected'] = 'INSERT ALL  INTO "customer" ("address") ' .
            "VALUES ('SQL-danger chars are escaped: ''); --') SELECT 1 FROM SYS.DUAL";

        $data[2][3] = 'INSERT ALL  INTO "customer" () ' .
            "VALUES ('no columns passed') SELECT 1 FROM SYS.DUAL";

        $data['bool-false, bool2-null']['expected'] = 'INSERT ALL  INTO "type" ("bool_col", "bool_col2") ' .
            "VALUES ('0', NULL) SELECT 1 FROM SYS.DUAL";

        $data[3][3] = 'INSERT ALL  INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) ' .
            "VALUES (NULL, now()) SELECT 1 FROM SYS.DUAL";

        $data['bool-false, time-now()']['expected'] = 'INSERT ALL  INTO {{%type}} ({{%type}}.[[bool_col]], [[time]]) ' .
            "VALUES (0, now()) SELECT 1 FROM SYS.DUAL";

        return $data;
    }

    /**
     * Dummy test to speed up QB's tests which rely on DB schema
     */
    public function testInitFixtures()
    {
        $this->assertInstanceOf('yii\db\QueryBuilder', $this->getQueryBuilder(true, true));
    }
}
