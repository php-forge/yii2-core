<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\querybuilder;

use yii\db\QueryInterface;
use yiiunit\support\SqliteConnection;

use function dirname;

/**
 * @group db
 * @group sqlite
 * @group querybuilder
 * @group upsert
 */
final class UpsertTest extends \yiiunit\framework\db\querybuilder\AbstractUpsert
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QueryBuilderProvider::upsert
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSQL,
        array $expectedParams,
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\QueryBuilderProvider::upsert
     */
    public function testUpsertExecute(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        parent::testUpsertExecute($table, $insertColumns, $updateColumns);
    }
}
