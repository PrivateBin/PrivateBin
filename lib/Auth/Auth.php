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

use PrivateBin\Configuration;
use PrivateBin\Data\AbstractData;
use PrivateBin\Json;

/**
 * Auth
 *
 * Handles user authentication, registration, and user management.
 */
class Auth
{
    /**
     * configuration
     *
     * @access private
     * @var Configuration
     */
    private $_conf;

    /**
     * data storage backend
     *
     * @access private
     * @var AbstractData
     */
    private $_store;

    /**
     * session manager
     *
     * @access private
     * @var Session
     */
    private $_session;

    /**
     * current authenticated user
     *
     * @access private
     * @var User|null
     */
    private $_currentUser = null;

    /**
     * Constructor
     *
     * @access public
     * @param Configuration $conf
     * @param AbstractData $store
     */
    public function __construct(Configuration $conf, AbstractData $store)
    {
        $this->_conf    = $conf;
        $this->_store   = $store;
        $this->_session = new Session(
            $store,
            (int) $conf->getKey('session_timeout', 'auth')
        );
    }

    /**
     * Check if authentication is enabled
     *
     * @access public
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->_conf->getKey('enabled', 'auth');
    }

    /**
     * Check if login is required to create pastes
     *
     * @access public
     * @return bool
     */
    public function requiresLoginToCreate(): bool
    {
        return $this->isEnabled() && (bool) $this->_conf->getKey('require_login_to_create', 'auth');
    }

    /**
     * Check if login is required to read pastes
     *
     * @access public
     * @return bool
     */
    public function requiresLoginToRead(): bool
    {
        return $this->isEnabled() && (bool) $this->_conf->getKey('require_login_to_read', 'auth');
    }

    /**
     * Check if self-registration is allowed
     *
     * @access public
     * @return bool
     */
    public function allowsRegistration(): bool
    {
        return $this->isEnabled() && (bool) $this->_conf->getKey('allow_registration', 'auth');
    }

    /**
     * Resume the current session and load the authenticated user
     *
     * @access public
     * @return bool true if a user is authenticated
     */
    public function authenticate(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (!$this->_session->resume()) {
            return false;
        }

        $username = $this->_session->getUsername();
        if ($username === null) {
            return false;
        }

        $this->_currentUser = $this->_loadUser($username);
        if ($this->_currentUser === null || !$this->_currentUser->isActive()) {
            $this->_session->destroy();
            $this->_currentUser = null;
            return false;
        }

        return true;
    }

    /**
     * Attempt login with credentials
     *
     * @access public
     * @param string $username
     * @param string $password
     * @return array result with 'success' bool and optional 'message'
     */
    public function login(string $username, string $password): array
    {
        if (!$this->isEnabled()) {
            return array('success' => false, 'message' => 'Authentication is not enabled.');
        }

        $username = trim($username);
        if (empty($username) || empty($password)) {
            return array('success' => false, 'message' => 'Username and password are required.');
        }

        $user = $this->_loadUser($username);
        if ($user === null) {
            // constant-time comparison to prevent timing attacks
            password_verify($password, '$2y$12$invalidsaltinvalidsaltinvalidsaltinvalidsaltinvalidsa');
            return array('success' => false, 'message' => 'Invalid username or password.');
        }

        if (!$user->isActive()) {
            return array('success' => false, 'message' => 'Account is disabled.');
        }

        if (!$user->verifyPassword($password)) {
            return array('success' => false, 'message' => 'Invalid username or password.');
        }

        // update last login
        $user->updateLastLogin();
        $this->_saveUser($user);

        // create session
        $this->_session->create($username);
        $this->_currentUser = $user;

        return array('success' => true);
    }

    /**
     * Logout the current user
     *
     * @access public
     */
    public function logout(): void
    {
        $this->_session->destroy();
        $this->_currentUser = null;
    }

    /**
     * Register a new user
     *
     * @access public
     * @param string $username
     * @param string $password
     * @return array result with 'success' bool and optional 'message'
     */
    public function register(string $username, string $password): array
    {
        if (!$this->allowsRegistration()) {
            return array('success' => false, 'message' => 'Registration is not allowed.');
        }

        return $this->createUser($username, $password, User::ROLE_USER);
    }

