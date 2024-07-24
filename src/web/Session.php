<?php

declare(strict_types=1);

namespace yii\web;

use SessionHandlerInterface;
use Yii;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Session provides session data management and the related configurations.
 *
 * Session is a Web application component that can be accessed via `Yii::$app->session`.
 *
 * To start the session, call [[open()]]; To complete and send out session data, call [[close()]];
 * To destroy the session, call [[destroy()]].
 *
 * Session can be used like an array to set and get session data. For example,
 *
 * ```php
 * $session = new Session;
 * $session->open();
 * $value1 = $session['name1'];  // get session variable 'name1'
 * $value2 = $session['name2'];  // get session variable 'name2'
 * foreach ($session as $name => $value) // traverse all session variables
 * $session['name3'] = $value3;  // set session variable 'name3'
 * ```
 *
 * Session can be extended to support customized session storage.
 * To do so, override [[useCustomStorage]] so that it returns true, and
 * override these methods with the actual logic about using custom storage:
 * [[openSession()]], [[closeSession()]], [[readSession()]], [[writeSession()]],
 * [[destroySession()]] and [[gcSession()]].
 *
 * Session also supports a special type of session data, called *flash messages*.
 * A flash message is available only in the current request and the next request.
 * After that, it will be deleted automatically. Flash messages are particularly
 * useful for displaying confirmation messages. To use flash messages, simply
 * call methods such as [[setFlash()]], [[getFlash()]].
 *
 * For more details and usage information on Session, see the [guide article on sessions](guide:runtime-sessions-cookies).
 *
 * @property-read array $allFlashes Flash messages (key => message or key => [message1, message2]).
 * @property-read string $cacheLimiter Current cache limiter.
 * @property-read array $cookieParams The session cookie parameters.
 * @property-read int $count The number of session variables.
 * @property-write string $flash The key identifying the flash message. Note that flash messages and normal
 * session variables share the same name space. If you have a normal session variable using the same name, its
 * value will be overwritten by this method.
 * @property float $gCProbability The probability (percentage) that the GC (garbage collection) process is
 * started on every session initialization.
 * @property bool $hasSessionId Whether the current request has sent the session ID.
 * @property string $id The current session ID.
 * @property-read bool $isActive Whether the session has started.
 * @property string $name The current session name.
 * @property string $savePath The current session save path, defaults to '/tmp'.
 * @property int $timeout The number of seconds after which data will be seen as 'garbage' and cleaned up. The
 * default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
 * @property bool|null $useCookies The value indicating whether cookies should be used to store session IDs.
 * @property-read bool $useCustomStorage Whether to use custom storage.
 * @property bool $useStrictMode Whether strict mode is enabled or not.
 * @property bool $useTransparentSessionID Whether transparent sid support is enabled or not, defaults to
 * false.
 */
