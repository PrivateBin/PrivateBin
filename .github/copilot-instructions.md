# Copilot Coding Agent Onboarding for PrivateBin

## Repository Overview

**PrivateBin** is a minimalist, open-source online pastebin where the server has zero knowledge of the pasted data. All data is encrypted/decrypted in the browser via 256-bit AES (Galois Counter Mode). The project is a refactored fork of ZeroBin focused on extensibility and additional features.

- **Main Use Case:** Secure, ephemeral sharing of text/code, with encryption happening exclusively client-side.
- **Languages:** PHP (~48%), JavaScript (~35%), CSS (~17%), with some legacy browser support (see `legacy.js`).
- **Type:** Web application (pastebin), with both server (PHP) and client (JavaScript) components.

## Build & Validation Instructions

### Prerequisites

- **PHP:** 7.4+ (recommended: latest stable 7.x or 8.x)
- **Composer:** For dependency management (`composer install`)
- **Node.js & npm:** Only required for running JavaScript unit tests. **Main JS logic must remain browser-compatible!**
- **Recommended Environment:** Unix-like OS (Linux, macOS) with Apache or Nginx for full demo.

### Bootstrap & Install

1. **Clone the repository** and enter its root directory.
2. **Install PHP dependencies:**
   ```sh
   composer require --global google/cloud-storage phpunit/phpunit
   ```
   - Always run this before building or testing PHP code.
   - If you receive permission errors, verify `vendor/` is writable.

3. **Install JS dependencies (for test only):**
   ```sh
   cd ./js
   npm install
   ```
   - Only required for running JS tests. Not needed for building or running the app.

### Build

- **No explicit build step** for PHP. The web app is served directly from source.
  - This means **composer directory** need to be comitted (_except_ of big optional dependences like Google Cloud like GCS support or similar!)
- **For JavaScript:** There is no webpack/bundler step for release; browser JS is written in compatible ES6+ syntax, except in `legacy.js` (which must be designed to run cleanly even on ancient IE4 or Netscape to display the error message that a browser upgrade is necessary). We are trying to avoid jQuery in any new code and would like to eventually drop use of jQuery. We are considering modularizing the JS logic, but need to ensure to do so in a way that will work both in the browser as well as for node JS driven unit tests.

### Run

- **PHP Server Mode:** Use Apache/Nginx with PHP, pointing the web root to the repo root.
- **Demo:** Open the root directory served by the web server in a browser. This should call the index.php in the repositories root directory.

### Test

- **PHP Unit Tests:**
  ```sh
  vendor/bin/phpunit
  ```
  - Always run after code changes to backend logic.
  - If `vendor/bin/phpunit` does not exist, ensure `composer install` completed without errors.

  ```sh
  cd ./js
  npm run test
  ```
  - Runs Mocha-based tests in Node.js context. Tests are implemented in BDD-Style or using jsVerify fixtures for property-based tests.
  - Note: **Production JS must not use Node-only APIs.** Test code may use Node.js features, but main JS logic must remain browser-compatible.
  - If you encounter `ReferenceError` for browser features, ensure only test code uses Node.js APIs.

### Lint

- **PHP:** Run (if `phpcs.xml` or similar config exists):
  ```sh
  vendor/bin/phpcs
  ```
- **JavaScript:** If `eslint` is present:
  ```sh
  npm run lint
  ```
  - Check for configuration in `.eslintrc.*` files.

### Validation / CI

- **GitHub Actions:** CI runs `composer install`, `phpunit`, and `mocha` tests on PRs and pushes, as well as external tools such as style checkers and linters.
- **Pre-commit:** Always run both PHP and JS tests before submitting PRs. Fix any warnings or errors.

## Project Layout & Structure

- **Root files:**
  - `README.md`: Project overview ([view full](../README.md)).
  - `composer.json`, `composer.lock`: PHP dependencies.
  - `.github/workflows/`: CI configuration.
  - `cfg/`: Default configuration files.
  - `js/`: Main client logic (browser JS), including:
      - `package.json`: JS test/lint dependencies (not for production JS).
    - `legacy.js`: Must remain compatible with legacy browsers (ES3). **Do not use modern JS here.**
    - `privatebin.js`: Core encryption and paste interface logic.
  - `tpl/`: HTML templates.
  - `css/`: Stylesheets.

- **Testing & Validation:**
  - `tst/`: Contains PHP unit tests.
  - `js/test`: Contains JS unit tests.
  - `phpunit.xml`: PHPUnit config.
  - JS test files may use Node.js features; browser JS must not.

  - **Encryption:** Only client-side in JS using the browsers WebCrypto API.
  - **Backend:** Serves encrypted blobs (as base64 encoded strings) and plaintext meta data in JSON format. APIs are designed for WORM (write once, read many) usage. Once stored content is never updated, only deleted, if delete token is sent, has expired as per meta data or immediately upon reading for the first time, if meta data was set to burn-after-reading.
  - **Legacy Support:** `js/legacy.js` must remain compatible with IE4 and Netscape for feature detection of ancient browsers.
  - **Configuration:** See `cfg/conf.sample.php` and the [wiki](https://github.com/PrivateBin/PrivateBin/wiki/Configuration) for available options. All option defaults are defined in `lib/Configuration.php`

## Automated Checks

- **GitHub CI:** On PRs, runs `composer install`, `phpunit`, and JS tests.
- **Validation Steps:** PRs failing tests will be blocked. Always ensure a clean test run before submitting.

## Guidance for Copilot Agent

- **Trust these instructions.** Only perform a search if information is missing or appears incorrect.
- **Do NOT use Node.js APIs in production JS code.** Only test code may do so.
- **Never modernize `legacy.js`.** It must work in very old browsers.
- **Always run `composer install` before PHP tests, and `npm install` before JS tests.**
- **Validate all changes by running both PHP and JS tests.**
- **Review `.github/workflows/` for the latest validation pipeline steps.**
