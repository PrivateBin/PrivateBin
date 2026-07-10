<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

namespace PrivateBin\Auth;

use PrivateBin\Data\AbstractData;
use PrivateBin\Data\Database;

/**
 * Session
 *
 * Manages authenticated user sessions using the existing storage backend.
 */
class Session
{
    /**
     * session cookie name
     *
     * @const string
     */
    const COOKIE_NAME = 'privatebin_session';

    /**
     * CSRF token cookie name
     *
     * @const string
     */
    const CSRF_COOKIE_NAME = 'privatebin_csrf';

    /**
     * storage backend
     *
     * @access private
     * @var AbstractData
     */
    private $_store;

    /**
     * session timeout in seconds
     *
     * @access private
     * @var int
     */
    private $_timeout;

    /**
     * current session ID
     *
     * @access private
     * @var string|null
     */
    private $_sessionId = null;

    /**
     * current session data
     *
     * @access private
     * @var array|null
     */
    private $_sessionData = null;

    /**
     * Constructor
     *
     * @access public
     * @param AbstractData $store
     * @param int $timeout session timeout in seconds (default: 3600)
     */
    public function __construct(AbstractData $store, int $timeout = 3600)
    {
        $this->_store   = $store;
        $this->_timeout = $timeout;
    }

    /**
     * Start or resume a session from the cookie
     *
     * @access public
     * @return bool true if a valid session exists
     */
    public function resume(): bool
    {
        $sessionId = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (empty($sessionId) || !$this->_isValidSessionId($sessionId)) {
            return false;
        }

        // use dedicated DB methods if available
        if ($this->_store instanceof \PrivateBin\Data\Database) {
            $session = $this->_store->readSession($sessionId);
            if (!$session) {
                return false;
            }
            // check expiration
            if ($session['expires_at'] < time()) {
                $this->_store->deleteSession($sessionId);
                return false;
            }
            $this->_sessionId   = $sessionId;
            $this->_sessionData = $session;
            return true;
        }

        // fallback to key-value store
        $data = $this->_store->getValue('auth_sessions', $sessionId);
        if (empty($data)) {
            return false;
        }

        $session = json_decode($data, true);
        if (!$session || !is_array($session)) {
            return false;
        }

        // check expiration
        if ($session['expires_at'] < time()) {
            $this->_store->setValue('', 'auth_sessions', $sessionId);
            return false;
        }

        $this->_sessionId   = $sessionId;
        $this->_sessionData = $session;
        return true;
    }

    /**
     * Create a new session for the given username
     *
     * @access public
     * @param string $username
     * @return string the session ID
     */
    public function create(string $username): string
    {
        $sessionId   = bin2hex(random_bytes(32));
        $sessionData = array(
            'username'   => $username,
            'created_at' => time(),
            'expires_at' => time() + $this->_timeout,
            'ip_hash'    => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
        );

        // use dedicated DB methods if available
        if ($this->_store instanceof \PrivateBin\Data\Database) {
            $this->_store->createSession($sessionId, $sessionData);
            // also purge expired sessions periodically
            $this->_store->purgeExpiredSessions();
        } else {
            $this->_store->setValue(
                json_encode($sessionData),
                'auth_sessions',
                $sessionId
            );
        }

        $this->_sessionId   = $sessionId;
        $this->_sessionData = $sessionData;

        $this->_setSessionCookie($sessionId);
        $this->_setCsrfCookie();

        return $sessionId;
    }

    /**
     * Destroy the current session
     *
     * @access public
     */
    public function destroy(): void
    {
        if ($this->_sessionId !== null) {
            // use dedicated DB methods if available
            if ($this->_store instanceof \PrivateBin\Data\Database) {
                $this->_store->deleteSession($this->_sessionId);
            } else {
                $this->_store->setValue('', 'auth_sessions', $this->_sessionId);
            }
        }

        $this->_sessionId   = null;
        $this->_sessionData = null;

        // expire cookies
        $expiredTime = time() - 86400;
        setcookie(self::COOKIE_NAME, '', array(
            'expires'   => $expiredTime,
            'path'      => '/',
            'secure'    => true,
            'httponly'  => true,
            'samesite'  => 'Lax',
        ));
        setcookie(self::CSRF_COOKIE_NAME, '', array(
            'expires'   => $expiredTime,
            'path'      => '/',
            'secure'    => true,
            'httponly'  => false,
            'samesite'  => 'Lax',
        ));
    }

    /**
     * Get the username of the currently authenticated user
     *
     * @access public
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->_sessionData['username'] ?? null;
    }

    /**
     * Check if a session is active
     *
     * @access public
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->_sessionId !== null && $this->_sessionData !== null;
    }

    /**
     * Generate a CSRF token for the current session
     *
     * @access public
     * @return string
     */
    public function getCsrfToken(): string
    {
        if ($this->_sessionId === null) {
            return '';
        }
        return hash_hmac('sha256', $this->_sessionId, $this->_getServerSecret());
    }

    /**
     * Validate a CSRF token
     *
     * @access public
     * @param string $token
     * @return bool
     */
    public function validateCsrfToken(string $token): bool
    {
        if ($this->_sessionId === null || empty($token)) {
            return false;
        }
        return hash_equals($this->getCsrfToken(), $token);
    }

    /**
     * Set session cookie
     *
     * @access private
     * @param string $sessionId
     */
    private function _setSessionCookie(string $sessionId): void
    {
        setcookie(self::COOKIE_NAME, $sessionId, array(
            'expires'   => time() + $this->_timeout,
            'path'      => '/',
            'secure'    => true,
            'httponly'  => true,
            'samesite'  => 'Lax',
        ));
    }

    /**
     * Set CSRF cookie (readable by JavaScript)
     *
     * @access private
     */
    private function _setCsrfCookie(): void
    {
        setcookie(self::CSRF_COOKIE_NAME, $this->getCsrfToken(), array(
            'expires'   => time() + $this->_timeout,
            'path'      => '/',
            'secure'    => true,
            'httponly'  => false,
            'samesite'  => 'Lax',
        ));
    }

    /**
     * Validate session ID format
     *
     * @access private
     * @param string $sessionId
     * @return bool
     */
    private function _isValidSessionId(string $sessionId): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $sessionId);
    }

    /**
     * Get or generate a server-side secret for CSRF token generation
     *
     * @access private
     * @return string
     */
    private function _getServerSecret(): string
    {
        $secret = $this->_store->getValue('auth_secret', '');
        if (empty($secret)) {
            $secret = bin2hex(random_bytes(32));
            $this->_store->setValue($secret, 'auth_secret', '');
        }
        return $secret;
    }
}
