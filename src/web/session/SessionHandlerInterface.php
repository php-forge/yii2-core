<?php

declare(strict_types=1);

namespace yii\web\session;

interface SessionHandlerInterface extends \SessionHandlerInterface
{
    /**
     * @return bool whether id session is regenerated.
     */
    public function isRegenerateId(): bool;
}
