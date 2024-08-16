<?php

declare(strict_types=1);

namespace yiiunit\framework\db;

use yii\db\ActiveQuery;
use yii\db\Command;
use yii\db\Connection;
use yii\db\QueryBuilder;
use yiiunit\data\ar\ActiveRecord;
use yiiunit\TestCase;

/**
 * @group db
 * @group activerecord
 */
final class ActiveQueryModelConnectionTest extends TestCase
{
    private Connection|null $globalConnection = null;
    private Connection|null $modelConnection = null;

    protected function setUp(): void
    {
        $this->globalConnection = $this->createMock(Connection::class);
        $this->modelConnection = $this->createMock(Connection::class);

        $this->mockApplication(
            [
                'components' => [
                    'db' => $this->globalConnection
                ],
            ],
        );

        ActiveRecord::$db = $this->modelConnection;
    }

    public function testEnsureGlobalConnectionForAll(): void
    {
        $this->modelConnection->expects($this->never())->method('getQueryBuilder');
        $this->prepareConnectionMock($this->globalConnection);

        $query = new ActiveQuery(\yii\db\ActiveRecord::class);
        $query->all();
    }

    public function testEnsureGlobalConnectionForOne(): void
    {
        $this->modelConnection->expects($this->never())->method('getQueryBuilder');

        $this->prepareConnectionMock($this->globalConnection);

        $query = new ActiveQuery(\yii\db\ActiveRecord::class);
        $query->one();
    }

    public function testEnsureModelConnectionForAll(): void
    {
        $this->globalConnection->expects($this->never())->method('getQueryBuilder');

        $this->prepareConnectionMock($this->modelConnection);

        $query = new ActiveQuery(ActiveRecord::class);
        $query->all();
    }

    public function testEnsureModelConnectionForOne(): void
    {
        $this->globalConnection->expects($this->never())->method('getQueryBuilder');

        $this->prepareConnectionMock($this->modelConnection);

        $query = new ActiveQuery(ActiveRecord::class);
        $query->one();
    }

    private function prepareConnectionMock(Connection $connection): void
    {
        $command = $this->createMock(Command::class);
        $command->method('queryOne')->willReturn(false);
        $connection->method('createCommand')->willReturn($command);

        $builder = $this->createMock(QueryBuilder::class);

        $connection->expects($this->once())->method('getQueryBuilder')->willReturn($builder);
    }
}
