# Agent Guide

This document provides context for AI agents working on this codebase.

## Project Overview

PrivateBin is a zero-knowledge pastebin written in PHP (backend) and vanilla JavaScript (frontend). Data is encrypted/decrypted client-side using 256-bit AES-GCM. The server never sees plaintext content.

## Architecture

### Backend (PHP)

- **Entry point**: `index.php` — defines `PATH` and `PUBLIC_PATH`, loads autoloader, instantiates `Controller`
- **`lib/Controller.php`** — Main controller. Routes requests (`create`, `read`, `delete`, `jsonld`, proxy). Outputs JSON API responses or renders HTML via `View`.
- **`lib/Configuration.php`** — Parses `cfg/conf.php` (INI format), merges with defaults in `$_defaults`. Add new config sections/keys here.
- **`lib/Request.php`** — Parses incoming HTTP requests (operation, params, JSON body).
- **`lib/Model.php`** — Factory for storage backends.
- **`lib/Persistence/`** — `ServerSalt`, `TrafficLimiter`, `DataStore` — server-side persistence helpers.
- **`lib/FormatV2.php`** — Validates paste/comment data format.
- **`lib/View.php`** — Simple template engine. `assign()` sets variables, `draw()` renders a template from `tpl/`.

### Frontend (JavaScript)

- **`js/privatebin.js`** — Single-file modular JS using the revealing module pattern. Key modules:
  - `ServerInteraction` — AJAX requests (jQuery `$.ajax`), handles encryption/decryption via Web Crypto API
  - `Editor` — Paste creation UI
  - `PasteViewer` — Paste display
  - `TopNav` — Navigation bar actions
  - `Alert` — User notifications
  - `AuthPrompt` — Authentication modal for HTTP Basic Auth (when `auth.enabled` is true)
  - `Controller.init()` — Initializes all modules at `$(document).ready()`

### Templates

- `tpl/bootstrap5.php` — Bootstrap 5 template (default)
- `tpl/bootstrap.php` — Bootstrap 3 template (with dark/compact/page variants via CSS)
- Templates receive variables from `Controller::_view()` via `$page->assign()`
- Config values are passed to JS via `data-*` attributes on `<body>` (e.g., `data-compression`, `data-authrequired`)

### Configuration

- **`cfg/conf.sample.php`** — Documented sample config (INI format behind a PHP guard)
- **`cfg/conf.php`** — Actual config (not in repo)
- Sections: `[main]`, `[expire]`, `[expire_options]`, `[formatter_options]`, `[traffic]`, `[purge]`, `[model]`, `[model_options]`, `[auth]`, `[yourls]`, `[shlink]`, `[sri]`

## Key Patterns

- **Adding a config option**: Add default in `Configuration.php` `$_defaults`, document in `cfg/conf.sample.php`, read via `$this->_conf->getKey('key', 'section')` in `Controller.php`.
- **Exposing config to JS**: In `Controller::_view()`, call `$page->assign('VARNAME', value)`. In templates, output as a `data-*` attribute on `<body>`. In JS, read with `$('body').data('varname')`.
- **Adding a modal**: Add HTML in both `tpl/bootstrap5.php` and `tpl/bootstrap.php`. Handle in JS with a new module or existing one.

## Testing

- PHP tests: `php vendor/bin/phpunit`
- JS tests: `npx mocha`
- Test fixtures and configuration overrides are in `tst/`

## File Layout

```
cfg/              Configuration files
css/              Stylesheets
doc/              Documentation
img/              Images and icons
js/               JavaScript (privatebin.js + vendored libs)
lib/              PHP source code
tpl/              PHP templates
tst/              Tests
vendor/           Composer dependencies
index.php         Entry point
```
