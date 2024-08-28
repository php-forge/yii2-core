<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\querybuilder;

use yii\db\Connection;
use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group querybuilder
 * @group sequence
 */
final class SequenceTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    protected function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::createSequence
     */
    public function testCreateSequence(
        string $table,
        int $start,
        int $increment,
        array $options,
        string $expectedSQL
    ): void {
        $sql = $this->db->queryBuilder->createSequence($table, $start, $increment, $options);

        $this->assertSame($expectedSQL, $sql);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::dropSequence
     */
    public function testDropSequence(string $table, string $expectedSQL): void
    {
        $sql = $this->db->queryBuilder->dropSequence($table);

        $this->assertSame($expectedSQL, $sql);
    }

    /**
     * @dataProvider \yiiunit\framework\db\oci\provider\QueryBuilderProvider::resetSequence
     */
    public function testResetSequence(string $table, int $value, array $options, string $expectedSQL): void
    {
        $sql = $this->db->queryBuilder->resetSequence($table, $value, $options);

        $this->assertSame($expectedSQL, $sql);
    }
}
