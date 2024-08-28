<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\command\types;

use Closure;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group command
 * @group column-type
 * @group auto-increment
 */
final class AutoIncrementTest extends \yiiunit\framework\db\command\types\AbstractExecuteColumnTypes
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\AutoIncrementProvider::command
     */
    public function testExecute(
        Closure|string $abstractColumn,
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
