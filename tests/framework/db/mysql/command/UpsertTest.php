<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\command;

use yii\db\QueryInterface;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group command
 * @group upsert
 */
final class UpsertTest extends \yiiunit\framework\db\command\AbstractUpsert
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\QueryBuilderProvider::upsert
     */
    public function testExecuteUpsert(
        string $tableName,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        parent::testExecuteUpsert($tableName, $insertColumns, $updateColumns);
    }
}
