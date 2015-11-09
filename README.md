# ZeroBin 0.22

ZeroBin is a minimalist, opensource online pastebin where the server has zero 
knowledge of pasted data.

Data is encrypted/decrypted in the browser using 256 bit AES.

This fork of ZeroBin refactored the source code to allow easier and cleaner 
extensions. It is still fully compatible to the original ZeroBin 0.19 data 
storage scheme. Therefore such installations can be upgraded to this fork 
without loosing any data.

## What ZeroBin provides

+ As a server administrator you don't have to worry if your users post content
  that is considered illegal in your country. You have no knowledge of any
  of the pastes content. If requested or enforced, you can delete any paste from
  your system.

+ Pastebin-like system to store text documents, code samples, etc.

+ Encryption of data sent to server, even if it does not provide HTTPS.

+ Possibility to set a password which is required to read the paste. It further 
  protects a paste and prevents people stumbling upon your paste's link
  from being able to read it without the password.

## What it doesn't provide

- As a user you have to trust the server administrator, your internet provider 
  and any country the traffic passes not to inject any malicious javascript code.
  Ideally, the ZeroBin installation used would provide HTTPS, secured by 
  [HSTS](https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security) and
  [HKPH](https://en.wikipedia.org/wiki/HTTP_Public_Key_Pinning) using a 
  certificate either validated by a trusted third party (check the certificate 
  when first using a new ZeroBin instance) or self-signed by the server operator,
  validated using a
  [DNSSEC](https://en.wikipedia.org/wiki/Domain_Name_System_Security_Extensions) protected
  [DANE](https://en.wikipedia.org/wiki/DNS-based_Authentication_of_Named_Entities)
  record.

- The "key" used to encrypt the paste is part of the URL. If you publicly post
  the URL of a paste that is not password-protected, everybody can read it.
  Use a password if you want your paste to be private.

- A server admin might be forced to hand over access logs to the authorities.
  ZeroBin encrypts your text and the discussion contents, but who accessed it
  first might still be disclosed via such access logs.

## Options

Some features are optional and can be enabled or disabled in the [configuration
file](https://github.com/elrido/ZeroBin/wiki/Configuration):

* Password protection

* Discussions

* Expiration times, including a "forever" and "burn after reading" option

* Markdown format support for HTML formatted pastes

* Syntax highlighting for source code using prettify.js, including 4 prettify themes

* File upload support, images get displayed (disabled by default, possibility to adjust size limit)

* Templates: By default there is a bootstrap CSS and a "classic ZeroBin" theme
  and it is easy to adapt these to your own websites layout or create your own.

* Translation system and automatic browser language detection (if enabled in browser)

* Language selection (disabled by default, as it uses a session cookie)

## Further resources

* [Installation guide](https://github.com/elrido/ZeroBin/wiki/Installation)

* [Upgrading from 0.19 Alpha](https://github.com/elrido/ZeroBin/wiki/Upgrading-from-ZeroBin-0.19-Alpha)

* [Configuration guide](https://github.com/elrido/ZeroBin/wiki/Configuration)

* [Templates](https://github.com/elrido/ZeroBin/wiki/Templates)

* [Translation guide](https://github.com/elrido/ZeroBin/wiki/Translation)

* [Developer guide](https://github.com/elrido/ZeroBin/wiki/Development)

Run into any issues? Have ideas for further developments? Please 
[report](https://github.com/elrido/ZeroBin/issues) them!
