# [<img alt="PrivateBin" src="https://cdn.rawgit.com/PrivateBin/assets/master/images/minified/logo.svg" width="500" />](https://privatebin.info/)
[![Build Status](https://travis-ci.org/PrivateBin/PrivateBin.svg?branch=master)](https://travis-ci.org/PrivateBin/PrivateBin) [![Build Status](https://scrutinizer-ci.com/g/PrivateBin/PrivateBin/badges/build.png?b=master)](https://scrutinizer-ci.com/g/PrivateBin/PrivateBin/build-status/master)  
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/094500f62abf4c9aa0c8a8a4520e4789)](https://www.codacy.com/app/PrivateBin/PrivateBin)
[![Code Climate](https://codeclimate.com/github/PrivateBin/PrivateBin/badges/gpa.svg)](https://codeclimate.com/github/PrivateBin/PrivateBin)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PrivateBin/PrivateBin/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PrivateBin/PrivateBin/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/57c9e74e-c6f9-4de6-a876-df66ec2ea1ff/mini.png)](https://insight.sensiolabs.com/projects/57c9e74e-c6f9-4de6-a876-df66ec2ea1ff)  
[![Codacy Badge](https://api.codacy.com/project/badge/Coverage/094500f62abf4c9aa0c8a8a4520e4789)](https://www.codacy.com/app/PrivateBin/PrivateBin)
[![Test Coverage](https://codeclimate.com/github/PrivateBin/PrivateBin/badges/coverage.svg)](https://codeclimate.com/github/PrivateBin/PrivateBin/coverage) [![Code Coverage](https://scrutinizer-ci.com/g/PrivateBin/PrivateBin/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/PrivateBin/PrivateBin/?branch=master)

*Current version: 1.1*

**PrivateBin** is a minimalist, open source online pastebin where the server has
zero knowledge of pasted data.

Data is encrypted/decrypted in the browser using 256bit AES in [Galois Counter mode](https://en.wikipedia.org/wiki/Galois/Counter_Mode).

This is a fork of ZeroBin, originally developed by
[Sébastien Sauvage](https://github.com/sebsauvage/ZeroBin). It was refactored
to allow easier and cleaner extensions and has now much more features than the
original. It is however still fully compatible to the original ZeroBin 0.19
data storage scheme. Therefore such installations can be upgraded to this fork
without loosing any data.

## What PrivateBin provides

+ As a server administrator you don't have to worry if your users post content
  that is considered illegal in your country. You have no knowledge of any
  of the pastes content. If requested or enforced, you can delete any paste from
  your system.

+ Pastebin-like system to store text documents, code samples, etc.

+ Encryption of data sent to server.

+ Possibility to set a password which is required to read the paste. It further
  protects a paste and prevents people stumbling upon your paste's link
  from being able to read it without the password.

## What it doesn't provide

- As a user you have to trust the server administrator, your internet provider
  and any country the traffic passes not to inject any malicious javascript code.
  For a basic security the PrivateBin installation *has to provide HTTPS*!
  Additionally it should be secured by
  [HSTS](https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security) and
  ideally by [HPKP](https://en.wikipedia.org/wiki/HTTP_Public_Key_Pinning) using a
  certificate either validated by a trusted third party (check the certificate
  when first using a new PrivateBin instance) or self-signed by the server
  operator, validated using a
  [DNSSEC](https://en.wikipedia.org/wiki/Domain_Name_System_Security_Extensions)
  protected
  [DANE](https://en.wikipedia.org/wiki/DNS-based_Authentication_of_Named_Entities)
  record.

- The "key" used to encrypt the paste is part of the URL. If you publicly post
  the URL of a paste that is not password-protected, everybody can read it.
  Use a password if you want your paste to be private. In this case make sure to
  use a strong password and do only share it privately and end-to-end-encrypted.

- A server admin might be forced to hand over access logs to the authorities.
  PrivateBin encrypts your text and the discussion contents, but who accessed it
  first might still be disclosed via such access logs.

- In case of a server breach your data is secure as it is only stored encrypted
  on the server. However the server could be misused or the server admin could
  be legally forced into sending malicious JavaScript to all web users, which
  grabs the decryption key and send it to the server when a user accesses a
  PrivateBin.  
  Therefore do not access any PrivateBin instance if you think it has been
  compromised. As long as no user accesses this instance with a previously
  generated URL, the content can''t be decrypted.

## Options

Some features are optional and can be enabled or disabled in the [configuration
file](https://github.com/PrivateBin/PrivateBin/wiki/Configuration):

* Password protection

* Discussions, anonymous or with nicknames and IP based identicons or vizhashes

* Expiration times, including a "forever" and "burn after reading" option

* Markdown format support for HTML formatted pastes, including preview function

* Syntax highlighting for source code using prettify.js, including 4 prettify
  themes

* File upload support, images get displayed (disabled by default, possibility
  to adjust size limit)

* Templates: By default there are bootstrap CSS, darkstrap and "classic ZeroBin"
  themes and it is easy to adapt these to your own websites layout or create
  your own.

* Translation system and automatic browser language detection (if enabled in
  browser)

* Language selection (disabled by default, as it uses a session cookie)

## Further resources

* [Installation guide](https://github.com/PrivateBin/PrivateBin/wiki/Installation)

* [Upgrading from ZeroBin 0.19 Alpha](https://github.com/PrivateBin/PrivateBin/wiki/Upgrading-from-ZeroBin-0.19-Alpha)

* [Configuration guide](https://github.com/PrivateBin/PrivateBin/wiki/Configuration)

* [Templates](https://github.com/PrivateBin/PrivateBin/wiki/Templates)

* [Translation guide](https://github.com/PrivateBin/PrivateBin/wiki/Translation)

* [Developer guide](https://github.com/PrivateBin/PrivateBin/wiki/Development)

Run into any issues? Have ideas for further developments? Please
[report](https://github.com/PrivateBin/PrivateBin/issues) them!
