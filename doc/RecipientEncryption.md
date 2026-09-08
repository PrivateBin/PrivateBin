# Experimental recipient encryption

PrivateBin can optionally encrypt a new document for one ML-KEM-768 recipient.
This mode is aimed at the weak point in ordinary bearer links: the random AES
content key normally has to travel in the URL fragment.

Enable it in `cfg/conf.php`:

```ini
[main]
recipientencryption = true
```

It is disabled by default. The bundled `@noble/post-quantum` implementation has
not been independently audited and documents that its JavaScript code does not
provide side-channel protection. Treat the feature as experimental until that
dependency and the protocol have received focused security review.

## Workflow

1. The recipient enables post-quantum recipient encryption on the create page,
   generates a key pair, downloads the key file, and sends only the displayed
   public key to the sender through an authenticated channel.
2. The sender enables the option, pastes the recipient public key, and creates
   the document.
3. The resulting URL ends in `#pqc`; it contains no AES content key. The sender
   sends that URL to the recipient.
4. When opening the document, the recipient pastes the downloaded key file into
   the secret-key prompt. PrivateBin keeps it only in memory long enough to
   recover the content key.

ML-KEM provides confidentiality, not sender authentication. A substituted
recipient public key lets the substituting party decrypt a newly created paste,
so the public key and its 16-character key ID must be verified through a trusted
channel. TLS and trusted browser code remain necessary.

## Version 3 envelope

The paste body remains encrypted with the existing AES-256-GCM `CryptTool` and
may still use a password. Version 3 adds an object at `adata[4]`:

```json
{
  "type": "recipient",
  "suite": "ML-KEM-768+HKDF-SHA-256+AES-256-GCM",
  "kid": "16-char-key-id",
  "kemct": "base64 ML-KEM ciphertext",
  "salt": "base64 32-byte HKDF salt",
  "iv": "base64 12-byte AES-GCM IV",
  "wrappedkey": "base64 wrapped 32-byte content key"
}
```

The sender encapsulates with ML-KEM-768, derives a key-encryption key using
HKDF-SHA-256 with the domain string `PrivateBin recipient encryption v3`, and
wraps the 32-byte content key with AES-256-GCM. The envelope header is the
key-wrap AAD, and the complete `adata` array—including `wrappedkey`—is also AAD
for the encrypted paste body. This binds the suite, recipient key ID, KEM
ciphertext, KDF salt, IV, and wrapped key to the paste.

Comments remain version 2 AES-GCM records encrypted under the recovered content
key. They do not need separate ML-KEM envelopes.

## Scope and limitations

- One recipient is supported per paste in this first version.
- Keep `recipientencryption` enabled while version 3 pastes remain in use; the
  ML-KEM browser bundle is loaded only when the option is enabled.
- Losing the ML-KEM secret key makes the document unrecoverable.
- PrivateBin does not store the secret key in local storage or send it to the
  server.
- URL shortening is safe from content-key disclosure in this mode, but a
  shortener still learns the paste URL and access metadata.
- ML-KEM does not protect against malicious JavaScript served by a compromised
  PrivateBin instance.
- AES-256-GCM remains the content cipher; ML-KEM protects distribution of its
  random key rather than replacing symmetric encryption.

The browser bundle is reproducible with `cd js && npm ci && npm run build:pqc`.
Dependencies are pinned in `package-lock.json`, the generated asset is committed,
and PrivateBin serves it with an SHA-512 subresource-integrity value.
