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
 * @group auto-increment
 */
final class AutoIncrementTest extends \yiiunit\framework\db\querybuilder\types\AbstractColumnType
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\AutoIncrementProvider::builder
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\AutoIncrementProvider::builderWithUnsigned
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\AutoIncrementProvider::raw
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\AutoIncrementProvider::rawWithUnsigned
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
