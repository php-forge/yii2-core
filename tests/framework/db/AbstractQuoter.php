<?php

declare(strict_types=1);

namespace yiiunit\framework\db;

use yii\db\Connection;
use yiiunit\TestCase;

abstract class AbstractQuoter extends TestCase
{
    protected Connection $db;
    protected string $driverName = '';

    abstract public function getConnection(bool $fixture = false): Connection;

    public function testQuoteValueNotString(): void
    {
        $quoter = $this->getConnection()->getQuoter();

        $this->assertFalse($quoter->quoteValue(false));
        $this->assertTrue($quoter->quoteValue(true));
        $this->assertSame(1, $quoter->quoteValue(1));
        $this->assertSame([], $quoter->quoteValue([]));
    }
}
