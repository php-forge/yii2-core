<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\schema;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group schema
 * @group sequence
 */
final class SequenceTest extends \yiiunit\framework\db\schema\AbstractSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection(true);
    }

    public function testFindTableSequenceFromTriggers(): void
    {
        $sequenceName = $this->db->getSchema()->findTableSequenceFromTriggers('{{%profile}}');

        $this->assertSame('profile_SEQ', $sequenceName);
    }

    public function testFindTableSequenceFromTriggersWithSequenceNotExist(): void
    {
        $this->assertFalse($this->db->getSchema()->findTableSequenceFromTriggers('{{%not_exists}}'));
    }
}