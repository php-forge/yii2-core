<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\command;

use yii\db\QueryInterface;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group command
 * @group upsert
 */
final class UpsertTest extends \yiiunit\framework\db\command\AbstractUpsert
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\QueryBuilderProvider::upsert
     */
    public function testExecuteUpsert(
        string $tableName,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        parent::testExecuteUpsert($tableName, $insertColumns, $updateColumns);
    }
}
