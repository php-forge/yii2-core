<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\querybuilder\types;

use Closure;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group querybuilder
 * @group column-type
 * @group big-auto-increment
 */
final class BigAutoIncrementTest extends \yiiunit\framework\db\querybuilder\types\AbstractColumnType
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\BigAutoIncrementProvider::builder
     */
    public function testBuilder(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $this->getColumnType($column, $expectedColumn, $builder, $expectedBuilder);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\BigAutoIncrementProvider::builderWithUnsigned
     */
    public function testBuilderWithUnsigned(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $this->getColumnType($column, $expectedColumn, $builder, $expectedBuilder);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\BigAutoIncrementProvider::raw
     */
    public function testRaw(
        string $columnRaw,
        string $column,
        Closure $builder,
        string $expectColumn = '',
    ): void {
        $this->getColumnTypeRaw($columnRaw, $column, $builder, $expectColumn);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\BigAutoIncrementProvider::rawWithUnsigned
     */
    public function testRawWithUnsigned(
        string $columnRaw,
        string $column,
        Closure $builder,
        string $expectColumn = '',
    ): void {
        $this->getColumnTypeRaw($columnRaw, $column, $builder, $expectColumn);
    }
}
