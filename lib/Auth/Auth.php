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
     * Check if admin approval is required for new registrations
     *
     * @access public
     * @return bool
     */
    public function requiresApproval(): bool
    {
        return $this->isEnabled() && (bool) $this->_conf->getKey('require_approval', 'auth');
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

        if (!$user->isApproved()) {
            return array('success' => false, 'message' => 'Account is pending admin approval.');
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

        // check if password change is forced
        if ($user->isForcePasswordChange()) {
            return array('success' => true, 'force_password_change' => true);
        }

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
     * @param string $email
     * @return array result with 'success' bool and optional 'message'
     */
    public function register(string $username, string $password, string $email = ''): array
    {
        if (!$this->allowsRegistration()) {
            return array('success' => false, 'message' => 'Registration is not allowed.');
        }

        $needsApproval = $this->requiresApproval();

        return $this->createUser($username, $password, User::ROLE_USER, $email, !$needsApproval);
    }

    /**
     * Create a new user (admin operation)
     *
     * @access public
     * @param string $username
     * @param string $password
     * @param string $role
     * @param string $email
     * @param bool   $approved
     * @return array result with 'success' bool and optional 'message'
     */
    public function createUser(string $username, string $password, string $role = User::ROLE_USER, string $email = '', bool $approved = true): array
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
        $user->setEmail($email);
        $user->setApproved($approved);
        $this->_saveUser($user);

        // send notification emails
        if (!$approved) {
            $this->_notifyAdminNewRegistration($user);
            $this->_notifyUserPendingApproval($user);
        }

        return array('success' => true, 'pending_approval' => !$approved);
    }

    /**
     * Update the current user's email address
     *
     * @access public
     * @param string $username
     * @param string $email
     * @return array result with 'success' bool and optional 'message'
     */
    public function updateEmail(string $username, string $email): array
    {
        $user = $this->_loadUser($username);
        if ($user === null) {
            return array('success' => false, 'message' => 'User not found.');
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array('success' => false, 'message' => 'Invalid email address.');
        }

        $user->setEmail($email);
        $this->_saveUser($user);

        return array('success' => true);
    }

    /**
     * Admin: force a password reset for a user (sets a temporary password and forces change on next login)
     *
     * @access public
     * @param string $username
     * @param string $newPassword temporary password
     * @return array result with 'success' bool and optional 'message'
     */
    public function adminResetPassword(string $username, string $newPassword): array
    {
        if (!User::isValidPassword($newPassword)) {
            return array('success' => false, 'message' => 'Password must be at least 8 characters long.');
        }

        $user = $this->_loadUser($username);
        if ($user === null) {
            return array('success' => false, 'message' => 'User not found.');
        }

        $user->setPassword($newPassword);
        $user->setForcePasswordChange(true);
        $this->_saveUser($user);

        // notify user if they have an email
        $this->_notifyUserPasswordReset($user);

        return array('success' => true);
    }

    /**
     * Generate a password reset token and send it via email
     *
     * @access public
     * @param string $username
     * @return array result with 'success' bool and optional 'message'
     */
    public function generateResetToken(string $username): array
    {
        $user = $this->_loadUser($username);
        if ($user === null) {
            // don't reveal whether user exists
            return array('success' => true);
        }

        if (empty($user->getEmail())) {
            // can't send token without email — but don't reveal this
            return array('success' => true);
        }

        if (!$user->isActive() || !$user->isApproved()) {
            return array('success' => true);
        }

        // generate a secure random token
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        // token expires in 1 hour
        $user->setResetToken($tokenHash);
        $user->setResetTokenExpires(time() + 3600);
        $this->_saveUser($user);

        // send email with reset link
        $this->_sendPasswordResetEmail($user, $token);

        return array('success' => true);
    }

    /**
     * Reset password using a token (from forgot password email)
     *
     * @access public
     * @param string $username
     * @param string $token
     * @param string $newPassword
     * @return array result with 'success' bool and optional 'message'
     */
    public function resetPasswordWithToken(string $username, string $token, string $newPassword): array
    {
        if (!User::isValidPassword($newPassword)) {
            return array('success' => false, 'message' => 'Password must be at least 8 characters long.');
        }

        $user = $this->_loadUser($username);
        if ($user === null) {
            return array('success' => false, 'message' => 'Invalid or expired reset link.');
        }

        $tokenHash = hash('sha256', $token);
        if (empty($user->getResetToken()) || !hash_equals($user->getResetToken(), $tokenHash)) {
            return array('success' => false, 'message' => 'Invalid or expired reset link.');
        }

        if ($user->getResetTokenExpires() < time()) {
            return array('success' => false, 'message' => 'Reset link has expired. Please request a new one.');
        }

        $user->setPassword($newPassword);
        $user->setForcePasswordChange(false);
        $user->setResetToken('');
        $user->setResetTokenExpires(0);
        $this->_saveUser($user);

        return array('success' => true);
    }

    /**
     * Change password and clear force_password_change flag
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
        $user->setForcePasswordChange(false);
        $user->setResetToken('');
        $user->setResetTokenExpires(0);
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

        // use dedicated DB methods if available
        if (method_exists($this->_store, 'deleteUser')) {
            $this->_store->deleteUser($username);
        } else {
            $this->_store->setValue('', 'auth_users', $username);

            // remove from user list
            $users = $this->_getUserList();
            $users = array_filter($users, function ($u) use ($username) {
                return $u !== $username;
            });
            $this->_store->setValue(json_encode(array_values($users)), 'auth_user_list', '');
        }

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
        // use dedicated DB methods if available
        if (method_exists($this->_store, 'listUsers')) {
            $rows = $this->_store->listUsers();
            return array_map(function ($row) {
                return User::fromArray($row);
            }, $rows);
        }

        // fallback to key-value store
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
     * Approve a pending user
     *
     * @access public
     * @param string $username
     * @return array result with 'success' bool and optional 'message'
     */
    public function approveUser(string $username): array
    {
        $user = $this->_loadUser($username);
        if ($user === null) {
            return array('success' => false, 'message' => 'User not found.');
        }

        if ($user->isApproved()) {
            return array('success' => false, 'message' => 'User is already approved.');
        }

        $user->setApproved(true);
        $user->setActive(true);
        $this->_saveUser($user);

        $this->_notifyUserApproved($user);

        return array('success' => true);
    }

    /**
     * Reject a pending user (deletes the account)
     *
     * @access public
     * @param string $username
     * @return array result with 'success' bool and optional 'message'
     */
    public function rejectUser(string $username): array
    {
        $user = $this->_loadUser($username);
        if ($user === null) {
            return array('success' => false, 'message' => 'User not found.');
        }

        $this->_notifyUserRejected($user);

        return $this->deleteUser($username);
    }

    /**
     * Check if there are any users (for initial setup)
     *
     * @access public
     * @return bool
     */
    public function hasUsers(): bool
    {
        // use dedicated DB methods if available
        if (method_exists($this->_store, 'hasUsers')) {
            return $this->_store->hasUsers();
        }

        // fallback to key-value store
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
        // use dedicated DB methods if available
        if (method_exists($this->_store, 'readUser')) {
            $data = $this->_store->readUser($username);
            if ($data === null) {
                return null;
            }
            return User::fromArray($data);
        }

        // fallback to key-value store (Filesystem backend)
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
        $userData = $user->toArray();

        // use dedicated DB methods if available
        if (method_exists($this->_store, 'createUser')) {
            $existing = $this->_store->readUser($user->getUsername());
            if ($existing) {
                $this->_store->updateUser($user->getUsername(), $userData);
            } else {
                $this->_store->createUser($user->getUsername(), $userData);
            }
            return;
        }

        // fallback to key-value store (Filesystem backend)
        $this->_store->setValue(
            json_encode($userData),
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

    /**
     * Send an email notification
     *
     * @access private
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return bool
     */
    private function _sendEmail(string $to, string $subject, string $body): bool
    {
        if (empty($to)) {
            return false;
        }

        $fromEmail = $this->_conf->getKey('email_from', 'auth');
        if (empty($fromEmail)) {
            $fromEmail = 'noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
        }

        $siteName = $this->_conf->getKey('name', 'main') ?: 'PrivateBin';

        $headers  = 'From: ' . $siteName . ' <' . $fromEmail . '>' . "\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
        $headers .= 'X-Mailer: PrivateBin-Auth' . "\r\n";

        return @mail($to, $subject, $body, $headers);
    }

    /**
     * Notify admin about a new user registration pending approval
     *
     * @access private
     * @param User $user
     */
    private function _notifyAdminNewRegistration(User $user): void
    {
        $adminEmail = $this->_conf->getKey('admin_email', 'auth');
        if (empty($adminEmail)) {
            return;
        }

        $siteName = $this->_conf->getKey('name', 'main') ?: 'PrivateBin';
        $basepath = $this->_conf->getKey('basepath', 'main') ?: '';

        $subject = '[' . $siteName . '] New user registration pending approval';
        $body    = "A new user has registered and is waiting for your approval.\n\n";
        $body   .= 'Username: ' . $user->getUsername() . "\n";
        if ($user->getEmail()) {
            $body .= 'Email: ' . $user->getEmail() . "\n";
        }
        $body .= 'Registered: ' . date('Y-m-d H:i:s') . "\n\n";
        if ($basepath) {
            $body .= "Log in to the admin panel to approve or reject this user:\n";
            $body .= $basepath . "\n";
        }

        $this->_sendEmail($adminEmail, $subject, $body);
    }

    /**
     * Notify user that their registration is pending approval
     *
     * @access private
     * @param User $user
     */
    private function _notifyUserPendingApproval(User $user): void
    {
        $userEmail = $user->getEmail();
        if (empty($userEmail)) {
            return;
        }

        $siteName = $this->_conf->getKey('name', 'main') ?: 'PrivateBin';

        $subject = '[' . $siteName . '] Registration received - pending approval';
        $body    = "Hello " . $user->getUsername() . ",\n\n";
        $body   .= "Your registration on " . $siteName . " has been received.\n";
        $body   .= "An administrator will review your account shortly.\n";
        $body   .= "You will receive another email once your account has been approved.\n\n";
        $body   .= "Thank you for your patience.";

        $this->_sendEmail($userEmail, $subject, $body);
    }

    /**
     * Notify user that their account has been approved
     *
     * @access private
     * @param User $user
     */
    private function _notifyUserApproved(User $user): void
    {
        $userEmail = $user->getEmail();
        if (empty($userEmail)) {
            return;
        }

        $siteName = $this->_conf->getKey('name', 'main') ?: 'PrivateBin';
        $basepath = $this->_conf->getKey('basepath', 'main') ?: '';

        $subject = '[' . $siteName . '] Account approved';
        $body    = "Hello " . $user->getUsername() . ",\n\n";
        $body   .= "Your account on " . $siteName . " has been approved.\n";
        $body   .= "You can now log in and start using the service.\n";
        if ($basepath) {
            $body .= "\n" . $basepath . "\n";
        }

        $this->_sendEmail($userEmail, $subject, $body);
    }

    /**
     * Notify user that their account has been rejected
     *
     * @access private
     * @param User $user
     */
    private function _notifyUserRejected(User $user): void
    {
        $userEmail = $user->getEmail();
        if (empty($userEmail)) {
            return;
        }

        $siteName = $this->_conf->getKey('name', 'main') ?: 'PrivateBin';

        $subject = '[' . $siteName . '] Registration declined';
        $body    = "Hello " . $user->getUsername() . ",\n\n";
        $body   .= "Unfortunately, your registration on " . $siteName . " has been declined.\n";
        $body   .= "If you believe this is an error, please contact the administrator.";

        $this->_sendEmail($userEmail, $subject, $body);
    }

    /**
     * Notify user that their password has been reset by an admin
     *
     * @access private
     * @param User $user
     */
    private function _notifyUserPasswordReset(User $user): void
    {
        $userEmail = $user->getEmail();
        if (empty($userEmail)) {
            return;
        }

        $siteName = $this->_conf->getKey('name', 'main') ?: 'PrivateBin';
        $basepath = $this->_conf->getKey('basepath', 'main') ?: '';

        $subject = '[' . $siteName . '] Your password has been reset';
        $body    = "Hello " . $user->getUsername() . ",\n\n";
        $body   .= "An administrator has reset your password on " . $siteName . ".\n";
        $body   .= "You will be required to set a new password when you next log in.\n";
        if ($basepath) {
            $body .= "\nLogin at: " . $basepath . "\n";
        }

        $this->_sendEmail($userEmail, $subject, $body);
    }

    /**
     * Send password reset email with token link
     *
     * @access private
     * @param User $user
     * @param string $token plain-text token (not hashed)
     */
    private function _sendPasswordResetEmail(User $user, string $token): void
    {
        $userEmail = $user->getEmail();
        if (empty($userEmail)) {
            return;
        }

        $siteName = $this->_conf->getKey('name', 'main') ?: 'PrivateBin';
        $basepath = $this->_conf->getKey('basepath', 'main') ?: '';

        $resetLink = $basepath . '?reset_password&user=' . urlencode($user->getUsername()) . '&token=' . $token;

        $subject = '[' . $siteName . '] Password reset request';
        $body    = "Hello " . $user->getUsername() . ",\n\n";
        $body   .= "A password reset was requested for your account on " . $siteName . ".\n\n";
        $body   .= "Click the following link to reset your password:\n";
        $body   .= $resetLink . "\n\n";
        $body   .= "This link will expire in 1 hour.\n\n";
        $body   .= "If you did not request this, you can safely ignore this email.";

        $this->_sendEmail($userEmail, $subject, $body);
    }
}
