<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\command;

use yii\db\QueryInterface;
use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group command
 * @group upsert
 */
final class UpsertTest extends \yiiunit\framework\db\command\AbstractUpsert
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\QueryBuilderProvider::upsert
     */
    public function testExecuteUpsert(
        string $tableName,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        parent::testExecuteUpsert($tableName, $insertColumns, $updateColumns);
    }
}
