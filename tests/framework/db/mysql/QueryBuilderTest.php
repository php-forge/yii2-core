<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;

use yii\base\DynamicModel;
use yii\db\Expression;
use yii\db\JsonExpression;
use yii\db\Query;
use yii\db\Schema;

/**
 * @group db
 * @group mysql
 */
class QueryBuilderTest extends \yiiunit\framework\db\QueryBuilderTest
{
    protected $driverName = 'mysql';

    /**
     * This is not used as a dataprovider for testGetColumnType to speed up the test
     * when used as dataprovider every single line will cause a reconnect with the database which is not needed here.
     */
    public function columnTypes()
    {
        $columns = [
            [
                Schema::TYPE_PK . ' AFTER `col_before`',
                $this->primaryKey()->after('col_before'),
                'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY AFTER `col_before`',
            ],
            [
                Schema::TYPE_PK . ' FIRST',
                $this->primaryKey()->first(),
                'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
            ],
            [
                Schema::TYPE_PK . ' FIRST',
                $this->primaryKey()->first()->after('col_before'),
                'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
            ],
            [
                Schema::TYPE_PK . '(8) AFTER `col_before`',
                $this->primaryKey(8)->after('col_before'),
                'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY AFTER `col_before`',
            ],
            [
                Schema::TYPE_PK . '(8) FIRST',
                $this->primaryKey(8)->first(),
                'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
            ],
            [
                Schema::TYPE_PK . '(8) FIRST',
                $this->primaryKey(8)->first()->after('col_before'),
                'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
            ],
            [
                Schema::TYPE_PK . " COMMENT 'test' AFTER `col_before`",
                $this->primaryKey()->comment('test')->after('col_before'),
                "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'test' AFTER `col_before`",
            ],
            [
                Schema::TYPE_PK . " COMMENT 'testing \'quote\'' AFTER `col_before`",
                $this->primaryKey()->comment('testing \'quote\'')->after('col_before'),
                "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'testing \'quote\'' AFTER `col_before`",
            ],
        ];

        $columns[] = [Schema::TYPE_JSON, $this->json(), "json"];

        return array_merge(parent::columnTypes(), $this->columnTimeTypes(), $columns);
    }

    public function columnTimeTypes()
    {
        $columns = [
            [
                Schema::TYPE_DATETIME . ' NOT NULL',
                $this->dateTime()->notNull(),
                'datetime NOT NULL',
            ],
            [
                Schema::TYPE_DATETIME,
                $this->dateTime(),
                'datetime',
            ],
            [
                Schema::TYPE_TIME . ' NOT NULL',
                $this->time()->notNull(),
                'time NOT NULL',
            ],
            [
                Schema::TYPE_TIME,
                $this->time(),
                'time',
            ],
            [
                Schema::TYPE_TIMESTAMP . ' NOT NULL',
                $this->timestamp()->notNull(),
                'timestamp NOT NULL',
            ],
            [
                Schema::TYPE_TIMESTAMP . ' NULL DEFAULT NULL',
                $this->timestamp()->defaultValue(null),
                'timestamp NULL DEFAULT NULL',
            ],
        ];

        /**
         * @link https://github.com/yiisoft/yii2/issues/14367
         */
        $expectedValues = [
            'datetime(0) NOT NULL',
            'datetime(0)',
            'time(0) NOT NULL',
            'time(0)',
            'timestamp(0) NOT NULL',
            'timestamp(0) NULL DEFAULT NULL',
        ];

        foreach ($expectedValues as $index => $expected) {
            $columns[$index][2] = $expected;
        }

        /**
         * @link https://github.com/yiisoft/yii2/issues/14834
         */
        $sqlModes = $this->getConnection(false)->createCommand('SELECT @@sql_mode')->queryScalar();
        $sqlModes = explode(',', $sqlModes);

        if (in_array('NO_ZERO_DATE', $sqlModes, true)) {
            $this->markTestIncomplete(
                "MySQL doesn't allow the 'TIMESTAMP' column definition when the NO_ZERO_DATE mode enabled. " .
                "This definition test was skipped."
            );
        } else {
            $columns[] = [
                Schema::TYPE_TIMESTAMP,
                $this->timestamp(),
                'timestamp(0)',
            ];
        }

        return $columns;
    }

