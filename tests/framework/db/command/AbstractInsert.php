<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command;

use yii\db\Connection;

abstract class AbstractInsert extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testInsertWithReturningPks(): void
    {
        $this->assertEquals(
            ['C_id_1' => '1', 'C_id_2' => '2'],
            $this->db
                ->createCommand()
                ->insertWithReturningPks('T_constraints_2', ['C_id_1' => 1, 'C_id_2' => 2]),
        );
    }

    public function testInsertWithReturningPksEmptyValues(): void
    {
        $this->assertEquals(
            ['id' => '1'],
            $this->db->createCommand()->insertWithReturningPks('null_values', []),
        );
    }

    public function testInsertWithReturningPksEmptyValuesAndNoPk(): void
    {
        $this->assertSame(
            1,
            $this->db->createCommand()->insertWithReturningPks('negative_default_values', []),
        );
    }
}
