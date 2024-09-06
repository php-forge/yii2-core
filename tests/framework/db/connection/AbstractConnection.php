<?php

declare(strict_types=1);

namespace yiiunit\framework\db\connection;

use yii\db\Connection;
use yii\db\Schema;

abstract class AbstractConnection extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    protected function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testHasTable(): void
    {
        $tableName = 'T_table';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => Schema::TYPE_PK,
                'name' => Schema::TYPE_STRING,
            ],
        )->execute();

        $this->assertSame(0, $result);
        $this->assertTrue($this->db->hasTable($tableName));
        $this->assertNotNull($this->db->getTableSchema($tableName));

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
        $this->assertFalse($this->db->hasTable($tableName));
        $this->assertNull($this->db->getTableSchema($tableName));
    }
}