    public function primaryKeysProvider()
    {
        $result = parent::primaryKeysProvider();
        $result['drop'][0] = 'ALTER TABLE {{T_constraints_1}} DROP PRIMARY KEY';
        $result['add'][0] = 'ALTER TABLE {{T_constraints_1}} ADD CONSTRAINT [[CN_pk]] PRIMARY KEY ([[C_id_1]])';
        $result['add (2 columns)'][0] = 'ALTER TABLE {{T_constraints_1}} ADD CONSTRAINT [[CN_pk]] PRIMARY KEY ([[C_id_1]], [[C_id_2]])';
        return $result;
    }

    public function foreignKeysProvider()
    {
        $result = parent::foreignKeysProvider();
        $result['drop'][0] = 'ALTER TABLE {{T_constraints_3}} DROP FOREIGN KEY [[CN_constraints_3]]';
        return $result;
    }

    public function indexesProvider()
    {
        $result = parent::indexesProvider();
        $result['create'][0] = 'ALTER TABLE {{T_constraints_2}} ADD INDEX [[CN_constraints_2_single]] ([[C_index_1]])';
        $result['create (2 columns)'][0] = 'ALTER TABLE {{T_constraints_2}} ADD INDEX [[CN_constraints_2_multi]] ([[C_index_2_1]], [[C_index_2_2]])';
        $result['create unique'][0] = 'ALTER TABLE {{T_constraints_2}} ADD UNIQUE INDEX [[CN_constraints_2_single]] ([[C_index_1]])';
        $result['create unique (2 columns)'][0] = 'ALTER TABLE {{T_constraints_2}} ADD UNIQUE INDEX [[CN_constraints_2_multi]] ([[C_index_2_1]], [[C_index_2_2]])';
        return $result;
    }

    public function uniquesProvider()
    {
        $result = parent::uniquesProvider();
        $result['drop'][0] = 'DROP INDEX [[CN_unique]] ON {{T_constraints_1}}';
        return $result;
    }

    public function defaultValuesProvider()
    {
        $this->markTestSkipped('Adding/dropping default constraints is not supported in MySQL.');
    }

    public function conditionProvider()
    {
        return array_merge(parent::conditionProvider(), [
            // json conditions
            [
                ['=', 'jsoncol', new JsonExpression(['lang' => 'uk', 'country' => 'UA'])],
                '[[jsoncol]] = :qp0', [':qp0' => '{"lang":"uk","country":"UA"}'],
            ],
            [
                ['=', 'jsoncol', new JsonExpression([false])],
                '[[jsoncol]] = :qp0', [':qp0' => '[false]']
            ],
            'object with type. Type is ignored for MySQL' => [
                ['=', 'prices', new JsonExpression(['seeds' => 15, 'apples' => 25], 'jsonb')],
                '[[prices]] = :qp0', [':qp0' => '{"seeds":15,"apples":25}'],
            ],
            'nested json' => [
                ['=', 'data', new JsonExpression(['user' => ['login' => 'silverfire', 'password' => 'c4ny0ur34d17?'], 'props' => ['mood' => 'good']])],
                '[[data]] = :qp0', [':qp0' => '{"user":{"login":"silverfire","password":"c4ny0ur34d17?"},"props":{"mood":"good"}}']
            ],
            'null value' => [
                ['=', 'jsoncol', new JsonExpression(null)],
                '[[jsoncol]] = :qp0', [':qp0' => 'null']
            ],
            'null as array value' => [
                ['=', 'jsoncol', new JsonExpression([null])],
                '[[jsoncol]] = :qp0', [':qp0' => '[null]']
            ],
            'null as object value' => [
                ['=', 'jsoncol', new JsonExpression(['nil' => null])],
                '[[jsoncol]] = :qp0', [':qp0' => '{"nil":null}']
            ],
            'with object as value' => [
                ['=', 'jsoncol', new JsonExpression(new DynamicModel(['a' => 1, 'b' => 2]))],
                '[[jsoncol]] = :qp0', [':qp0' => '{"a":1,"b":2}']
            ],
            'query' => [
                ['=', 'jsoncol', new JsonExpression((new Query())->select('params')->from('user')->where(['id' => 1]))],
                '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)', [':qp0' => 1]
            ],
            'query with type, that is ignored in MySQL' => [
                ['=', 'jsoncol', new JsonExpression((new Query())->select('params')->from('user')->where(['id' => 1]), 'jsonb')],
                '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)', [':qp0' => 1]
            ],
            'nested and combined json expression' => [
                ['=', 'jsoncol', new JsonExpression(new JsonExpression(['a' => 1, 'b' => 2, 'd' => new JsonExpression(['e' => 3])]))],
                "[[jsoncol]] = :qp0", [':qp0' => '{"a":1,"b":2,"d":{"e":3}}']
            ],
            'search by property in JSON column (issue #15838)' => [
                ['=', new Expression("(jsoncol->>'$.someKey')"), '42'],
                "(jsoncol->>'$.someKey') = :qp0", [':qp0' => 42]
            ]
        ]);
    }

