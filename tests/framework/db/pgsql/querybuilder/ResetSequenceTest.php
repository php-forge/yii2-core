<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\querybuilder;

use yii\base\InvalidArgumentException;
use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group querybuilder
 * @group reset-sequence
 */
final class ResetSequenceTest extends \yiiunit\framework\db\querybuilder\AbstractResetSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\QueryBuilderProvider::resetSequence
     */
    public function testResetSequence(string $tableName, string $columnPK, int|null $value, string $expected): void
    {
        parent::testResetSequence($tableName, $columnPK, $value, $expected);
    }
}
