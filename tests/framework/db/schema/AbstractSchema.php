<?php

declare(strict_types=1);

namespace yiiunit\framework\db\schema;

use yii\db\Connection;
use yiiunit\TestCase;

abstract class AbstractSchema extends TestCase
{
    protected Connection|null $db = null;
    protected array $columnsSchema = [];

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testgetNextAutoIncrementValue(): void
    {
        if ($this->db->hasTable('T_autoincrement')) {
            $result = $this->db->createCommand()->dropTable('T_autoincrement')->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable('T_autoincrement', $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementValue('T_autoincrement', 'id'));

        $result = $this->db->createCommand()->insert('T_autoincrement', ['name' => 'test_1'])->execute();

        $this->assertSame(1, $result);

        $result = $this->db->createCommand()->insert('T_autoincrement', ['name' => 'test_2'])->execute();

        $this->assertSame(1, $result);

        $this->assertSame(3, $this->db->getSchema()->getNextAutoIncrementValue('T_autoincrement', 'id'));
    }
}
