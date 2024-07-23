<?php

declare(strict_types=1);

namespace yii\web;

use SessionHandlerInterface;

class SessionHandler implements SessionHandlerInterface
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public function open(string $savePath, string $sessionName): bool
    {
        return $this->session->openSession($savePath, $sessionName);
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        return $this->session->closeSession();
    }

    /**
     * @inheritDoc
     */
    public function read(string $id): string
    {
        return $this->session->readSession($id);
    }

    /**
     * @inheritDoc
     */
    public function write(string $id, string $data): bool
    {
        return $this->session->writeSession($id, $data);
    }

    /**
     * @inheritDoc
     */
    public function destroy(string $id): bool
    {
        return $this->session->destroySession($id);
    }

    /**
     * @inheritDoc
     */
    public function gc(int $maxlifetime): int|false
    {
        return $this->session->gcSession($maxlifetime);
    }
}
