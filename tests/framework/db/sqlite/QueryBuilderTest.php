<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

use yii\db\Query;
use yii\db\Schema;
use yii\db\sqlite\QueryBuilder;
use yiiunit\data\base\TraversableObject;

/**
 * @group db
 * @group sqlite
 */
class QueryBuilderTest extends \yiiunit\framework\db\QueryBuilderTest
{
    protected $driverName = 'sqlite';

    protected $likeEscapeCharSql = " ESCAPE '\\'";

    public function columnTypes()
    {
        return array_merge(parent::columnTypes(), [
            [
                Schema::TYPE_PK,
                $this->primaryKey()->first()->after('col_before'),
                'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
            ],
        ]);
    }

    public function conditionProvider()
    {
        return array_merge(parent::conditionProvider(), [
            'composite in using array objects' => [
                ['in', new TraversableObject(['id', 'name']), new TraversableObject([
                    ['id' => 1, 'name' => 'oy'],
                    ['id' => 2, 'name' => 'yo'],
                ])],
                '(([[id]] = :qp0 AND [[name]] = :qp1) OR ([[id]] = :qp2 AND [[name]] = :qp3))',
                [':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2, ':qp3' => 'yo'],
            ],
            'composite in' => [
                ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
                '(([[id]] = :qp0 AND [[name]] = :qp1))',
                [':qp0' => 1, ':qp1' => 'oy'],
            ],
        ]);
    }

    public function primaryKeysProvider()
    {
        $this->markTestSkipped('Adding/dropping primary keys is not supported in SQLite.');
    }

    public function foreignKeysProvider()
    {
        $this->markTestSkipped('Adding/dropping foreign keys is not supported in SQLite.');
    }

    public function indexesProvider()
    {
        $result = parent::indexesProvider();
        $result['drop'][0] = 'DROP INDEX [[CN_constraints_2_single]]';

        $indexName = 'myindex';
        $schemaName = 'myschema';
        $tableName = 'mytable';

        $result['with schema'] = [
            "CREATE INDEX {{{$schemaName}}}.[[$indexName]] ON {{{$tableName}}} ([[C_index_1]])",
            function (QueryBuilder $qb) use ($tableName, $indexName, $schemaName) {
                return $qb->createIndex($indexName, $schemaName . '.' . $tableName, 'C_index_1');
            },
        ];

        return $result;
    }

    public function uniquesProvider()
    {
        $this->markTestSkipped('Adding/dropping unique constraints is not supported in SQLite.');
    }

    public function checksProvider()
    {
        $this->markTestSkipped('Adding/dropping check constraints is not supported in SQLite.');
    }

    public function defaultValuesProvider()
    {
        $this->markTestSkipped('Adding/dropping default constraints is not supported in SQLite.');
    }

    public function testCommentColumn()
    {
        $this->markTestSkipped('Comments are not supported in SQLite');
    }

    public function testCommentTable()
    {
        $this->markTestSkipped('Comments are not supported in SQLite');
    }

    public function testRenameTable()
    {
        $sql = $this->getQueryBuilder()->renameTable('table_from', 'table_to');
        $this->assertEquals('ALTER TABLE `table_from` RENAME TO `table_to`', $sql);
    }

    /**
     * {@inheritdoc}
     */
    public function testBuildUnion()
    {
        $expectedQuerySql = $this->replaceQuotes(
            'SELECT `id` FROM `TotalExample` `t1` WHERE (w > 0) AND (x < 2) UNION  SELECT `id` FROM `TotalTotalExample` `t2` WHERE w > 5 UNION ALL  SELECT `id` FROM `TotalTotalExample` `t3` WHERE w = 3'
        );
        $query = new Query();
        $secondQuery = new Query();
        $secondQuery->select('id')
            ->from('TotalTotalExample t2')
            ->where('w > 5');
        $thirdQuery = new Query();
        $thirdQuery->select('id')
            ->from('TotalTotalExample t3')
            ->where('w = 3');
        $query->select('id')
            ->from('TotalExample t1')
            ->where(['and', 'w > 0', 'x < 2'])
            ->union($secondQuery)
            ->union($thirdQuery, true);
        list($actualQuerySql, $queryParams) = $this->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals([], $queryParams);
    }

    public function testBuildWithQuery()
    {
        $expectedQuerySql = $this->replaceQuotes(
            'WITH a1 AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1), a2 AS (SELECT [[id]] FROM [[t2]] INNER JOIN [[a1]] ON t2.id = a1.id WHERE expr = 2 UNION  SELECT [[id]] FROM [[t3]] WHERE expr = 3) SELECT * FROM [[a2]]'
        );
        $with1Query = (new Query())
            ->select('id')
            ->from('t1')
            ->where('expr = 1');

        $with2Query = (new Query())
            ->select('id')
            ->from('t2')
            ->innerJoin('a1', 't2.id = a1.id')
            ->where('expr = 2');

        $with3Query = (new Query())
            ->select('id')
            ->from('t3')
            ->where('expr = 3');

        $query = (new Query())
            ->withQuery($with1Query, 'a1')
            ->withQuery($with2Query->union($with3Query), 'a2')
            ->from('a2');

        list($actualQuerySql, $queryParams) = $this->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals([], $queryParams);
    }
}
