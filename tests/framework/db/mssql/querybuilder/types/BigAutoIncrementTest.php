<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\types\querybuilder;

use Closure;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group querybuilder
 * @group column-type
 * @group big-auto-increment
 */
final class BigAutoIncrementTest extends \yiiunit\framework\db\querybuilder\types\AbstractColumnType
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\types\BigAutoIncrementProvider::queryBuilder
     */
    public function testGenerateColumnType(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $this->getColumnType($column, $expectedColumn, $builder, $expectedBuilder);
    }
}
