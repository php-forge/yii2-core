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

    public function read(string $id): string
    {
        $data = $this->session->cache->get($this->calculateKey($id), false);

        return $data === false ? '' : $data;
    }

    public function write(string $id, string $data): bool
    {
        if ($this->session->getUseStrictMode() && $id === $this->session->_forceRegenerateId) {
            //Ignore write when forceRegenerate is active for this id
            return true;
        }

        return $this->session->cache->set($this->calculateKey($id), $data, $this->session->getTimeout());
    }

    public function destroy(string $id): bool
    {
        $cacheId = $this->calculateKey($id);

        if (!$this->session->cache->has($cacheId)) {
            return true;
        }

        return $this->session->cache->delete($cacheId);
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function gc(int $maxLifetime): false|int
    {
        return 0;
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
