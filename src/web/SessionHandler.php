<?php

declare(strict_types=1);

namespace yii\web;

use SessionHandlerInterface;
use Yii;
use yii\base\InvalidArgumentException;

class SessionHandler implements SessionHandlerInterface
{
    public function __construct(private Session $session)
    {
    }

    public function open(string $path, string $name): bool
    {
        if ($this->session->getIsActive()) {
            return true;
        }

        $this->setCookieParamsInternal();

        YII_DEBUG ? session_start() : @session_start();

        if ($this->session->getUseStrictMode() && $this->session->_forceRegenerateId) {
            $this->session->regenerateID();
            $this->session->_forceRegenerateId = null;
        }

        if ($this->session->getIsActive()) {
            Yii::info('Session started', __METHOD__);
            $this->session->updateFlashCounters();
        } else {
            $error = error_get_last();
            $message = isset($error['message']) ? $error['message'] : 'Failed to start session.';
            Yii::error($message, __METHOD__);
        }

        return true;
    }

    public function close(): bool
    {
        if ($this->session->getIsActive()) {
            YII_DEBUG ? session_write_close() : @session_write_close();
        }

        $this->session->_forceRegenerateId = null;

        return true;
    }

    public function destroy(string $id = ''): bool
    {
        if ($this->session->getIsActive()) {
            $sessionId = session_id();

            $this->close();

            $this->session->setId($sessionId);

            $this->open('', '');

            session_unset();
            session_destroy();

            $this->session->setId($sessionId);
        }

        return true;
    }

    public function read(string $id): string
    {
        return '';
    }

    public function write(string $id, string $data): bool
    {
        return true;
    }

    /**
     * @throws InvalidArgumentException if the `$maxLifetime` is invalid.
     */
    public function gc(int $maxLifetime): false|int
    {
        $this->session->freeze();

        if ($maxLifetime >= 0 && $maxLifetime <= 100) {
            // percent * 21474837 / 2147483647 ≈ percent * 0.01
            ini_set('session.gc_probability', floor($maxLifetime * 21474836.47));
            ini_set('session.gc_divisor', 2147483647);
        } else {
            throw new InvalidArgumentException('GCProbability must be a value between 0 and 100.');
        }

        $this->session->unfreeze();

        return 0;
    }

    /**
     * Sets the session cookie parameters.
     * This method is called by [[open()]] when it is about to open the session.
     *
     * @throws InvalidArgumentException if the parameters are incomplete.
     *
     * @see https://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    private function setCookieParamsInternal(): void
    {
        $data = $this->session->getCookieParams();

        if (isset($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly'])) {
            if (!empty($data['samesite'])) {
                $data['path'] .= '; samesite=' . $data['samesite'];
            }
            session_set_cookie_params(
                $data['lifetime'],
                $data['path'],
                $data['domain'],
                $data['secure'],
                $data['httponly'],
            );
        } else {
            throw new InvalidArgumentException(
                'Please make sure cookieParams contains these elements: lifetime, path, domain, secure and httponly.'
            );
        }
    }
}
