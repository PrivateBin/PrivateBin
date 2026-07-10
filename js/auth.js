/**
 * PrivateBin Authentication Module
 *
 * Handles user login, logout, registration, and admin user management.
 *
 * @see       {@link https://github.com/PrivateBin/PrivateBin}
 * @copyright 2012 Sébastien SAUVAGE ({@link http://sebsauvage.net})
 * @license   {@link https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License}
 */

jQuery.PrivateBin = jQuery.PrivateBin || {};

jQuery.PrivateBin.Auth = (function($) {
    'use strict';

    var me = {},
        csrfToken = '',
        currentUser = null,
        authEnabled = false,
        allowRegistration = false;

    /**
     * Initialize auth module from page data attributes
     *
     * @name Auth.init
     * @function
     * @param {object} config - auth configuration from page
     */
    me.init = function(config) {
        authEnabled = config.enabled || false;
        allowRegistration = config.allowRegistration || false;
        csrfToken = config.csrf || '';

        if (config.username) {
            currentUser = {
                username: config.username,
                role: config.role
            };
        }

        if (!authEnabled) {
            return;
        }

        me.renderAuthUI();

        // show setup dialog if no users exist yet
        if (config.needsSetup) {
            me.showSetupDialog();
            return;
        }

        // bind inline login form if present on page
        me.bindInlineLoginForm();

        // show login dialog if login is required and user is not authenticated
        // (only if there's no inline form already)
        if (!currentUser && (config.requireLoginCreate || config.requireLoginRead) && $('#auth-login-page-form').length === 0) {
            me.showLoginDialog();
        }
    };

    /**
     * Render authentication UI elements
     *
     * @name Auth.renderAuthUI
     * @function
     */
    me.renderAuthUI = function() {
        var $navbar = $('#navbar');
        if ($navbar.length === 0) {
            return;
        }

        var $authContainer = $('<ul class="navbar-nav ms-auto" id="auth-nav"></ul>');

        if (currentUser) {
            var adminLink = '';
            if (currentUser.role === 'admin') {
                adminLink = '<li class="nav-item"><a class="nav-link" href="#" id="auth-admin-btn">' +
                    '<span class="icon"><svg width="16" height="16" viewBox="0 0 16 16"><path d="M8 0a4 4 0 0 0-4 4c0 2.21 1.79 4 4 4s4-1.79 4-4-1.79-4-4-4zm0 10c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/></svg></span> ' +
                    'Admin</a></li>';
            }
            $authContainer.html(
                adminLink +
                '<li class="nav-item"><span class="nav-link text-muted" id="auth-username">' +
                me.escapeHtml(currentUser.username) + '</span></li>' +
                '<li class="nav-item"><a class="nav-link" href="#" id="auth-logout-btn">Logout</a></li>'
            );
            $authContainer.find('#auth-logout-btn').on('click', function(e) {
                e.preventDefault();
                me.logout();
            });
            $authContainer.find('#auth-admin-btn').on('click', function(e) {
                e.preventDefault();
                me.showAdminPanel();
            });
        } else {
            $authContainer.html(
                '<li class="nav-item"><a class="nav-link" href="#" id="auth-login-btn">Login</a></li>'
            );
            $authContainer.find('#auth-login-btn').on('click', function(e) {
                e.preventDefault();
                me.showLoginDialog();
            });
        }

        // remove old auth nav if present
        $('#auth-nav').remove();
        $navbar.append($authContainer);
    };

    /**
     * Bind inline login/register forms rendered server-side in the template
     *
     * @name Auth.bindInlineLoginForm
     * @function
     */
    me.bindInlineLoginForm = function() {
        // inline login form
        $('#auth-login-page-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true).text('Logging in...');
            me.apiCall({
                auth_action: 'login',
                username: $('#auth-page-username').val(),
                password: $('#auth-page-password').val()
            }, function(data) {
                window.location.reload();
            }, function(message) {
                $btn.prop('disabled', false).text('Login');
                $('#auth-login-error').text(message).removeClass('d-none');
            });
        });

        // toggle to register form
        $('#auth-page-show-register').on('click', function(e) {
            e.preventDefault();
            $('#auth-login-page').addClass('d-none');
            $('#auth-register-page').removeClass('d-none');
        });

        // toggle back to login form
        $('#auth-page-show-login').on('click', function(e) {
            e.preventDefault();
            $('#auth-register-page').addClass('d-none');
            $('#auth-login-page').removeClass('d-none');
        });

        // inline register form
        $('#auth-register-page-form').on('submit', function(e) {
            e.preventDefault();
            var pw1 = $('#auth-page-reg-password').val();
            var pw2 = $('#auth-page-reg-password2').val();
            if (pw1 !== pw2) {
                $('#auth-register-error').text('Passwords do not match.').removeClass('d-none');
                return;
            }
            var $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true).text('Registering...');
            me.apiCall({
                auth_action: 'register',
                username: $('#auth-page-reg-username').val(),
                password: pw1
            }, function(data) {
                window.location.reload();
            }, function(message) {
                $btn.prop('disabled', false).text('Register');
                $('#auth-register-error').text(message).removeClass('d-none');
            });
        });
    };

    /**
     * Show login dialog
     *
     * @name Auth.showLoginDialog
     * @function
     */
    me.showLoginDialog = function() {
        var registrationHtml = '';
        if (allowRegistration) {
            registrationHtml = '<p class="mt-3 text-center"><a href="#" id="auth-show-register">Create an account</a></p>';
        }

        var html = '<div class="modal fade" id="auth-modal" tabindex="-1">' +
            '<div class="modal-dialog modal-sm">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title">Login</h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
            '<div class="modal-body">' +
            '<div class="alert alert-danger d-none" id="auth-error"></div>' +
            '<form id="auth-login-form">' +
            '<div class="mb-3"><label for="auth-username-input" class="form-label">Username</label>' +
            '<input type="text" class="form-control" id="auth-username-input" required autocomplete="username"></div>' +
            '<div class="mb-3"><label for="auth-password-input" class="form-label">Password</label>' +
            '<input type="password" class="form-control" id="auth-password-input" required autocomplete="current-password"></div>' +
            '<button type="submit" class="btn btn-primary w-100">Login</button>' +
            '</form>' +
            registrationHtml +
            '</div></div></div></div>';

        me.removeModal();
        $('body').append(html);

        var $modal = $('#auth-modal');
        var modal = new bootstrap.Modal($modal[0]);
        modal.show();

        $('#auth-login-form').on('submit', function(e) {
            e.preventDefault();
            me.login(
                $('#auth-username-input').val(),
                $('#auth-password-input').val()
            );
        });

        $('#auth-show-register').on('click', function(e) {
            e.preventDefault();
            modal.hide();
            me.showRegisterDialog();
        });
    };

    /**
     * Show registration dialog
     *
     * @name Auth.showRegisterDialog
     * @function
     */
    me.showRegisterDialog = function() {
        var html = '<div class="modal fade" id="auth-modal" tabindex="-1">' +
            '<div class="modal-dialog modal-sm">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title">Register</h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
            '<div class="modal-body">' +
            '<div class="alert alert-danger d-none" id="auth-error"></div>' +
            '<form id="auth-register-form">' +
            '<div class="mb-3"><label for="auth-reg-username" class="form-label">Username</label>' +
            '<input type="text" class="form-control" id="auth-reg-username" required autocomplete="username">' +
            '<div class="form-text">3-64 characters: letters, numbers, _, -, .</div></div>' +
            '<div class="mb-3"><label for="auth-reg-password" class="form-label">Password</label>' +
            '<input type="password" class="form-control" id="auth-reg-password" required autocomplete="new-password">' +
            '<div class="form-text">Minimum 8 characters</div></div>' +
            '<div class="mb-3"><label for="auth-reg-password2" class="form-label">Confirm Password</label>' +
            '<input type="password" class="form-control" id="auth-reg-password2" required autocomplete="new-password"></div>' +
            '<button type="submit" class="btn btn-primary w-100">Register</button>' +
            '</form>' +
            '<p class="mt-3 text-center"><a href="#" id="auth-show-login">Back to login</a></p>' +
            '</div></div></div></div>';

        me.removeModal();
        $('body').append(html);

        var $modal = $('#auth-modal');
        var modal = new bootstrap.Modal($modal[0]);
        modal.show();

        $('#auth-register-form').on('submit', function(e) {
            e.preventDefault();
            var pw1 = $('#auth-reg-password').val();
            var pw2 = $('#auth-reg-password2').val();
            if (pw1 !== pw2) {
                me.showError('Passwords do not match.');
                return;
            }
            me.register($('#auth-reg-username').val(), pw1);
        });

        $('#auth-show-login').on('click', function(e) {
            e.preventDefault();
            modal.hide();
            me.showLoginDialog();
        });
    };

    /**
     * Show initial setup dialog (first admin creation)
     *
     * @name Auth.showSetupDialog
     * @function
     */
    me.showSetupDialog = function() {
        var html = '<div class="modal fade" id="auth-modal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">' +
            '<div class="modal-dialog modal-sm">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title">Initial Setup</h5></div>' +
            '<div class="modal-body">' +
            '<p class="text-muted">Authentication is enabled. Create the first administrator account to get started.</p>' +
            '<div class="alert alert-danger d-none" id="auth-error"></div>' +
            '<form id="auth-setup-form">' +
            '<div class="mb-3"><label for="auth-setup-username" class="form-label">Admin Username</label>' +
            '<input type="text" class="form-control" id="auth-setup-username" required autocomplete="username"></div>' +
            '<div class="mb-3"><label for="auth-setup-password" class="form-label">Password</label>' +
            '<input type="password" class="form-control" id="auth-setup-password" required autocomplete="new-password">' +
            '<div class="form-text">Minimum 8 characters</div></div>' +
            '<div class="mb-3"><label for="auth-setup-password2" class="form-label">Confirm Password</label>' +
            '<input type="password" class="form-control" id="auth-setup-password2" required autocomplete="new-password"></div>' +
            '<button type="submit" class="btn btn-primary w-100">Create Admin Account</button>' +
            '</form>' +
            '</div></div></div></div>';

        me.removeModal();
        $('body').append(html);

        var $modal = $('#auth-modal');
        var modal = new bootstrap.Modal($modal[0]);
        modal.show();

        $('#auth-setup-form').on('submit', function(e) {
            e.preventDefault();
            var pw1 = $('#auth-setup-password').val();
            var pw2 = $('#auth-setup-password2').val();
            if (pw1 !== pw2) {
                me.showError('Passwords do not match.');
                return;
            }
            me.apiCall({
                auth_action: 'setup',
                username: $('#auth-setup-username').val(),
                password: pw1
            }, function(data) {
                csrfToken = data.csrf || '';
                currentUser = { username: data.username, role: data.role };
                me.removeModal();
                me.renderAuthUI();
                window.location.reload();
            });
        });
    };

    /**
     * Show admin panel
     *
     * @name Auth.showAdminPanel
     * @function
     */
    me.showAdminPanel = function() {
        var html = '<div class="modal fade" id="auth-modal" tabindex="-1">' +
            '<div class="modal-dialog modal-lg">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title">Administration</h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
            '<div class="modal-body">' +
            '<ul class="nav nav-tabs mb-3" role="tablist">' +
            '<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#admin-tab-users">Users</a></li>' +
            '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#admin-tab-settings">Settings</a></li>' +
            '</ul>' +
            '<div class="tab-content">' +
            '<div class="tab-pane fade show active" id="admin-tab-users">' +
            '<div class="alert alert-danger d-none" id="auth-error"></div>' +
            '<div class="alert alert-success d-none" id="auth-success"></div>' +
            '<h6>Add User</h6>' +
            '<form id="auth-add-user-form" class="row g-2 mb-4">' +
            '<div class="col-md-3"><input type="text" class="form-control form-control-sm" placeholder="Username" id="admin-new-username" required></div>' +
            '<div class="col-md-3"><input type="password" class="form-control form-control-sm" placeholder="Password" id="admin-new-password" required></div>' +
            '<div class="col-md-3"><select class="form-select form-select-sm" id="admin-new-role">' +
            '<option value="user">User</option><option value="admin">Admin</option></select></div>' +
            '<div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary">Add User</button></div>' +
            '</form>' +
            '<h6>Users</h6>' +
            '<div id="admin-users-list"><p class="text-muted">Loading...</p></div>' +
            '</div>' +
            '<div class="tab-pane fade" id="admin-tab-settings">' +
            '<div class="alert alert-danger d-none" id="settings-error"></div>' +
            '<div class="alert alert-success d-none" id="settings-success"></div>' +
            '<div id="admin-settings-content"><p class="text-muted">Loading settings...</p></div>' +
            '</div>' +
            '</div>' +
            '</div></div></div></div>';

        me.removeModal();
        $('body').append(html);

        var $modal = $('#auth-modal');
        var modal = new bootstrap.Modal($modal[0]);
        modal.show();

        me.loadUsers();

        // load settings when tab is shown
        $('a[href="#admin-tab-settings"]').on('shown.bs.tab', function() {
            me.loadSettings();
        });

        $('#auth-add-user-form').on('submit', function(e) {
            e.preventDefault();
            me.adminCreateUser(
                $('#admin-new-username').val(),
                $('#admin-new-password').val(),
                $('#admin-new-role').val()
            );
        });
    };

    /**
     * Load and render settings form
     *
     * @name Auth.loadSettings
     * @function
     */
    me.loadSettings = function() {
        me.apiCall({
            auth_action: 'get_settings',
            csrf_token: csrfToken
        }, function(data) {
            var s = data.settings || {};
            var html = '<form id="admin-settings-form">';

            // Main section
            html += '<h6 class="border-bottom pb-2 mb-3">General</h6>';
            html += '<div class="row g-3 mb-4">';
            html += me.settingsInput('main', 'name', 'Site Name', s, 'text');
            html += me.settingsInput('main', 'basepath', 'Base URL', s, 'text', 'https://example.com/');
            html += me.settingsCheckbox('main', 'discussion', 'Enable discussions', s);
            html += me.settingsCheckbox('main', 'opendiscussion', 'Pre-select discussions', s);
            html += me.settingsCheckbox('main', 'password', 'Enable paste passwords', s);
            html += me.settingsCheckbox('main', 'fileupload', 'Enable file upload', s);
            html += me.settingsCheckbox('main', 'burnafterreadingselected', 'Pre-select burn after reading', s);
            html += me.settingsCheckbox('main', 'qrcode', 'Enable QR code sharing', s);
            html += me.settingsCheckbox('main', 'email', 'Enable email sharing', s);
            html += me.settingsSelect('main', 'defaultformatter', 'Default format', s,
                {plaintext: 'Plain Text', syntaxhighlighting: 'Source Code', markdown: 'Markdown'});
            html += me.settingsSelect('main', 'compression', 'Compression', s,
                {zlib: 'zlib', none: 'None'});
            html += me.settingsInput('main', 'sizelimit', 'Size limit (bytes)', s, 'number');
            html += me.settingsSelect('main', 'template', 'Default template', s,
                {'bootstrap5': 'Bootstrap 5', 'bootstrap': 'Bootstrap 3', 'bootstrap-dark': 'Bootstrap Dark',
                 'bootstrap-compact': 'Bootstrap Compact', 'bootstrap-page': 'Bootstrap Page',
                 'bootstrap-dark-page': 'Dark Page', 'bootstrap-compact-page': 'Compact Page'});
            html += me.settingsCheckbox('main', 'languageselection', 'Enable language selection', s);
            html += me.settingsInput('main', 'notice', 'Notice text', s, 'text', 'Optional notice shown to users');
            html += me.settingsCheckbox('main', 'httpwarning', 'HTTP warning', s);
            html += '</div>';

            // Auth section
            html += '<h6 class="border-bottom pb-2 mb-3">Authentication</h6>';
            html += '<div class="row g-3 mb-4">';
            html += me.settingsCheckbox('auth', 'enabled', 'Enable authentication', s);
            html += me.settingsCheckbox('auth', 'require_login_to_create', 'Require login to create', s);
            html += me.settingsCheckbox('auth', 'require_login_to_read', 'Require login to read', s);
            html += me.settingsCheckbox('auth', 'allow_registration', 'Allow self-registration', s);
            html += me.settingsInput('auth', 'session_timeout', 'Session timeout (seconds)', s, 'number');
            html += '</div>';

            // Traffic section
            html += '<h6 class="border-bottom pb-2 mb-3">Rate Limiting</h6>';
            html += '<div class="row g-3 mb-4">';
            html += me.settingsInput('traffic', 'limit', 'Rate limit (seconds)', s, 'number');
            html += me.settingsInput('traffic', 'header', 'IP header (proxy)', s, 'text', 'e.g. X_FORWARDED_FOR');
            html += me.settingsInput('traffic', 'exempted', 'Exempted IPs', s, 'text', 'Comma-separated');
            html += me.settingsInput('traffic', 'creators', 'Creator IPs', s, 'text', 'Comma-separated');
            html += '</div>';

            // Purge section
            html += '<h6 class="border-bottom pb-2 mb-3">Purge</h6>';
            html += '<div class="row g-3 mb-4">';
            html += me.settingsInput('purge', 'limit', 'Purge interval (seconds)', s, 'number');
            html += me.settingsInput('purge', 'batchsize', 'Purge batch size', s, 'number');
            html += '</div>';

            // Expire section
            html += '<h6 class="border-bottom pb-2 mb-3">Expiration</h6>';
            html += '<div class="row g-3 mb-4">';
            var expireOpts = s.expire_options || {};
            var expireKeys = {};
            for (var ek in expireOpts) { expireKeys[ek] = ek; }
            html += me.settingsSelect('expire', 'default', 'Default expiration', s, expireKeys);
            html += '</div>';

            html += '<button type="submit" class="btn btn-primary">Save Settings</button>';
            html += '</form>';

            $('#admin-settings-content').html(html);

            $('#admin-settings-form').on('submit', function(e) {
                e.preventDefault();
                me.saveSettings();
            });
        });
    };

    /**
     * Generate a text/number input for settings
     */
    me.settingsInput = function(section, key, label, settings, type, placeholder) {
        var value = (settings[section] && settings[section][key] !== undefined) ? settings[section][key] : '';
        return '<div class="col-md-6"><label class="form-label small">' + label + '</label>' +
            '<input type="' + (type || 'text') + '" class="form-control form-control-sm" ' +
            'data-section="' + section + '" data-key="' + key + '" ' +
            'value="' + me.escapeHtml(String(value)) + '"' +
            (placeholder ? ' placeholder="' + me.escapeHtml(placeholder) + '"' : '') +
            '></div>';
    };

    /**
     * Generate a checkbox for settings
     */
    me.settingsCheckbox = function(section, key, label, settings) {
        var checked = (settings[section] && settings[section][key]) ? ' checked' : '';
        return '<div class="col-md-6"><div class="form-check">' +
            '<input type="checkbox" class="form-check-input" ' +
            'data-section="' + section + '" data-key="' + key + '"' + checked + '>' +
            '<label class="form-check-label small">' + label + '</label></div></div>';
    };

    /**
     * Generate a select for settings
     */
    me.settingsSelect = function(section, key, label, settings, options) {
        var current = (settings[section] && settings[section][key] !== undefined) ? settings[section][key] : '';
        var html = '<div class="col-md-6"><label class="form-label small">' + label + '</label>' +
            '<select class="form-select form-select-sm" data-section="' + section + '" data-key="' + key + '">';
        for (var val in options) {
            html += '<option value="' + me.escapeHtml(val) + '"' +
                (val === String(current) ? ' selected' : '') + '>' +
                me.escapeHtml(options[val]) + '</option>';
        }
        html += '</select></div>';
        return html;
    };

    /**
     * Save settings from the admin form
     *
     * @name Auth.saveSettings
     * @function
     */
    me.saveSettings = function() {
        var settings = {};

        $('#admin-settings-form [data-section][data-key]').each(function() {
            var section = $(this).data('section');
            var key = $(this).data('key');
            if (!settings[section]) { settings[section] = {}; }

            if ($(this).is(':checkbox')) {
                settings[section][key] = $(this).is(':checked');
            } else {
                settings[section][key] = $(this).val();
            }
        });

        me.apiCall({
            auth_action: 'save_settings',
            settings: settings,
            csrf_token: csrfToken
        }, function() {
            $('#settings-success').text('Settings saved successfully. Some changes may require a page reload.').removeClass('d-none');
            $('#settings-error').addClass('d-none');
        }, function(msg) {
            $('#settings-error').text(msg).removeClass('d-none');
            $('#settings-success').addClass('d-none');
        });
    };

    /**
     * Perform login
     *
     * @name Auth.login
     * @function
     * @param {string} username
     * @param {string} password
     */
    me.login = function(username, password) {
        me.apiCall({
            auth_action: 'login',
            username: username,
            password: password
        }, function(data) {
            csrfToken = data.csrf || '';
            currentUser = { username: data.username, role: data.role };
            me.removeModal();
            me.renderAuthUI();
            window.location.reload();
        });
    };

    /**
     * Perform logout
     *
     * @name Auth.logout
     * @function
     */
    me.logout = function() {
        me.apiCall({
            auth_action: 'logout'
        }, function() {
            currentUser = null;
            csrfToken = '';
            window.location.reload();
        });
    };

    /**
     * Perform registration
     *
     * @name Auth.register
     * @function
     * @param {string} username
     * @param {string} password
     */
    me.register = function(username, password) {
        me.apiCall({
            auth_action: 'register',
            username: username,
            password: password
        }, function(data) {
            csrfToken = data.csrf || '';
            currentUser = { username: data.username, role: data.role };
            me.removeModal();
            me.renderAuthUI();
            window.location.reload();
        });
    };

    /**
     * Load users list (admin)
     *
     * @name Auth.loadUsers
     * @function
     */
    me.loadUsers = function() {
        me.apiCall({
            auth_action: 'users',
            csrf_token: csrfToken
        }, function(data) {
            var $list = $('#admin-users-list');
            if (!data.users || data.users.length === 0) {
                $list.html('<p class="text-muted">No users found.</p>');
                return;
            }

            var table = '<table class="table table-sm table-striped">' +
                '<thead><tr><th>Username</th><th>Role</th><th>Active</th><th>Created</th><th>Last Login</th><th>Actions</th></tr></thead><tbody>';

            for (var i = 0; i < data.users.length; i++) {
                var u = data.users[i];
                var created = u.created_at ? new Date(u.created_at * 1000).toLocaleDateString() : 'N/A';
                var lastLogin = u.last_login ? new Date(u.last_login * 1000).toLocaleDateString() : 'Never';
                var activeClass = u.is_active ? 'text-success' : 'text-danger';
                var activeText = u.is_active ? 'Yes' : 'No';
                var isSelf = currentUser && u.username === currentUser.username;

                table += '<tr>' +
                    '<td>' + me.escapeHtml(u.username) + (isSelf ? ' <em>(you)</em>' : '') + '</td>' +
                    '<td><span class="badge bg-' + (u.role === 'admin' ? 'warning' : 'secondary') + '">' + u.role + '</span></td>' +
                    '<td class="' + activeClass + '">' + activeText + '</td>' +
                    '<td>' + created + '</td>' +
                    '<td>' + lastLogin + '</td>' +
                    '<td>';

                if (!isSelf) {
                    table += '<button class="btn btn-xs btn-outline-danger admin-delete-user" data-username="' + me.escapeHtml(u.username) + '" title="Delete">&#x2716;</button> ';
                    table += '<button class="btn btn-xs btn-outline-secondary admin-toggle-active" data-username="' + me.escapeHtml(u.username) + '" data-active="' + (u.is_active ? '0' : '1') + '" title="' + (u.is_active ? 'Disable' : 'Enable') + '">' + (u.is_active ? '&#x23F8;' : '&#x25B6;') + '</button>';
                }

                table += '</td></tr>';
            }

            table += '</tbody></table>';
            $list.html(table);

            $list.find('.admin-delete-user').on('click', function() {
                var username = $(this).data('username');
                if (confirm('Delete user "' + username + '"? This cannot be undone.')) {
                    me.adminDeleteUser(username);
                }
            });

            $list.find('.admin-toggle-active').on('click', function() {
                var username = $(this).data('username');
                var active = $(this).data('active') === 1 || $(this).data('active') === '1';
                me.adminToggleActive(username, active);
            });
        });
    };

    /**
     * Admin: create user
     *
     * @name Auth.adminCreateUser
     * @function
     * @param {string} username
     * @param {string} password
     * @param {string} role
     */
    me.adminCreateUser = function(username, password, role) {
        me.apiCall({
            auth_action: 'create_user',
            username: username,
            password: password,
            role: role,
            csrf_token: csrfToken
        }, function() {
            me.showSuccess('User created successfully.');
            $('#admin-new-username').val('');
            $('#admin-new-password').val('');
            me.loadUsers();
        });
    };

    /**
     * Admin: delete user
     *
     * @name Auth.adminDeleteUser
     * @function
     * @param {string} username
     */
    me.adminDeleteUser = function(username) {
        me.apiCall({
            auth_action: 'delete_user',
            username: username,
            csrf_token: csrfToken
        }, function() {
            me.showSuccess('User deleted.');
            me.loadUsers();
        });
    };

    /**
     * Admin: toggle active status
     *
     * @name Auth.adminToggleActive
     * @function
     * @param {string} username
     * @param {boolean} active
     */
    me.adminToggleActive = function(username, active) {
        me.apiCall({
            auth_action: 'toggle_active',
            username: username,
            active: active ? '1' : '0',
            csrf_token: csrfToken
        }, function() {
            me.loadUsers();
        });
    };

    /**
     * Make API call to auth endpoint
     *
     * @name Auth.apiCall
     * @function
     * @param {object} data - request data
     * @param {function} success - success callback
     */
    me.apiCall = function(data, success, errorCallback) {
        // Determine the base URL: use <base> tag href or fallback to origin + pathname
        var baseUrl = document.querySelector('base') ? document.querySelector('base').href : (window.location.origin + window.location.pathname);

        fetch(baseUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'JSONHttpRequest'
            },
            body: JSON.stringify(data)
        }).then(function(resp) {
            return resp.text().then(function(text) {
                return { status: resp.status, text: text };
            });
        }).then(function(result) {
            var response;
            try {
                response = JSON.parse(result.text.trim());
            } catch (e) {
                var msg = 'Invalid server response (HTTP ' + result.status + ').';
                if (typeof errorCallback === 'function') {
                    errorCallback(msg);
                } else {
                    me.showError(msg);
                }
                return;
            }
            if (response.status === 0) {
                if (typeof success === 'function') {
                    success(response);
                }
            } else {
                var errMsg = response.message || 'An error occurred.';
                if (typeof errorCallback === 'function') {
                    errorCallback(errMsg);
                } else {
                    me.showError(errMsg);
                }
            }
        }).catch(function(err) {
            var msg = 'Network error: ' + (err.message || 'Request failed.');
            if (typeof errorCallback === 'function') {
                errorCallback(msg);
            } else {
                me.showError(msg);
            }
        });
    };

    /**
     * Show error message in modal
     *
     * @name Auth.showError
     * @function
     * @param {string} message
     */
    me.showError = function(message) {
        var $error = $('#auth-error');
        if ($error.length) {
            $error.text(message).removeClass('d-none');
        }
        var $success = $('#auth-success');
        if ($success.length) {
            $success.addClass('d-none');
        }
    };

    /**
     * Show success message in modal
     *
     * @name Auth.showSuccess
     * @function
     * @param {string} message
     */
    me.showSuccess = function(message) {
        var $success = $('#auth-success');
        if ($success.length) {
            $success.text(message).removeClass('d-none');
        }
        var $error = $('#auth-error');
        if ($error.length) {
            $error.addClass('d-none');
        }
    };

    /**
     * Remove existing auth modal
     *
     * @name Auth.removeModal
     * @function
     */
    me.removeModal = function() {
        var $modal = $('#auth-modal');
        if ($modal.length) {
            var instance = bootstrap.Modal.getInstance($modal[0]);
            if (instance) {
                instance.hide();
            }
            $modal.remove();
        }
        $('.modal-backdrop').remove();
    };

    /**
     * Escape HTML entities
     *
     * @name Auth.escapeHtml
     * @function
     * @param {string} str
     * @return {string}
     */
    me.escapeHtml = function(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    };

    return me;
})(jQuery);

// Auto-initialize from embedded config when loaded via defer
(function() {
    'use strict';
    var el = document.getElementById('auth-config');
    if (el && jQuery.PrivateBin && jQuery.PrivateBin.Auth) {
        try {
            var config = JSON.parse(el.textContent);
            jQuery.PrivateBin.Auth.init(config);
        } catch (e) {
            // config parse error — auth will not initialize
        }
    }
})();
