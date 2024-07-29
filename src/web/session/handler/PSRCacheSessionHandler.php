<?php

declare(strict_types=1);

namespace yii\web\session\handler;

use Psr\SimpleCache\CacheInterface;
use yii\web\session\SessionHandlerInterface;
use Yiisoft\Cache\CacheKeyNormalizer;

/**
 * PSRCacheSessionHandler uses cache to store session data.
 */
class PSRCacheSessionHandler implements SessionHandlerInterface
{
    /**
     * @var string The session id that needs to be regenerated.
     */
    private string $forceRegenerateId = '';

    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function open(string $savePath, string $sessionName): bool
    {
        $strictMode = (bool) ini_get('session.use_strict_mode');

        if ($strictMode) {
            $id = session_id();

            if (!$this->cache->has($this->calculateKey($id))) {
                //This session id does not exist, mark it for forced regeneration
                $this->forceRegenerateId = $id;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $id,  mixed $defaultValue = ''): string
    {
        $data = $this->cache->get($this->calculateKey($id), false);

        return $data === false ? $defaultValue : $data;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $id, string $data): bool
    {
        $strictMode = (bool) ini_get('session.use_strict_mode');
        $timeout = (int) ini_get('session.gc_maxlifetime');

        if ($strictMode && $id === $this->forceRegenerateId) {
            //Ignore write when forceRegenerate is active for this id
            return true;
        }

        return $this->cache->set($this->calculateKey($id), $data, $timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $id): bool
    {
        $cacheId = $this->calculateKey($id);

        if ($this->cache->has($cacheId) === false) {
            return true;
        }

        return $this->cache->delete($cacheId);
    }

    /**
     * {@inheritdoc}
     */
    public function gc(int $maxLifetime): int|false
    {
        return 0;
    }

    /**
     * @return bool Whether the session id needs to be regenerated.
     */
    public function isRegenerateId(): bool
    {
        return $this->forceRegenerateId !== '';
    }

    /**
     * @return string Normalized cache key.
     */
    private function calculateKey($id): string
    {
        return CacheKeyNormalizer::normalize([__CLASS__, $id]);
    }
}
