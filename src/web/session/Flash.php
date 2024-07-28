<?php

declare(strict_types=1);

namespace yii\web\session;

use yii\base\Component;

use function array_keys;
use function array_key_exists;
use function is_array;

class Flash extends Component
{
    /**
     * @var string the name of the session variable that stores the flash message data.
     */
    public string $flashParam = '__flash';
    private Session $session;

    public function __construct(Session $session, array $config = [])
    {
        $this->session = $session;

        parent::__construct($config);
    }

    /**
     * Adds a flash message.
     * If there are existing flash messages with the same key, the new one will be appended to the existing message
     * array.
     *
     * @param string $key the key identifying the flash message.
     * @param mixed $value flash message.
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if it is accessed.
     * If false, the flash message will be automatically removed after the next request, regardless if it is accessed or
     * not. If true (default value), the flash message will remain until after it is accessed.
     *
     * @see get()
     * @see set()
     * @see remove()
     */
    public function add(string $key, mixed $value = true, bool $removeAfterAccess = true): void
    {
        $counters = $this->session->get($this->flashParam, []);

        $counters[$key] = $removeAfterAccess ? -1 : 0;

        $this->session->set($this->flashParam, $counters);

        if ($this->session->has($key) === false) {
            $this->session->set($key, $value);
        } elseif (is_array($_SESSION[$key])) {
            $_SESSION[$key][] = $value;
        } else {
            $_SESSION[$key] = [$_SESSION[$key], $value];
        }
    }

    public function get(string $key, mixed $defaultValue = null, bool $delete = false): mixed
    {
        $counters = $this->session->get($this->flashParam, []);

        if (isset($counters[$key])) {
            $value = $this->session->get($key, $defaultValue);

            if ($delete) {
                $this->removeFlash($key);
            } elseif ($counters[$key] < 0) {
                // mark for deletion in the next request
                $counters[$key] = 1;
                $this->session->set($this->flashParam, $counters);
            }

            return $value;
        }

        return $defaultValue;
    }

    public function getAll(bool $delete = false): array
    {
        $counters = $this->session->get($this->flashParam, []);
        $flashes = [];

        foreach (array_keys($counters) as $key) {
            if (array_key_exists($key, $_SESSION)) {
                $flashes[$key] = $_SESSION[$key];

                if ($delete) {
                    unset($counters[$key], $_SESSION[$key]);
                } elseif ($counters[$key] < 0) {
                    // mark for deletion in the next request
                    $counters[$key] = 1;
                }
            } else {
                unset($counters[$key]);
            }
        }

        $this->session->set($this->flashParam, $counters);

        return $flashes;
    }

    public function remove(string $key): mixed
    {
        $counters = $this->session->get($this->flashParam, []);

        $value = isset($_SESSION[$key], $counters[$key]) ? $_SESSION[$key] : null;

        unset($counters[$key], $_SESSION[$key]);

        $this->session->set($this->flashParam, $counters);

        return $value;
    }

    public function removeAll(): void
    {
        $counters = $this->session->get($this->flashParam, []);

        foreach (array_keys($counters) as $key) {
            unset($_SESSION[$key]);
        }

        unset($_SESSION[$this->flashParam]);
    }

    public function setFlash(string $key, mixed $value = true, bool $removeAfterAccess = true): void
    {
        $counters = $this->session->get($this->flashParam, []);

        $counters[$key] = $removeAfterAccess ? -1 : 0;

        $this->session->set($key, $value);
        $this->session->set($this->flashParam, $counters);
    }

    /**
     * Updates the counters for flash messages and removes outdated flash messages.
     * This method should only be called once in [[init()]].
     */
    public function updateCounters(): void
    {
        $counters = $this->session->get($this->flashParam, []);

        if (is_array($counters)) {
            foreach ($counters as $key => $count) {
                if ($count > 0) {
                    unset($counters[$key], $_SESSION[$key]);
                } elseif ($count == 0) {
                    $counters[$key]++;
                }
            }
            $this->session->set($this->flashParam, $counters);
        } else {
            // fix the unexpected problem that flashParam doesn't return an array
            $this->session->set($this->flashParam, []);
        }
    }
}
