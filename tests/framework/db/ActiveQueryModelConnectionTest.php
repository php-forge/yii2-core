<?php

declare(strict_types=1);

namespace yiiunit\framework\db;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord as DefaultActiveRecord;
use yiiunit\data\ar\ActiveRecord;
use yiiunit\TestCase;

class ActiveQueryModelConnectionTest extends TestCase
{
    private $globalConnection;
    private $modelConnection;

    protected function setUp(): void
    {
        $this->globalConnection = $this->getMockBuilder('yii\db\Connection')->getMock();
        $this->modelConnection = $this->getMockBuilder('yii\db\Connection')->getMock();

        $this->mockApplication([
            'components' => [
                'db' => $this->globalConnection
            ]
        ]);

        ActiveRecord::$db = $this->modelConnection;
    }

    private function prepareConnectionMock($connection)
    {
        $command = $this->getMockBuilder('yii\db\Command')->getMock();
        $command->method('queryOne')->willReturn(false);
        $connection->method('createCommand')->willReturn($command);
        $builder = $this->getMockBuilder('yii\db\QueryBuilder')->disableOriginalConstructor()->getMock();
        $builder->method('build')->willReturn(['', []]);
        $connection->expects($this->once())->method('getQueryBuilder')->willReturn($builder);
    }

    public function testEnsureModelConnectionForOne1(): void
    {
        $this->globalConnection->expects($this->never())->method('getQueryBuilder');
        $this->prepareConnectionMock($this->modelConnection);

        $query = new ActiveQuery(ActiveRecord::class);
        $query->one();
    }

    public function testEnsureGlobalConnectionForOne()
    {
        $this->modelConnection->expects($this->never())->method('getQueryBuilder');
        $this->prepareConnectionMock($this->globalConnection);

        $query = new ActiveQuery(DefaultActiveRecord::class);
        $query->one();
    }

    public function testEnsureModelConnectionForAll()
    {
        $this->globalConnection->expects($this->never())->method('getQueryBuilder');
        $this->prepareConnectionMock($this->modelConnection);

        $query = new ActiveQuery(ActiveRecord::class);
        $query->all();
    }

    public function testEnsureGlobalConnectionForAll()
    {
        $this->modelConnection->expects($this->never())->method('getQueryBuilder');
        $this->prepareConnectionMock($this->globalConnection);

        $query = new ActiveQuery(DefaultActiveRecord::class);
        $query->all();
    }
}
