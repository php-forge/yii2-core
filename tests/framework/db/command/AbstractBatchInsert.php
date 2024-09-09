<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command;

use yii\db\Connection;
use yii\db\Query;

abstract class AbstractBatchInsert extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        $command = $this->db->createCommand();
        $command->batchInsert($table, $columns, $values);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->params);

        $command->prepare(false);
        $command->execute();

        $this->assertEquals($insertedRow, (new Query())->from($table)->count(db: $this->db));
    }
}