class Session extends Component implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var string the name of the session variable that stores the flash message data.
     */
    public string $flashParam = '__flash';
    /**
     * @var SessionHandlerInterface|array an object implementing the SessionHandlerInterface or a configuration array.
     * If set, will be used to provide persistency instead of build-in methods.
     */
    public SessionHandlerInterface|array|null $handler = null;

    /**
     * @var string|null Holds the session id in case useStrictMode is enabled and the session id needs to be regenerated
     */
    protected string|null $_forceRegenerateId = null;

    /**
     * @var array parameter-value pairs to override default session cookie parameters that are used for
     * session_set_cookie_params() function Array may have the following possible keys: 'lifetime', 'path', 'domain',
     * 'secure', 'httponly'.
     *
     * @see https://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    private array $_cookieParams = ['httponly' => true];
    /**
     * @var array|null is used for saving session between recreations due to session parameters update.
     */
    private array|null $_frozenSessionData = null;
    /**
     * @var bool|null whether to use custom session storage.
     */
    private bool|null $_hasSessionId = null;

    /**
     * Initializes the application component.
     * This method is required by IApplicationComponent and is invoked by application.
     */
    public function init()
    {
        parent::init();

        register_shutdown_function([$this, 'close']);

        if ($this->getIsActive()) {
            Yii::warning('Session is already started', __METHOD__);
            $this->updateFlashCounters();
        }

        $this->registerSessionHandler();
    }

    public function open(string $path = '', string $name = ''): bool
    {
        return $this->handler->open($path, $name);
    }

    public function close(): bool
    {
        return $this->handler->close();
    }

    /**
     * Registers session handler.
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function registerSessionHandler(): void
    {
        if (is_array($this->handler)) {
            $this->handler = Yii::createObject($this->handler);
        }

        if ($this->handler === null) {
            $this->handler = new SessionHandler($this);
        }

        if (!$this->handler instanceof SessionHandlerInterface) {
            throw new InvalidConfigException(
                '"' . get_class($this) . '::handler" must implement the SessionHandlerInterface.'
            );
        }

        YII_DEBUG ? session_set_save_handler($this->handler, false): @session_set_save_handler($this->handler, false);
    }

    /**
     * @return bool whether the session has started.
     */
    public function getIsActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Returns a value indicating whether the current request has sent the session ID.
     *
     * The default implementation will check cookie and $_GET using the session name.
     * If you send session ID via other ways, you may need to override this method or call [[setHasSessionId()]] to
     * explicitly set whether the session ID is sent.
     *
     * @return bool whether the current request has sent the session ID.
     */
    public function getHasSessionId(): bool
    {
        if ($this->_hasSessionId === null) {
            $name = $this->getName();
            $request = Yii::$app->getRequest();

            if (!empty($_COOKIE[$name]) && ini_get('session.use_cookies')) {
                $this->_hasSessionId = true;
            } elseif (!ini_get('session.use_only_cookies') && ini_get('session.use_trans_sid')) {
                $this->_hasSessionId = $request->get($name) != '';
            } else {
                $this->_hasSessionId = false;
            }
        }

        return $this->_hasSessionId;
    }

    /**
     * Sets the value indicating whether the current request has sent the session ID.
     *
     * This method is provided so that you can override the default way of determining whether the session ID is sent.
     *
     * @param bool $value whether the current request has sent the session ID.
     */
    public function setHasSessionId(bool $value): void
    {
        $this->_hasSessionId = $value;
    }

    /**
     * Gets the session ID.
     * This is a wrapper for [PHP session_id()](https://www.php.net/manual/en/function.session-id.php).
     *
     * @return string the current session ID.
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Sets the session ID.
     * This is a wrapper for [PHP session_id()](https://www.php.net/manual/en/function.session-id.php).
     *
     * @param string $value the session ID for the current session.
     */
    public function setId(string $value): void
    {
        session_id($value);
    }

    /**
     * Updates the current session ID with a newly generated one.
     *
     * Please refer to <https://www.php.net/session_regenerate_id> for more details.
     *
     * This method has no effect when session is not [[getIsActive()|active]].
     * Make sure to call [[open()]] before calling it.
     *
     * @param bool $deleteOldSession Whether to delete the old associated session file or not.
     *
     * @see open()
     * @see isActive
     */
    public function regenerateID(bool $deleteOldSession = false): void
    {
        if ($this->getIsActive()) {
            // add @ to inhibit possible warning due to race condition
            // https://github.com/yiisoft/yii2/pull/1812
            if (YII_DEBUG && !headers_sent()) {
                session_regenerate_id($deleteOldSession);
            } else {
                @session_regenerate_id($deleteOldSession);
            }
        }
    }

    /**
     * Gets the name of the current session.
     * This is a wrapper for [PHP session_name()](https://www.php.net/manual/en/function.session-name.php).
     *
     * @return string the current session name.
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * Sets the name for the current session.
     * This is a wrapper for [PHP session_name()](https://www.php.net/manual/en/function.session-name.php).
     *
     * @param string $value the session name for the current session, must be an alphanumeric string.
     * It defaults to "PHPSESSID".
     */
    public function setName(string $value): void
    {
        $this->freeze();

        session_name($value);

        $this->unfreeze();
    }

    /**
     * Gets the current session save path.
     * This is a wrapper for [PHP session_save_path()](https://www.php.net/manual/en/function.session-save-path.php).
     *
     * @return string the current session save path, defaults to '/tmp'.
     */
    public function getSavePath(): string
    {
        return session_save_path();
    }

    /**
     * Sets the current session save path.
     * This is a wrapper for [PHP session_save_path()](https://www.php.net/manual/en/function.session-save-path.php).
     *
     * @param string $value the current session save path. This can be either a directory name or a
     * [path alias](guide:concept-aliases).
     *
     * @throws InvalidArgumentException if the path is not a valid directory.
     */
    public function setSavePath(string $value): void
    {
        $path = Yii::getAlias($value);

        if (is_dir($path)) {
            session_save_path($path);
        } else {
            throw new InvalidArgumentException("Session save path is not a valid directory: $value");
        }
    }

    /**
     * @return array the session cookie parameters.
     *
     * @see https://www.php.net/manual/en/function.session-get-cookie-params.php
     */
    public function getCookieParams(): array
    {
        return array_merge(session_get_cookie_params(), array_change_key_case($this->_cookieParams));
    }

    /**
     * Sets the session cookie parameters.
     * The cookie parameters passed to this method will be merged with the result
     * of `session_get_cookie_params()`.
     *
     * @param array $value cookie parameters, valid keys include: `lifetime`, `path`, `domain`, `secure` and `httponly`.
     * Starting with `sameSite` is also supported.
     * For security, an exception will be thrown if `sameSite` is set while using an unsupported version of PHP.
     * To use this feature across different PHP versions check the version first. E.g.
     * ```php
     * [
     *     'sameSite' => yii\web\Cookie::SAME_SITE_LAX,
     * ]
     * ```
     * See https://owasp.org/www-community/SameSite for more information about `sameSite`.
     *
     * @throws InvalidArgumentException if the parameters are incomplete.
     *
     * @see https://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    public function setCookieParams(array $value): void
    {
        $this->_cookieParams = $value;
    }

    /**
     * Returns the value indicating whether cookies should be used to store session IDs.
     *
     * @return bool|null the value indicating whether cookies should be used to store session IDs.
     *
     * @see setUseCookies()
     */
    public function getUseCookies(): bool|null
    {
        if (ini_get('session.use_cookies') === '0') {
            return false;
        } elseif (ini_get('session.use_only_cookies') === '1') {
            return true;
        }

        return null;
    }

    /**
     * Sets the value indicating whether cookies should be used to store session IDs.
     *
     * Three states are possible:
     *
     * - true: cookies and only cookies will be used to store session IDs.
     * - false: cookies will not be used to store session IDs.
     * - null: if possible, cookies will be used to store session IDs; if not, other mechanisms will be used
     *   (e.g. GET parameter)
     *
     * @param bool|null $value the value indicating whether cookies should be used to store session IDs.
     */
    public function setUseCookies(bool|null $value): void
    {
        $this->freeze();

        if ($value === false) {
            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
        } elseif ($value === true) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
        } else {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '0');
        }

        $this->unfreeze();
    }

    /**
     * @return float the probability (percentage) that the GC (garbage collection) process is started on every session
     * initialization.
     */
    public function getGCProbability(): float
    {
        return (float) (ini_get('session.gc_probability') / ini_get('session.gc_divisor') * 100);
    }

    /**
     * @param int $value the probability (percentage) that the GC (garbage collection) process is started on every
     * session initialization.
     *
     * @throws InvalidArgumentException if the value is not between 0 and 100.
     */
    public function setGCProbability(int $value): void
    {
        $this->freeze();

        $this->handler->gc($value);

        $this->unfreeze();
    }

    /**
     * @return bool whether transparent sid support is enabled or not, defaults to false.
     */
    public function getUseTransparentSessionID(): bool
    {
        return ini_get('session.use_trans_sid') == 1;
    }

    /**
     * @param bool $value whether transparent sid support is enabled or not.
     */
    public function setUseTransparentSessionID(bool $value): void
    {
        $this->freeze();

        ini_set('session.use_trans_sid', $value ? '1' : '0');

        $this->unfreeze();
    }

    /**
     * @return int the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * The default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
     */
    public function getTimeout(): int
    {
        return (int) ini_get('session.gc_maxlifetime');
    }

    /**
     * @param int $value the number of seconds after which data will be seen as 'garbage' and cleaned up.
     */
    public function setTimeout(int $value): void
    {
        $this->freeze();

        ini_set('session.gc_maxlifetime', $value);

        $this->unfreeze();
    }

    /**
     * @param bool $value Whether strict mode is enabled or not.
     * When `true` this setting prevents the session component to use an uninitialized session ID.
     * Note: Enabling `useStrictMode` on PHP < 5.5.2 is only supported with custom storage classes.
     * Warning! Although enabling strict mode is mandatory for secure sessions, the default value of
     * 'session.use-strict-mode' is `0`.
     *
     * @see https://www.php.net/manual/en/session.configuration.php#ini.session.use-strict-mode
     */
    public function setUseStrictMode(bool $value): void
    {
        $this->freeze();

        ini_set('session.use_strict_mode', $value ? '1' : '0');

        $this->unfreeze();
    }

    /**
     * @return bool Whether strict mode is enabled or not.
     *
     * @see setUseStrictMode()
     */
    public function getUseStrictMode(): bool
    {
        return (bool) ini_get('session.use_strict_mode');
    }

    /**
     * Returns an iterator for traversing the session variables.
     * This method is required by the interface [[\IteratorAggregate]].
     * @return SessionIterator an iterator for traversing the session variables.
     */
    public function getIterator(): SessionIterator
    {
        $this->open();

        return new SessionIterator();
    }

    /**
     * Returns the number of items in the session.
     *
     * @return int the number of session variables.
     */
    public function getCount(): int
    {
        $this->open();

        return count($_SESSION);
    }

    /**
     * Returns the number of items in the session.
     * This method is required by [[\Countable]] interface.
     *
     * @return int number of items in the session.
     */
    public function count(): int
    {
        return $this->getCount();
    }

    /**
     * Returns the session variable value with the session variable name.
     * If the session variable does not exist, the `$defaultValue` will be returned.
     *
     * @param string $key the session variable name
     * @param mixed $defaultValue the default value to be returned when the session variable does not exist.
     *
     * @return mixed the session variable value, or $defaultValue if the session variable does not exist.
     */
    public function get(string $key, mixed $defaultValue = null): mixed
    {
        $this->open();

        return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
    }

    /**
     * Adds a session variable.
     * If the specified name already exists, the old value will be overwritten.
     *
     * @param string $key session variable name.
     * @param mixed $value session variable value.
     */
    public function set(string $key, mixed $value): void
    {
        $this->open();

        $_SESSION[$key] = $value;
    }

    /**
     * Removes a session variable.
     *
     * @param string $key the name of the session variable to be removed.
     *
     * @return mixed the removed value, null if no such session variable.
     */
    public function remove(string $key): mixed
    {
        $this->open();

        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);

            return $value;
        }

        return null;
    }

    /**
     * Removes all session variables.
     */
    public function removeAll(): void
    {
        $this->open();

        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * @param mixed $key session variable name.
     *
     * @return bool whether there is the named session variable.
     */
    public function has(mixed $key): bool
    {
        $this->open();

        return isset($_SESSION[$key]);
    }

    /**
     * Updates the counters for flash messages and removes outdated flash messages.
     * This method should only be called once in [[init()]].
     */
    protected function updateFlashCounters(): void
    {
        $counters = $this->get($this->flashParam, []);

        if (is_array($counters)) {
            foreach ($counters as $key => $count) {
                if ($count > 0) {
                    unset($counters[$key], $_SESSION[$key]);
                } elseif ($count == 0) {
                    $counters[$key]++;
                }
            }
            $_SESSION[$this->flashParam] = $counters;
        } else {
            // fix the unexpected problem that flashParam doesn't return an array
            unset($_SESSION[$this->flashParam]);
        }
    }

    /**
     * Returns a flash message.
     *
     * @param string $key the key identifying the flash message.
     * @param mixed $defaultValue value to be returned if the flash message does not exist.
     * @param bool $delete whether to delete this flash message right after this method is called.
     * If false, the flash message will be automatically deleted in the next request.
     *
     * @return mixed the flash message or an array of messages if addFlash was used.
     *
     * @see setFlash()
     * @see addFlash()
     * @see hasFlash()
     * @see getAllFlashes()
     * @see removeFlash()
     */
    public function getFlash(string $key, mixed $defaultValue = null, bool $delete = false): mixed
    {
        $counters = $this->get($this->flashParam, []);
        if (isset($counters[$key])) {
            $value = $this->get($key, $defaultValue);
            if ($delete) {
                $this->removeFlash($key);
            } elseif ($counters[$key] < 0) {
                // mark for deletion in the next request
                $counters[$key] = 1;
                $_SESSION[$this->flashParam] = $counters;
            }

            return $value;
        }

        return $defaultValue;
    }

    /**
     * Returns all flash messages.
     *
     * You may use this method to display all the flash messages in a view file:
     *
     * ```php
     * <?php
     * foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
     *     echo '<div class="alert alert-' . $key . '">' . $message . '</div>';
     * } ?>
     * ```
     *
     * With the above code you can use the [bootstrap alert][] classes such as `success`, `info`, `danger` as the flash
     * message key to influence the color of the div.
     *
     * Note that if you use [[addFlash()]], `$message` will be an array, and you will have to adjust the above code.
     *
     * [bootstrap alert]: https://getbootstrap.com/docs/3.4/components/#alerts
     *
     * @param bool $delete whether to delete the flash messages right after this method is called.
     * If false, the flash messages will be automatically deleted in the next request.
     *
     * @return array flash messages (key => message or key => [message1, message2]).
     *
     * @see setFlash()
     * @see addFlash()
     * @see getFlash()
     * @see hasFlash()
     * @see removeFlash()
     */
    public function getAllFlashes(bool $delete = false): array
    {
        $counters = $this->get($this->flashParam, []);

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

        $_SESSION[$this->flashParam] = $counters;

        return $flashes;
    }

    /**
     * Sets a flash message.
     * A flash message will be automatically deleted after it is accessed in a request and the deletion will happen in
     * the next request.
     * If there is already an existing flash message with the same key, it will be overwritten by the new one.
     *
     * @param string $key the key identifying the flash message. Note that flash messages and normal session variables
     * share the same name space. If you have a normal session variable using the same name, its value will be
     * overwritten by this method.
     *
     * @param mixed $value flash message.
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if it is accessed.
     * If false, the flash message will be automatically removed after the next request, regardless if it is accessed or
     * not. If true (default value), the flash message will remain until after it is accessed.
     *
     * @see getFlash()
     * @see addFlash()
     * @see removeFlash()
     */
    public function setFlash(string $key, mixed $value = true, bool $removeAfterAccess = true): void
    {
        $counters = $this->get($this->flashParam, []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $_SESSION[$key] = $value;
        $_SESSION[$this->flashParam] = $counters;
    }

    /**
     * Adds a flash message.
     * If there are existing flash messages with the same key, the new one will be appended to the existing message
     * array.
     *
     * @param string $key the key identifying the flash message.
     * @param mixed $value flash message
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if it is accessed.
     * If false, the flash message will be automatically removed after the next request, regardless if it is accessed or
     * not. If true (default value), the flash message will remain until after it is accessed.
     *
     * @see getFlash()
     * @see setFlash()
     * @see removeFlash()
     */
    public function addFlash(string $key, mixed $value = true, bool $removeAfterAccess = true): void
    {
        $counters = $this->get($this->flashParam, []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $_SESSION[$this->flashParam] = $counters;

        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = [$value];
        } elseif (is_array($_SESSION[$key])) {
            $_SESSION[$key][] = $value;
        } else {
            $_SESSION[$key] = [$_SESSION[$key], $value];
        }
    }

    /**
     * Removes a flash message.
     *
     * @param string $key the key identifying the flash message. Note that flash messages and normal session variables
     * share the same name space.  If you have a normal session variable using the same name, it will be removed by this
     * method.
     *
     * @return mixed the removed flash message. Null if the flash message does not exist.
     *
     * @see getFlash()
     * @see setFlash()
     * @see addFlash()
     * @see removeAllFlashes()
     */
    public function removeFlash(string $key): mixed
    {
        $counters = $this->get($this->flashParam, []);
        $value = isset($_SESSION[$key], $counters[$key]) ? $_SESSION[$key] : null;

        unset($counters[$key], $_SESSION[$key]);

        $_SESSION[$this->flashParam] = $counters;

        return $value;
    }

    /**
     * Removes all flash messages.
     * Note that flash messages and normal session variables share the same name space.
     * If you have a normal session variable using the same name, it will be removed by this method.
     *
     * @see getFlash()
     * @see setFlash()
     * @see addFlash()
     * @see removeFlash()
     */
    public function removeAllFlashes(): void
    {
        $counters = $this->get($this->flashParam, []);

        foreach (array_keys($counters) as $key) {
            unset($_SESSION[$key]);
        }

        unset($_SESSION[$this->flashParam]);
    }

    /**
     * Returns a value indicating whether there are flash messages associated with the specified key.
     *
     * @param string $key key identifying the flash message type.
     *
     * @return bool whether any flash messages exist under specified key.
     */
    public function hasFlash(string $key): bool
    {
        return $this->getFlash($key) !== null;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param int|string $offset the offset to check on.
     *
     * @return bool whether or not an offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->open();

        return isset($_SESSION[$offset]);
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param int|string $offset the offset to retrieve element.
     *
     * @return mixed the element at the offset, null if no element is found at the offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->open();

        return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param mixed $offset the offset to set element.
     * @param mixed $item the element value.
     */
    public function offsetSet(mixed $offset, mixed $item): void
    {
        $this->open();

        $_SESSION[$offset] = $item;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->open();

        unset($_SESSION[$offset]);
    }

    /**
     * This function saves session data to temporary variable and stop session.
     */
    protected function freeze(): void
    {
        if ($this->getIsActive()) {
            if (isset($_SESSION)) {
                $this->_frozenSessionData = $_SESSION;
            }

            $this->close();

            Yii::info('Session frozen', __METHOD__);
        }
    }

    /**
     * Starts session and restores data from temporary variable.
     */
    protected function unfreeze(): void
    {
        if (null !== $this->_frozenSessionData) {
            YII_DEBUG ? session_start() : @session_start();

            if ($this->getIsActive()) {
                Yii::info('Session unfrozen', __METHOD__);
            } else {
                $error = error_get_last();
                $message = isset($error['message']) ? $error['message'] : 'Failed to unfreeze session.';
                Yii::error($message, __METHOD__);
            }

            $_SESSION = $this->_frozenSessionData;
            $this->_frozenSessionData = null;
        }
    }

    /**
     * Set cache limiter.
     *
     * @param string $cacheLimiter cache limiter.
     */
    public function setCacheLimiter(string $cacheLimiter): void
    {
        $this->freeze();

        session_cache_limiter($cacheLimiter);

        $this->unfreeze();
    }

    /**
     * Returns current cache limiter.
     *
     * @return string current cache limiter.
     */
    public function getCacheLimiter(): string
    {
        return session_cache_limiter();
    }
}
