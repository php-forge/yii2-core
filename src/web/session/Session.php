<?php

declare(strict_types=1);

namespace yii\web\session;

use Yii;
use yii\base\Component;
use yii\base\InvalidArgumentException;

/**
 * @property-read array $allFlashes Flash messages (key => message or key => [message1, message2]).
 * @property-read string $cacheLimiter Current cache limiter.
 * @property-read array $cookieParams The session cookie parameters.
 * @property-read int $count The number of session variables.
 * @property float $gCProbability The probability (percentage) that the GC (garbage collection) process is started on
 * every session initialization.
 * @property bool $hasSessionId Whether the current request has sent the session ID.
 * @property string $id The current session ID.
 * @property-read bool $isActive Whether the session has started.
 * @property string $name The current session name.
 * @property string $savePath The current session save path, defaults to '/tmp'.
 * @property int $timeout The number of seconds after which data will be seen as 'garbage' and cleaned up. The
 * default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
 * @property bool|null $useCookies The value indicating whether cookies should be used to store session IDs.
 * @property bool $useStrictMode Whether strict mode is enabled or not.
 * @property bool $useTransparentSessionID Whether transparent sid support is enabled or not, defaults to false.
 */
class Session extends Component implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var string|null Holds the session id in case useStrictMode is enabled and the session id needs to be
     * regenerated.
     */
    public string|null $_forceRegenerateId = null;
    /**
     * @var string|null Holds the original session module (before a custom handler is registered) so that it can be
     * restored when a Session component without custom handler is used after one that has.
     */
    protected string|null $_originalSessionModule = null;

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
    private bool|null $_hasSessionId = null;
    protected Flash $flash;
    public SessionHandlerInterface|null $_handler = null;

    /**
     * Initializes the application component.
     * This method is required by IApplicationComponent and is invoked by application.
     */
    public function init()
    {
        parent::init();

        $this->flash = new Flash($this);

        register_shutdown_function([$this, 'close']);

        if ($this->getIsActive()) {
            Yii::warning('Session is already started', __METHOD__);
            $this->flash->updateCounters();
        }
    }

    /**
     * Starts the session.
     */
    public function open(): void
    {
        if ($this->getIsActive()) {
            return;
        }

        $this->registerSessionHandler();
        $this->setCookieParamsInternal();

        YII_DEBUG ? session_start() : @session_start();

        if ($this->getUseStrictMode() && $this->isRegenerateId()) {
            $this->regenerateID();

            $this->_forceRegenerateId = null;

        }

        if ($this->getIsActive()) {
            Yii::info('Session started', __METHOD__);
            $this->flash->updateCounters();
        } else {
            $error = error_get_last();
            $message = isset($error['message']) ? $error['message'] : 'Failed to start session.';

            Yii::error($message, __METHOD__);
        }
    }

    /**
     * Ends the current session and store session data.
     */
    public function close(): void
    {
        if ($this->getIsActive()) {
            YII_DEBUG ? @session_write_close() : @session_write_close();
        }

        $this->_forceRegenerateId = null;
    }

    /**
     * Frees all session variables and destroys all data registered to a session.
     *
     * This method has no effect when session is not [[getIsActive()|active]].
     * Make sure to call [[open()]] before calling it.
     *
     * @see open()
     * @see isActive
     */
    public function destroy(string $id = null): bool
    {
        if ($this->getIsActive()) {
            $sessionId = session_id();

            $this->close();
            $this->setId($sessionId);
            $this->open();

            session_unset();
            session_destroy();

            $this->setId($sessionId);
        }

        return true;
    }

    /**
     * @return bool whether the session has started
     */
    public function getIsActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Returns a value indicating whether the current request has sent the session ID.
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
            // add @ to inhibit possible warning due to race condition https://github.com/yiisoft/yii2/pull/1812
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
     *
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
     * @param string $value the current session save path. This can be either a directory name or a [path alias](guide:concept-aliases).
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

        match ($value) {
            false => [
                ini_set('session.use_cookies', '0'),
                ini_set('session.use_only_cookies', '0'),
            ],
            true => [
                ini_set('session.use_cookies', '1'),
                ini_set('session.use_only_cookies', '1'),
            ],
            default => [
                ini_set('session.use_cookies', '1'),
                ini_set('session.use_only_cookies', '0'),
            ],
        };

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
     * @param float $value the probability (percentage) that the GC (garbage collection) process is started on every
     * session initialization.
     *
     * @throws InvalidArgumentException if the value is not between 0 and 100.
     */
    public function setGCProbability(float $value): void
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException('GCProbability must be a value between 0 and 100.');
        }

        $this->freeze();

        // percent * 21474837 / 2147483647 ≈ percent * 0.01
        ini_set('session.gc_probability', floor($value * 21474836.47));
        ini_set('session.gc_divisor', 2147483647);

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
     *
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
     * @param string $key the session variable name.
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
     * Returns a flash message.
     *
     * @param string $key the key identifying the flash message.
     * @param mixed $defaultValue value to be returned if the flash message does not exist.
     * @param bool $delete whether to delete this flash message right after this method is called.
     * If false, the flash message will be automatically deleted in the next request.
     *
     * @return mixed the flash message or an array of messages if addFlash was used
     *
     * @see setFlash()
     * @see addFlash()
     * @see hasFlash()
     * @see getAllFlashes()
     * @see removeFlash()
     */
    public function getFlash(string $key, mixed $defaultValue = null, bool $delete = false): mixed
    {
        return $this->flash->get($key, $defaultValue, $delete);
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
        return $this->flash->getAll($delete);
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
     * @param mixed $value flash message.
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if it is accessed.
     * If false, the flash message will be automatically removed after the next request, regardless if it is accessed
     * or not. If true (default value), the flash message will remain until after it is accessed.
     *
     * @see getFlash()
     * @see addFlash()
     * @see removeFlash()
     */
    public function setFlash(string $key, mixed $value = true, bool $removeAfterAccess = true): void
    {
        $this->flash->set($key, $value, $removeAfterAccess);
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
     * @see getFlash()
     * @see setFlash()
     * @see removeFlash()
     */
    public function addFlash(string $key, mixed $value = true, bool $removeAfterAccess = true): void
    {
        $this->flash->add($key, $value, $removeAfterAccess);
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
        return $this->flash->remove($key);
    }

    /**
     * Removes all flash messages.
     * Note that flash messages and normal session variables share the same name space.
     * If you have a normal session variable using the same name, it will be removed
     * by this method.
     *
     * @see getFlash()
     * @see setFlash()
     * @see addFlash()
     * @see removeFlash()
     */
    public function removeAllFlashes(): void
    {
        $this->flash->removeAll();
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
        return $this->flash->get($key) !== null;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param mixed $offset the offset to check on.
     *
     * @return bool whether or not the offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->open();

        return isset($_SESSION[$offset]);
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     *
     * @param mixed $offset the offset to retrieve element.
     *
     * @return mixed the element at the offset, null if no element is found at the offset
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
     * If session is started it's not possible to edit session ini settings. In PHP7.2+ it throws exception.
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
     * Set cache limiter
     *
     * @param string $cacheLimiter cache limiter.
     */
    public function setCacheLimiter(string $cacheLimiter)
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

    protected function isRegenerateId(): bool
    {
        if ($this->_handler?->isRegenerateId()) {
            return true;
        }

        return $this->_forceRegenerateId !== null;
    }

    /**
     * Register module name to session module name.
     */
    protected function registerSessionHandler(): void
    {
        $sessionModuleName = session_module_name();

        if ($this->_originalSessionModule === null) {
            $this->_originalSessionModule = $sessionModuleName;
        }

        if (
            $sessionModuleName !== $this->_originalSessionModule
            && $this->_originalSessionModule !== null
            && $this->_originalSessionModule !== 'user'
        ) {
            session_module_name($this->_originalSessionModule);
        }

        if ($this->_handler !== null) {
            session_set_save_handler($this->_handler, false);
        }
    }

    /**
     * Sets the session cookie parameters.
     * This method is called by [[open()]] when it is about to open the session.
     *
     * @throws InvalidArgumentException if the parameters are incomplete.

     * @see https://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    protected function setCookieParamsInternal(): void
    {
        $data = $this->getCookieParams();

        if (isset($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly'])) {
            session_set_cookie_params($data);
        } else {
            throw new InvalidArgumentException(
                'Please make sure cookieParams contains these elements: lifetime, path, domain, secure and httponly.'
            );
        }
    }
}
