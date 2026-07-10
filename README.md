# [![PrivateBin](https://raw.githubusercontent.com/PrivateBin/assets/master/images/preview/logoSmall.png)](https://privatebin.info/) PrivateBin Auth Edition

*Based on PrivateBin 2.0.4*

**PrivateBin Auth Edition** is a fork of [PrivateBin](https://privatebin.info/)
that adds **built-in user authentication**, an **admin panel**, and a
**web-based installer** — so you can run a secure, private pastebin that only
authorized users can access, without relying on `.htaccess`, SSO, or third-party
auth solutions.

All the core PrivateBin features are preserved: data is encrypted and decrypted
in the browser using 256-bit AES in
[Galois Counter mode](https://en.wikipedia.org/wiki/Galois/Counter_Mode), and
the server has **zero knowledge** of stored data.

> **Upstream:** This fork is based on
> [PrivateBin](https://github.com/PrivateBin/PrivateBin), originally a fork of
> ZeroBin by [Sébastien Sauvage](https://github.com/sebsauvage/ZeroBin).

## What this fork adds

### 🔐 Built-in User Authentication

* Username / password login with bcrypt hashing (cost 12, unique salt per user)
* Role-based access: **admin** and **user** roles
* Configurable access policy — require login to create pastes, read pastes, or
  both
* Optional self-registration (enable/disable in settings)
* Cookie-based sessions with CSRF protection (HMAC-SHA256)
* Timing-safe login to prevent user enumeration

### 👤 Admin Panel

* **User management** — create, delete, activate/deactivate users, change roles
  and passwords
* **Settings management** — edit all `conf.php` options from the browser (no
  manual file editing required)
* Protected sections (`model`, `model_options`) cannot be altered via the UI

### 🧙 Web-Based Installer

* Accessible at `/install/` — guides you through first-time setup
* Configures database connection (SQLite, MySQL, PostgreSQL)
* Creates the admin account
* Sets all PrivateBin options (discussions, file upload, template, expiration,
  etc.)
* Generates `cfg/conf.php` automatically
* Delete the `install/` folder after setup for security

## What PrivateBin provides

+ As a server administrator you don't have to worry if your users post content
  that is considered illegal in your country. You have plausible deniability of
  any of the pastes content. If requested or enforced, you can delete any paste
  from your system.

+ Pastebin-like system to store text documents, code samples, etc.

+ Encryption of data sent to server.

+ Possibility to set a password which is required to read the paste. It further
  protects a paste and prevents people stumbling upon your paste's link
  from being able to read it without the password.

## What it doesn't provide

- As a user you have to trust the server administrator not to inject any
  malicious code. For security, a PrivateBin installation *has to be used over*
  *HTTPS*! Otherwise you would also have to trust your internet provider, and
  any jurisdiction the traffic passes through. Additionally the instance should
  be secured by
  [HSTS](https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security). It can
  use traditional certificate authorities and/or use a
  [DNSSEC](https://en.wikipedia.org/wiki/Domain_Name_System_Security_Extensions)
  protected
  [DANE](https://en.wikipedia.org/wiki/DNS-based_Authentication_of_Named_Entities)
  record.

- The "key" used to encrypt the paste is part of the URL (in
  [the fragment part separated by the `#`](https://en.wikipedia.org/wiki/URL#fragment)).
  If you publicly post the URL of a paste that is not password-protected, anyone
  can read it.
  Use a password if you want your paste to remain private. In that case, make
  sure to use a strong password and share it privately and end-to-end-encrypted.

- A server admin can be forced to hand over access logs to the authorities.
  PrivateBin encrypts your text and the discussion contents, but who accessed a
  paste (first) might still be disclosed via access logs.

- In case of a server breach your data is secure as it is only stored encrypted
  on the server. However, the server could be abused or the server admin could
  be legally forced into sending malicious code to their users, which logs
  the decryption key and sends it to a server when a user accesses a paste.
  Therefore, do not access any PrivateBin instance if you think it has been
  compromised. As long as no user accesses this instance with a previously
  generated URL, the content can't be decrypted.

## Options

Some features are optional and can be enabled or disabled in the [configuration
file](https://github.com/PrivateBin/PrivateBin/wiki/Configuration) or via the
**admin settings panel** (when authentication is enabled):

* Password protection

* Discussions, anonymous or with nicknames and IP based identicons or vizhashes

* Expiration times, including a "forever" and "burn after reading" option

* Markdown format support for HTML formatted pastes, including preview function

* Syntax highlighting for source code using prettify.js, including 4 prettify
  themes

* File upload support, image, media and PDF preview (disabled by default, size
  limit adjustable)

* Templates: By default there are bootstrap5, bootstrap CSS and darkstrap
  to choose from and it is easy to adapt these to your own websites layout or
  create your own.

* Translation system and automatic browser language detection (if enabled in
  browser)

* Language selection (disabled by default, as it uses a session cookie)

* QR code for paste URLs, to easily transfer them over to mobile devices

* **Built-in user authentication** with role-based access control (this fork)

## Quick Start

1. Extract the release to your web server's document root
2. Navigate to `https://your-domain/install/` in your browser
3. Follow the installer to configure the database and create an admin account
4. **Delete the `install/` folder** after successful setup
5. Log in and manage users and settings from the admin panel

For manual configuration, see [Installation](doc/Installation.md).

## Further resources

* [PrivateBin FAQ](https://github.com/PrivateBin/PrivateBin/wiki/FAQ)

* [Installation guide](doc/Installation.md)

* [PrivateBin Configuration guide](https://github.com/PrivateBin/PrivateBin/wiki/Configuration)

* [PrivateBin Templates](https://github.com/PrivateBin/PrivateBin/wiki/Templates)

* [PrivateBin Translation guide](https://github.com/PrivateBin/PrivateBin/wiki/Translation)

* [PrivateBin Developer guide](https://github.com/PrivateBin/PrivateBin/wiki/Development)

Run into any issues? Have ideas for further developments? Please
[report](https://github.com/EloTunk/PrivateBin-auth/issues) them!

## Credits

This project is a fork of [PrivateBin](https://github.com/PrivateBin/PrivateBin)
by the PrivateBin community. All upstream features and security properties are
preserved. The authentication system, admin panel, and web installer were added
as extensions to the existing architecture.
