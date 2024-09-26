<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use yii\db\QueryInterface;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group query-builder
 * @group upsert
 */
final class UpsertTest extends \yiiunit\framework\db\querybuilder\AbstractUpsert
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\QueryBuilderProvider::upsert
     */
    public function testUpsert(
        string $tableName,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSQL,
        array $expectedParams,
    ): void {
        parent::testUpsert($tableName, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }
}
