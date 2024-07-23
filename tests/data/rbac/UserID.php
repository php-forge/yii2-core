<?php

declare(strict_types=1);

namespace yiiunit\data\rbac;

use Stringable;

class UserID implements Stringable
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
