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

/**
 * User
 *
 * Represents a user account in the authentication system.
 */
class User
{
    /**
     * Admin role constant
     *
     * @const string
     */
    const ROLE_ADMIN = 'admin';

    /**
     * Regular user role constant
     *
     * @const string
     */
    const ROLE_USER = 'user';

    /**
     * username
     *
     * @access private
     * @var string
     */
    private $_username;

    /**
     * bcrypt password hash
     *
     * @access private
     * @var string
     */
    private $_passwordHash;

    /**
     * user role (admin or user)
     *
     * @access private
     * @var string
     */
    private $_role;

    /**
     * whether the account is active
     *
     * @access private
     * @var bool
     */
    private $_isActive;

    /**
     * whether the account is approved by admin
     *
     * @access private
     * @var bool
     */
    private $_isApproved;

    /**
     * user email address
     *
     * @access private
     * @var string
     */
    private $_email;

    /**
     * creation timestamp
     *
     * @access private
     * @var int
     */
    private $_createdAt;

    /**
     * last login timestamp
     *
     * @access private
     * @var int
     */
    private $_lastLogin;

    /**
     * Constructor
     *
     * @access public
     * @param string $username
     * @param string $passwordHash
     * @param string $role
     * @param bool   $isActive
     * @param int    $createdAt
     * @param int    $lastLogin
     */
    public function __construct(
        string $username,
        string $passwordHash = '',
        string $role = self::ROLE_USER,
        bool $isActive = true,
        int $createdAt = 0,
        int $lastLogin = 0,
        bool $isApproved = true,
        string $email = ''
    ) {
        $this->_username     = $username;
        $this->_passwordHash = $passwordHash;
        $this->_role         = $role;
        $this->_isActive     = $isActive;
        $this->_createdAt    = $createdAt ?: time();
        $this->_lastLogin    = $lastLogin;
        $this->_isApproved   = $isApproved;
        $this->_email        = $email;
    }

    /**
     * Get username
     *
     * @access public
     * @return string
     */
    public function getUsername(): string
    {
        return $this->_username;
    }

    /**
     * Get password hash
     *
     * @access public
     * @return string
     */
    public function getPasswordHash(): string
    {
        return $this->_passwordHash;
    }

    /**
     * Set password (hashes it)
     *
     * @access public
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->_passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password against stored hash
     *
     * @access public
     * @param string $password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->_passwordHash);
    }

    /**
     * Get role
     *
     * @access public
     * @return string
     */
    public function getRole(): string
    {
        return $this->_role;
    }

    /**
     * Set role
     *
     * @access public
     * @param string $role
     */
    public function setRole(string $role): void
    {
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_USER], true)) {
            throw new \InvalidArgumentException('Invalid role: ' . $role);
        }
        $this->_role = $role;
    }

    /**
     * Check if user is admin
     *
     * @access public
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->_role === self::ROLE_ADMIN;
    }

    /**
     * Check if account is active
     *
     * @access public
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->_isActive;
    }

    /**
     * Set active status
     *
     * @access public
     * @param bool $isActive
     */
    public function setActive(bool $isActive): void
    {
        $this->_isActive = $isActive;
    }

    /**
     * Get creation timestamp
     *
     * @access public
     * @return int
     */
    public function getCreatedAt(): int
    {
        return $this->_createdAt;
    }

    /**
     * Get last login timestamp
     *
     * @access public
     * @return int
     */
    public function getLastLogin(): int
    {
        return $this->_lastLogin;
    }

    /**
     * Update last login to current time
     *
     * @access public
     */
    public function updateLastLogin(): void
    {
        $this->_lastLogin = time();
    }

    /**
     * Check if account is approved
     *
     * @access public
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->_isApproved;
    }

    /**
     * Set approved status
     *
     * @access public
     * @param bool $isApproved
     */
    public function setApproved(bool $isApproved): void
    {
        $this->_isApproved = $isApproved;
    }

    /**
     * Get email address
     *
     * @access public
     * @return string
     */
    public function getEmail(): string
    {
        return $this->_email;
    }

    /**
     * Set email address
     *
     * @access public
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->_email = $email;
    }

    /**
     * Serialize user to array for storage
     *
     * @access public
     * @return array
     */
    public function toArray(): array
    {
        return array(
            'username'      => $this->_username,
            'password_hash' => $this->_passwordHash,
            'role'          => $this->_role,
            'is_active'     => $this->_isActive,
            'created_at'    => $this->_createdAt,
            'last_login'    => $this->_lastLogin,
            'is_approved'   => $this->_isApproved,
            'email'         => $this->_email,
        );
    }

    /**
     * Create User from stored array data
     *
     * @access public
     * @static
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['username'],
            $data['password_hash'] ?? '',
            $data['role'] ?? self::ROLE_USER,
            $data['is_active'] ?? true,
            $data['created_at'] ?? 0,
            $data['last_login'] ?? 0,
            $data['is_approved'] ?? true,
            $data['email'] ?? ''
        );
    }

    /**
     * Validate username format
     *
     * @access public
     * @static
     * @param string $username
     * @return bool
     */
    public static function isValidUsername(string $username): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_\-\.]{3,64}$/', $username);
    }

    /**
     * Validate password strength
     *
     * @access public
     * @static
     * @param string $password
     * @return bool
     */
    public static function isValidPassword(string $password): bool
    {
        return strlen($password) >= 8;
    }
}
