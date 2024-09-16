<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\command;

use yii\db\QueryInterface;
use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group command
 * @group upsert
 */
final class UpsertTest extends \yiiunit\framework\db\command\AbstractUpsert
{
    public function setup(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection(true);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::upsert
     */
    public function testExecuteUpsert(
        string $tableName,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        parent::testExecuteUpsert($tableName, $insertColumns, $updateColumns);
    }
}
