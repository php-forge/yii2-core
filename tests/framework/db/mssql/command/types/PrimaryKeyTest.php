<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\command\types;

use Closure;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group command
 * @group column-type
 * @group primary-key
 */
final class PrimaryKeyTest extends \yiiunit\framework\db\command\types\AbstractExecuteColumnTypes
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\types\PrimaryKeyProvider::schema
     */
    public function testExecute(
        Closure $abstractColumn,
        string $expectedColumnSchemaType,
        bool|null $isPrimaryKey,
        string $expectedColumnType,
        int|string $expectedLastInsertID,
    ): void {
        parent::executeColumnTypes(
            $abstractColumn,
            $expectedColumnSchemaType,
            $isPrimaryKey,
            $expectedColumnType,
            $expectedLastInsertID,
        );
    }
}
