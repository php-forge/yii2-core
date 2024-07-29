<?php

declare(strict_types=1);

namespace yii\web\session;

use yii\base\Component;

use function is_array;

class Flash extends Component
{
    /**
     * @var string the name of the session variable that stores the counter for flash messages.
     */
    private const COUNTERS = '__counters';
    /**
     * @var string the name of the session variable that stores the flash message data.
     */
    private const FLASH_PARAM = '__flash';

    /**
     * @var Session the session object to be used.
     */
    private Session $session;

    public function __construct(Session $session, array $config = [])
    {
        $this->session = $session;

        parent::__construct($config);
    }

    public function get(string $key)
    {
        $flashes = $this->fetch();

        if (!isset($flashes[$key], $flashes[self::COUNTERS][$key])) {
            return null;
        }

        if ($flashes[self::COUNTERS][$key] < 0) {
            // Mark for deletion in the next request.
            $flashes[self::COUNTERS][$key] = 1;

            $this->save($flashes);
        }

        return $flashes[$key];
    }

    public function getAll(): array
    {
        $flashes = $this->fetch();

        $list = [];

        foreach ($flashes as $key => $value) {
            if ($key === self::COUNTERS) {
                continue;
            }

            $list[$key] = $value;

            if ($flashes[self::COUNTERS][$key] < 0) {
                // Mark for deletion in the next request.
                $flashes[self::COUNTERS][$key] = 1;
            }
        }

        $this->save($flashes);

        return $list;
    }

    public function set(string $key, $value = true, bool $removeAfterAccess = true): void
    {
        $flashes = $this->fetch();

        /** @psalm-suppress MixedArrayAssignment */
        $flashes[self::COUNTERS][$key] = $removeAfterAccess ? -1 : 0;
        $flashes[$key] = $value;

        $this->save($flashes);
    }

    public function add(string $key, $value = true, bool $removeAfterAccess = true): void
    {
        $flashes = $this->fetch();

        /** @psalm-suppress MixedArrayAssignment */
        $flashes[self::COUNTERS][$key] = $removeAfterAccess ? -1 : 0;

        if (empty($flashes[$key])) {
            $flashes[$key] = [$value];
        } elseif (is_array($flashes[$key])) {
            $flashes[$key][] = $value;
        } else {
            $flashes[$key] = [$flashes[$key], $value];
        }

        $this->save($flashes);
    }

    public function remove(string $key): void
    {
        $flashes = $this->fetch();
        unset($flashes[self::COUNTERS][$key], $flashes[$key]);
        $this->save($flashes);
    }

    public function removeAll(): void
    {
        $this->save([self::COUNTERS => []]);
    }

    public function has(string $key): bool
    {
        $flashes = $this->fetch();

        return isset($flashes[$key], $flashes[self::COUNTERS][$key]);
    }

    /**
     * Updates the counters for flash messages and removes outdated flash messages.
     * This method should be called once after session initialization.
     */
    private function updateCounters(): void
    {
        $flashes = $this->session->get(self::FLASH_PARAM, []);

        if (!is_array($flashes)) {
            $flashes = [self::COUNTERS => []];
        }

        $counters = $flashes[self::COUNTERS] ?? [];

        if (!is_array($counters)) {
            $counters = [];
        }

        /** @var array<string, int> $counters */
        foreach ($counters as $key => $count) {
            if ($count > 0) {
                unset($counters[$key], $flashes[$key]);
            } elseif ($count === 0) {
                $counters[$key]++;
            }
        }

        $flashes[self::COUNTERS] = $counters;

        $this->save($flashes);
    }

    /**
     * Obtains flash messages. Updates counters once per session.
     *
     * @return array Flash messages array.
     *
     * @psalm-return array{__counters:array<string,int>}&array
     */
    private function fetch(): array
    {
        // Ensure session is active (and has id).
        $this->session->open();

        if ($this->session->getIsActive()) {
            $this->updateCounters();
        }

        /** @psalm-var array{__counters:array<string,int>}&array */
        return $this->session->get(self::FLASH_PARAM, []);
    }

    /**
     * Save flash messages into session.
     *
     * @param array $flashes Flash messages to save.
     */
    private function save(array $flashes): void
    {
        $this->session->set(self::FLASH_PARAM, $flashes);
    }
}
