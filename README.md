# [![PrivateBin](https://cdn.rawgit.com/PrivateBin/assets/master/images/preview/logoSmall.png)](https://privatebin.info/)

*Current version: 3.0.0 (PQC-enabled fork)*

**PrivateBin-PQC** is a minimalist, open source online
[pastebin](https://en.wikipedia.org/wiki/Pastebin)
where the server has zero knowledge of stored data.

Data is encrypted and decrypted in the browser using 256bit AES in
[Galois Counter mode](https://en.wikipedia.org/wiki/Galois/Counter_Mode),
with optional **post-quantum cryptography** (ML-KEM/Kyber-768) for
protection against harvest-now, decrypt-later attacks.

This is a fork of [PrivateBin](https://privatebin.info/), which itself
is a fork of ZeroBin, originally developed by
[Sébastien Sauvage](https://github.com/sebsauvage/ZeroBin).

## Post-Quantum Cryptography (PQC)

This fork includes **experimental post-quantum cryptography** protection against
harvest-now, decrypt-later attacks using ML-KEM (Kyber-768).

### What Changed

- **New pastes use hybrid encryption** (classical + post-quantum)
- **URLs remain short** (~43 characters, same format as v2)
- **Legacy pastes continue to work unchanged** (full backward compatibility)
- **Graceful fallback** for older browsers (automatic v2 fallback)

### How It Works

1. **Sender creates a paste:** Browser generates ephemeral Kyber-768 keypair, encapsulates shared secret
2. **Hybrid key derivation:** Content key = HKDF-SHA-256(shared_secret || urlKey)
3. **Defense-in-depth:** Attacker needs to break BOTH Kyber AND obtain URL to decrypt
4. **Zero-knowledge preserved:** Server sees encrypted data only, no keys

### Browser Requirements

**Modern browsers with PQC support (v3 pastes):**
- Chrome 90+
- Firefox 88+
- Safari 15+
- Edge 90+

**Older browsers:**
- Automatically fall back to classical encryption (v2 pastes)
- No user intervention needed

**Required browser APIs:**
- Web Crypto API (crypto.subtle)
- WebAssembly
- Secure Random (crypto.getRandomValues)
- HKDF support

### Security Scope

**✅ Protects Against:**
- Future quantum cryptanalysis of harvested pastes
- Long-term confidentiality (10+ years)
- Adversaries who store encrypted pastes today

**❌ Does NOT Protect Against:**
- Endpoint compromise (malicious browser extensions, malware)
- URL interception (if URL captured, paste can be decrypted)
- Social engineering (voluntary URL sharing)
- Malicious server administrators

**Important:** This is honest scope control. PQC protects against future cryptanalysis, not endpoint security.

### URL Retention Risks

**Critical:** URLs contain decryption keys. Avoid sharing via:
- ❌ Email (may be archived indefinitely)
- ❌ Ticketing systems (long-term retention)
- ❌ Chat with history (enables future access)
- ❌ Public forums or websites

**Prefer:**
- ✅ In-person sharing
- ✅ Verbal communication
- ✅ Ephemeral messaging (Signal, WhatsApp with disappearing messages)
- ✅ Password-protected pastes with separate password delivery

### For Developers

- **Implementation:** See `js/pqccrypto.js` for PQC module
- **Security model:** See [SECURITY.md](SECURITY.md) for complete threat model and design rationale
- **Tests:** Run `cd js && npm test` to execute test suite
- **WASM library:** Uses [mlkem-wasm](https://github.com/dchest/mlkem-wasm) (npm package)

### Strategic Positioning

This is not just "PrivateBin with PQC" — it's a **reference design** for post-quantum, zero-knowledge, browser-based secure exchange that:
- Can be cited in academic papers
- Survives security audits
- Provides a template for PQC integration in web applications
- Demonstrates infrastructure-grade cryptographic design

### Installation

```bash
# Install dependencies (includes mlkem-wasm)
cd js
npm install

# Run tests
npm test

# Deploy as normal PrivateBin instance
# (See standard PrivateBin installation guide)
```

### Algorithm Agility

The v3 format supports algorithm migration:
- **Current (v3.0):** Kyber-768 only
- **Future (v3.1):** Add Kyber-1024 support
- **Future (v4.0):** Migrate to final NIST ML-KEM standard

Designed for smooth transitions as cryptographic standards evolve.

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

- The "key" used to encrypt the paste is part of the URL. If you publicly post
  the URL of a paste that is not password-protected, anyone can read it.
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
file](https://github.com/PrivateBin/PrivateBin/wiki/Configuration):

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

## Further resources

* [FAQ](https://github.com/PrivateBin/PrivateBin/wiki/FAQ)

* [Installation guide](https://github.com/PrivateBin/PrivateBin/blob/master/doc/Installation.md#installation)

* [Configuration guide](https://github.com/PrivateBin/PrivateBin/wiki/Configuration)

* [Templates](https://github.com/PrivateBin/PrivateBin/wiki/Templates)

* [Translation guide](https://github.com/PrivateBin/PrivateBin/wiki/Translation)

* [Developer guide](https://github.com/PrivateBin/PrivateBin/wiki/Development)

Run into any issues? Have ideas for further developments? Please
[report](https://github.com/PrivateBin/PrivateBin/issues) them!
