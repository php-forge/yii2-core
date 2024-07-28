<?php

declare(strict_types=1);

namespace yii\web\session\handler;

use Psr\SimpleCache\CacheInterface;
use yii\web\session\SessionHandlerInterface;
use Yiisoft\Cache\CacheKeyNormalizer;

/**
 * CacheSessionHandler uses cache to store session data.
 */
class CacheSessionHandler implements SessionHandlerInterface
{
    private string $forceRegenerateId = '';
    private int|null $timeout = 0;
    private bool $useStrictMode = false;

    public function __construct(private CacheInterface $cache)
    {
        $this->timeout = (int) ini_get('session.gc_maxlifetime');
        $this->useStrictMode = (bool) ini_get('session.use_strict_mode');
    }

    public function open(string $savePath, string $sessionName): bool
    {
        if ($this->useStrictMode) {
            $id = session_id();

            if (!$this->cache->has($this->calculateKey($id))) {
                //This session id does not exist, mark it for forced regeneration
                $this->forceRegenerateId = $id;
            }
        }

        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id,  mixed $defaultValue = ''): string
    {
        $data = $this->cache->get($this->calculateKey($id), false);

        return $data === false ? $defaultValue : $data;
    }

    public function write(string $id, string $data): bool
    {
        if ($this->useStrictMode && $id === $this->forceRegenerateId) {
            //Ignore write when forceRegenerate is active for this id
            return true;
        }

        return $this->cache->set($this->calculateKey($id), $data, $this->timeout);
    }

    public function destroy(string $id): bool
    {
        $cacheId = $this->calculateKey($id);

        if ($this->cache->has($cacheId) === false) {
            return true;
        }

        return $this->cache->delete($cacheId);
    }

    public function gc(int $maxLifetime): int|false
    {
        return 0;
    }

    public function isRegenerateId(): bool
    {
        return $this->forceRegenerateId !== '';
    }

    private function calculateKey($id): string
    {
        return CacheKeyNormalizer::normalize([__CLASS__, $id]);
    }
}
