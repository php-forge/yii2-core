<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\querybuilder;

use Closure;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group querybuilder
 * @group column-schema-builder
 * @group column-type
 */
final class ColumnTypeTest extends \yiiunit\framework\db\querybuilder\AbstractColumnType
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::autoIncrement
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::autoIncrementWithRaw
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::bigAutoIncrement
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::bigAutoIncrementWithRaw
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::bigPrimaryKey
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::bigPrimaryKeyWithRaw
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::primaryKey
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
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::primaryKeyWithRaw
     */
    public function testprimaryKeyWithRaw(
        string $columnRaw,
        string $column,
        Closure $builder,
        string $expectColumn = '',
    ): void {
        $this->getColumnTypeRaw($columnRaw, $column, $builder, $expectColumn);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::unsignedBigPrimaryKey
     */
    public function testUnsignedBigPrimaryKey(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $this->getColumnType($column, $expectedColumn, $builder, $expectedBuilder);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::unsignedBigPrimaryKeyWithRaw
     */
    public function testUnsignedBigPrimaryKeyWithRaw(
        string $columnRaw,
        string $column,
        Closure $builder,
        string $expectColumn = '',
    ): void {
        $this->getColumnTypeRaw($columnRaw, $column, $builder, $expectColumn);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::unsignedPrimaryKey
     */
    public function testUnsignedPrimaryKey(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $this->getColumnType($column, $expectedColumn, $builder, $expectedBuilder);
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\ColumnTypeProvider::unsignedPrimaryKeyWithRaw
     */
    public function testUnsignedPrimaryKeyWithRaw(
        string $columnRaw,
        string $column,
        Closure $builder,
        string $expectColumn = '',
    ): void {
        $this->getColumnTypeRaw($columnRaw, $column, $builder, $expectColumn);
    }
}
