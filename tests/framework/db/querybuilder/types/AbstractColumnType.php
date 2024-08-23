<?php

declare(strict_types=1);

namespace yiiunit\framework\db\querybuilder\types;

use Closure;
use yii\db\Connection;
use yii\db\SchemaBuilderTrait;

abstract class AbstractColumnType extends \yiiunit\TestCase
{
    use SchemaBuilderTrait;

    protected Connection|null $db = null;

    protected function getDb(): Connection
    {
        return $this->db;
    }

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function getColumnType(
        string $column,
        string $expectedColumn,
        Closure $builder,
        string $expectedBuilder,
    ): void {
        $columnSchemaBuilder = $this->db->schema->createColumnSchemaBuilder();
        $qb = $this->db->getQueryBuilder();

        $this->assertSame($expectedColumn, $builder($columnSchemaBuilder)->__toString());
        $this->assertSame($expectedBuilder, $qb->getColumnType($column));
        $this->assertSame($expectedBuilder, $qb->getColumnType($builder($columnSchemaBuilder)));
    }

    public function getColumnTypeRaw(string $sql, string $column, Closure $builder, string $expectedColumn): void
    {
        $columnSchemaBuilder = $this->db->schema->createColumnSchemaBuilder();
        $qb = $this->db->getQueryBuilder();

        if ($expectedColumn === '') {
            $expectedColumn = $sql;
        }

        $this->assertSame($column, $builder($columnSchemaBuilder)->__toString());
        $this->assertSame($expectedColumn, $qb->getColumnType($sql));
        $this->assertSame($expectedColumn, $qb->getColumnType($builder($columnSchemaBuilder)));
    }
}