    /**
     * Create a new user (admin operation)
     *
     * @access public
     * @param string $username
     * @param string $password
     * @param string $role
     * @return array result with 'success' bool and optional 'message'
     */
    public function createUser(string $username, string $password, string $role = User::ROLE_USER): array
    {
        $username = trim($username);

        if (!User::isValidUsername($username)) {
            return array('success' => false, 'message' => 'Invalid username. Use 3-64 characters: letters, numbers, underscore, dash, dot.');
        }

        if (!User::isValidPassword($password)) {
            return array('success' => false, 'message' => 'Password must be at least 8 characters long.');
        }

        // check if user already exists
        $existing = $this->_loadUser($username);
        if ($existing !== null) {
            return array('success' => false, 'message' => 'Username already exists.');
        }

        $user = new User($username);
        $user->setPassword($password);
        $user->setRole($role);
        $this->_saveUser($user);

        return array('success' => true);
    }

    /**
     * Update a user's password
     *
     * @access public
     * @param string $username
     * @param string $newPassword
     * @return array result with 'success' bool and optional 'message'
     */
    public function changePassword(string $username, string $newPassword): array
    {
        if (!User::isValidPassword($newPassword)) {
            return array('success' => false, 'message' => 'Password must be at least 8 characters long.');
        }

        $user = $this->_loadUser($username);
        if ($user === null) {
            return array('success' => false, 'message' => 'User not found.');
        }

        $user->setPassword($newPassword);
        $this->_saveUser($user);

        return array('success' => true);
    }

    /**
     * Update a user's role
     *
     * @access public
     * @param string $username
     * @param string $role
     * @return array result with 'success' bool and optional 'message'
     */
    public function changeRole(string $username, string $role): array
    {
        $user = $this->_loadUser($username);
        if ($user === null) {
            return array('success' => false, 'message' => 'User not found.');
        }

        try {
            $user->setRole($role);
        } catch (\InvalidArgumentException $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }

        $this->_saveUser($user);

        return array('success' => true);
    }

    /**
     * Toggle user active status
     *
     * @access public
     * @param string $username
     * @param bool $active
     * @return array result with 'success' bool and optional 'message'
     */
    public function setUserActive(string $username, bool $active): array
    {
        $user = $this->_loadUser($username);
        if ($user === null) {
            return array('success' => false, 'message' => 'User not found.');
        }

        $user->setActive($active);
        $this->_saveUser($user);

        return array('success' => true);
    }

    /**
     * Delete a user
     *
     * @access public
     * @param string $username
     * @return array result with 'success' bool and optional 'message'
     */
    public function deleteUser(string $username): array
    {
        $user = $this->_loadUser($username);
        if ($user === null) {
            return array('success' => false, 'message' => 'User not found.');
        }

        $this->_store->setValue('', 'auth_users', $username);

        // remove from user list
        $users = $this->_getUserList();
        $users = array_filter($users, function ($u) use ($username) {
            return $u !== $username;
        });
        $this->_store->setValue(json_encode(array_values($users)), 'auth_user_list', '');

        return array('success' => true);
    }

    /**
     * Get all users
     *
     * @access public
     * @return array of User objects
     */
    public function listUsers(): array
    {
        $usernames = $this->_getUserList();
        $users     = array();
        foreach ($usernames as $username) {
            $user = $this->_loadUser($username);
            if ($user !== null) {
                $users[] = $user;
            }
        }
        return $users;
    }

    /**
     * Get the currently authenticated user
     *
     * @access public
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        return $this->_currentUser;
    }

    /**
     * Get the session manager
     *
     * @access public
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->_session;
    }

    /**
     * Check if there are any users (for initial setup)
     *
     * @access public
     * @return bool
     */
    public function hasUsers(): bool
    {
        $users = $this->_getUserList();
        return !empty($users);
    }

    /**
     * Load a user from storage
     *
     * @access private
     * @param string $username
     * @return User|null
     */
    private function _loadUser(string $username): ?User
    {
        $data = $this->_store->getValue('auth_users', $username);
        if (empty($data)) {
            return null;
        }

        $decoded = json_decode($data, true);
        if (!$decoded || !is_array($decoded)) {
            return null;
        }

        return User::fromArray($decoded);
    }

    /**
     * Save a user to storage
     *
     * @access private
     * @param User $user
     */
    private function _saveUser(User $user): void
    {
        $this->_store->setValue(
            json_encode($user->toArray()),
            'auth_users',
            $user->getUsername()
        );

        // update user list
        $users = $this->_getUserList();
        if (!in_array($user->getUsername(), $users, true)) {
            $users[] = $user->getUsername();
            $this->_store->setValue(json_encode($users), 'auth_user_list', '');
        }
    }

    /**
     * Get list of all usernames
     *
     * @access private
     * @return array
     */
    private function _getUserList(): array
    {
        $data = $this->_store->getValue('auth_user_list', '');
        if (empty($data)) {
            return array();
        }

        $decoded = json_decode($data, true);
        if (!$decoded || !is_array($decoded)) {
            return array();
        }

        return $decoded;
    }
}
