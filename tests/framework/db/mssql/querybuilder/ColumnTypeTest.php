<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use Closure;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group querybuilder
 * @group column-schema-builder
 * @group column-type
 */
final class ColumnTypeTest extends \yiiunit\framework\db\querybuilder\AbstractColumnType
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\ColumnTypeProvider::autoIncrement
     */
    public function testAutoIncrement(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $this->getColumnType($column, $expectedColumn, $builder, $expectedBuilder);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\ColumnTypeProvider::autoIncrementWithRaw
     */
    public function testAutoIncrementWithRaw(
        string $columnRaw,
        string $column,
        Closure $builder,
        string $expectColumn = '',
    ): void {
        $this->getColumnTypeRaw($columnRaw, $column, $builder, $expectColumn);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\ColumnTypeProvider::bigAutoIncrement
     */
    public function testBigAutoIncrement(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $this->getColumnType($column, $expectedColumn, $builder, $expectedBuilder);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\ColumnTypeProvider::bigAutoIncrementWithRaw
     */
    public function testBigAutoIncrementWithRaw(
        string $columnRaw,
        string $column,
        Closure $builder,
        string $expectColumn = '',
    ): void {
        $this->getColumnTypeRaw($columnRaw, $column, $builder, $expectColumn);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\ColumnTypeProvider::bigPrimaryKey
     */
    public function testBigPrimaryKey(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $this->getColumnType($column, $expectedColumn, $builder, $expectedBuilder);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\ColumnTypeProvider::bigPrimaryKeyWithRaw
     */
    public function testBigPrimaryKeyWithRaw(
        string $columnRaw,
        string $column,
        Closure $builder,
        string $expectColumn = '',
    ): void {
        $this->getColumnTypeRaw($columnRaw, $column, $builder, $expectColumn);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\ColumnTypeProvider::primaryKey
     */
    public function testPrimaryKey(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $this->getColumnType($column, $expectedColumn, $builder, $expectedBuilder);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\ColumnTypeProvider::primaryKeyWithRaw
     */
    public function testprimaryKeyWithRaw(
        string $columnRaw,
        string $column,
        Closure $builder,
        string $expectColumn = '',
    ): void {
        $this->getColumnTypeRaw($columnRaw, $column, $builder, $expectColumn);
    }
}
