<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\command;

use yii\db\QueryInterface;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group command
 * @group upsert
 */
final class UpsertTest extends \yiiunit\framework\db\command\AbstractUpsert
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QueryBuilderProvider::upsert
     */
    public function testExecuteUpsert(
        string $tableName,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        parent::testExecuteUpsert($tableName, $insertColumns, $updateColumns);
    }
}
