<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\querybuilder;

use yii\db\QueryInterface;
use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group query-builder
 * @group upsert
 */
final class UpsertTest extends \yiiunit\framework\db\querybuilder\AbstractUpsert
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::upsert
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
