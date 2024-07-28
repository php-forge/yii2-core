<?php

declare(strict_types=1);

namespace yii\web\session;

/**
 * SessionIterator implements an [[\Iterator|iterator]] for traversing session variables managed by [[Session]].
 */
class SessionIterator implements \Iterator
{
    /**
     * @var array list of keys in the map
     */
    private array $_keys = [];
    /**
     * @var string|int|false current key
     */
    private string|int|false $_key = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_keys = array_keys(isset($_SESSION) ? $_SESSION : []);

        $this->rewind();
    }

    /**
     * Rewinds internal array pointer.
     * This method is required by the interface [[\Iterator]].
     */
    public function rewind(): void
    {
        $this->_key = reset($this->_keys);
    }

    /**
     * Returns the key of the current array element.
     * This method is required by the interface [[\Iterator]].
     *
     * @return string|int|null the key of the current array element
     */
    public function key(): string|int|null
    {
        return $this->_key === false ? null : $this->_key;
    }

    /**
     * Returns the current array element.
     * This method is required by the interface [[\Iterator]].
     *
     * @return mixed the current array element
     */
    public function current(): mixed
    {
        return $this->_key !== false && isset($_SESSION[$this->_key]) ? $_SESSION[$this->_key] : null;
    }

    /**
     * Moves the internal pointer to the next array element.
     * This method is required by the interface [[\Iterator]].
     */
    public function next(): void
    {
        do {
            $this->_key = next($this->_keys);
        } while ($this->_key !== false && !isset($_SESSION[$this->_key]));
    }

    /**
     * Returns whether there is an element at current position.
     * This method is required by the interface [[\Iterator]].
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->_key !== false;
    }
}
