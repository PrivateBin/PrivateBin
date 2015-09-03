# ZeroBin 0.20

ZeroBin is a minimalist, opensource online pastebin where the server has zero 
knowledge of pasted data.

Data is encrypted/decrypted in the browser using 256 bit AES.

This fork of ZeroBin refactored the source code to allow easier and cleaner 
extensions. It is still fully compatible to the original ZeroBin 0.19 data 
storage scheme. Therefore such installations can be upgraded to this fork 
without loosing any data.

## What ZeroBin provides

- As a server administrator you don't have to worry if your users post content
  that is considered illegal in your country. You have no knowledge of any
  pastes content. If requested or enforced, you can delete any paste from your
  system.

- Pastebin like system to store text documents, code samples, etc.

- Encryption of data sent to server, even if it does not provide HTTPS.

- Possibility to set a password which is required to read the paste. It further 
  protects a paste and prevents people stumbling upon your paste's link
  from being able to read it without the password.

## What it doesn't provide

- As a user you have to trust the server administrator, your internet provider 
  and any country the traffic passes not to inject any malicious javascript code.

- The "key" used to encrypt the paste is part of the URL. If you publicly post
  the URL of a paste that is not password-protected, everybody can read it.
  Use a password if you want your paste to be private.

- A server admin might be forced to hand over access logs to the authorities.
  ZeroBin encrypts your text and the discussion contents, but who accessed it
  first might still be disclosed via such access logs.

## Options

Some features are optional and can be enabled or disabled in the [configuration
file](https://github.com/elrido/ZeroBin/wiki/Configuration):

- Password protection

- Discussions

- Expiration times, including a "forever" and "burn after reading" option

- Syntax highlighting using prettify.js, including 4 prettify themes

- Templates: By default there is a bootstrap based and a "classic ZeroBin" theme
  and it is easy to adapt these to your own websites layout or create your own.

## Further resources

- [Installation guide](https://github.com/elrido/ZeroBin/wiki/Installation)

- [Configuration guide](https://github.com/elrido/ZeroBin/wiki/Configuration)

- [Developer guide](https://github.com/elrido/ZeroBin/wiki/Development)

Run into any issues? Have ideas for further developments? Please 
[report](https://github.com/elrido/ZeroBin/issues) them!

------------------------------------------------------------------------------

Copyright (c) 2012 Sébastien SAUVAGE (sebsauvage.net)

This software is provided 'as-is', without any express or implied warranty.
In no event will the authors be held liable for any damages arising from 
the use of this software.

Permission is granted to anyone to use this software for any purpose, 
including commercial applications, and to alter it and redistribute it 
freely, subject to the following restrictions:

    1. The origin of this software must not be misrepresented; you must 
       not claim that you wrote the original software. If you use this 
       software in a product, an acknowledgment in the product documentation
       would be appreciated but is not required.

    2. Altered source versions must be plainly marked as such, and must 
       not be misrepresented as being the original software.

    3. This notice may not be removed or altered from any source distribution.

------------------------------------------------------------------------------
