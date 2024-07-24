<?php

declare(strict_types=1);

namespace yii\web;

use SessionHandlerInterface;
use Yiisoft\Cache\CacheKeyNormalizer;

class CacheSessionHandler implements SessionHandlerInterface
{

    public function __construct(private CacheSession $session)
    {
    }

    /**
     * Session open handler.
     *
     * @param string $savePath session save path.
     * @param string $sessionName session name.
     *
     * @return bool whether session is opened successfully.
     */
    public function open(string $savePath, string $sessionName): bool
    {
        if ($this->session->getUseStrictMode()) {
            $id = $this->session->getId();

            if (!$this->session->cache->has($this->calculateKey($id))) {
                //This session id does not exist, mark it for forced regeneration
                $this->session->_forceRegenerateId = $id;
            }
        }

        return true;
    }

    /**
     * Session read handler.
     *
     * @param string $id session ID.
     *
     * @return string the session data.
     */
    public function read(string $id): string
    {
        $data = $this->session->cache->get($this->calculateKey($id), false);

        return $data === false ? '' : $data;
    }

    /**
     * Session write handler.
     *
     * @param string $id session ID.
     * @param string $data session data.
     *
     * @return bool whether session write is successful.
     */
    public function write(string $id, string $data): bool
    {
        if ($this->session->getUseStrictMode() && $id === $this->session->_forceRegenerateId) {
            //Ignore write when forceRegenerate is active for this id
            return true;
        }

        return $this->session->cache->set($this->calculateKey($id), $data, $this->session->getTimeout());
    }

    /**
     * Session destroy handler.
     *
     * @param string $id session ID.
     *
     * @return bool whether session is destroyed successfully.
     */
    public function destroy(string $id): bool
    {
        $cacheId = $this->calculateKey($id);

        if (!$this->session->cache->has($cacheId)) {
            return true;
        }

        return $this->session->cache->delete($cacheId);
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function gc(int $maxLifetime): bool
    {
        return true;
    }

    /**
     * Generates a unique key used for storing session data in cache.
     *
     * @param string $id session variable name.
     *
     * @return string a safe cache key associated with the session variable name.
     */
    protected function calculateKey(string $id): string
    {
        return CacheKeyNormalizer::normalize([get_class($this->session), $id]);
    }
}
