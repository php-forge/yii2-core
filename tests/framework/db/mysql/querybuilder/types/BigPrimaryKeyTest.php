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
 * @group big-primary-key
 */
final class BigPrimaryKeyTest extends \yiiunit\framework\db\querybuilder\types\AbstractColumnType
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mysql\provider\types\BigPrimaryKeyProvider::queryBuilder
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