    public function updateProvider()
    {
        $items = parent::updateProvider();

        $items[] = [
            'profile',
            [
                'description' => new JsonExpression(['abc' => 'def', 123, null]),
            ],
            [
                'id' => 1,
            ],
            $this->replaceQuotes('UPDATE [[profile]] SET [[description]]=:qp0 WHERE [[id]]=:qp1'),
            [
                ':qp0' => '{"abc":"def","0":123,"1":null}',
                ':qp1' => 1,
            ],
        ];

        return $items;
    }

    public function testIssue17449()
    {
        $db = $this->getConnection();
        $pdo = $db->pdo;
        $pdo->exec('DROP TABLE IF EXISTS `issue_17449`');

        $tableQuery = <<<MySqlStatement
CREATE TABLE `issue_17449` (
  `test_column` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'some comment' CHECK (json_valid(`test_column`))
) ENGINE=InnoDB DEFAULT CHARSET=latin1
MySqlStatement;
        $db->createCommand($tableQuery)->execute();

        $actual = $db->createCommand()->addCommentOnColumn('issue_17449', 'test_column', 'Some comment')->rawSql;

        $checkPos = stripos($actual, 'check');
        if ($checkPos === false) {
            $this->markTestSkipped("The used MySql-Server removed or moved the CHECK from the column line, so the original bug doesn't affect it");
        }
        $commentPos = stripos($actual, 'comment');
        $this->assertNotFalse($commentPos);
        $this->assertLessThan($checkPos, $commentPos);
    }

    /**
     * Test for issue https://github.com/yiisoft/yii2/issues/14663
     */
    public function testInsertInteger()
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        // int value should not be converted to string, when column is `int`
        $sql = $command->insert('{{type}}', ['int_col' => 22])->getRawSql();
        $this->assertEquals('INSERT INTO `type` (`int_col`) VALUES (22)', $sql);

        // int value should not be converted to string, when column is `int unsigned`
        $sql = $command->insert('{{type}}', ['int_col3' => 22])->getRawSql();
        $this->assertEquals('INSERT INTO `type` (`int_col3`) VALUES (22)', $sql);

        // int value should not be converted to string, when column is `bigint unsigned`
        $sql = $command->insert('{{type}}', ['bigint_col' => 22])->getRawSql();
        $this->assertEquals("INSERT INTO `type` (`bigint_col`) VALUES (22)", $sql);

        // string value should not be converted
        $sql = $command->insert('{{type}}', ['bigint_col' => '1000000000000'])->getRawSql();
        $this->assertEquals("INSERT INTO `type` (`bigint_col`) VALUES ('1000000000000')", $sql);
    }

    /**
     * Test for issue https://github.com/yiisoft/yii2/issues/15500
     */
    public function testDefaultValues()
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        // primary key columns should have NULL as value
        $sql = $command->insert('null_values', [])->getRawSql();
        $this->assertEquals("INSERT INTO `null_values` (`id`) VALUES (NULL)", $sql);

        // non-primary key columns should have DEFAULT as value
        $sql = $command->insert('negative_default_values', [])->getRawSql();
        $this->assertEquals("INSERT INTO `negative_default_values` (`smallint_col`) VALUES (DEFAULT)", $sql);
    }
}
