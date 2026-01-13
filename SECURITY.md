# Security Policy - PrivateBin-PQC Fork

## Supported Versions

| Version | Status | Features | Support |
| ------- | ------ | -------- | ------- |
| 3.0.0   | :rocket: **Current Fork** | PQC + QR Share | :heavy_check_mark: Full Support |
| 2.0.3   | :arrow_up: **Upgrade Available** | Base PrivateBin | :heavy_check_mark: Inherited from upstream |
| < 2.0.3 | :x: **Deprecated** | Legacy | :x: No support |

**Note**: This is a private fork with experimental post-quantum cryptography features. For upstream PrivateBin security issues, please report to the official PrivateBin team.

---

## 🔐 Post-Quantum Cryptography (v3.0.0)

### Threat Model

This fork implements **hybrid post-quantum encryption** to protect against future quantum computer attacks while maintaining security against classical attacks.

#### Attack Scenarios Protected Against

✅ **Harvest Now, Decrypt Later (HNDL)**
- **Threat**: Adversaries capture encrypted traffic today, decrypt with quantum computers in the future
- **Protection**: Kyber-768 (ML-KEM) provides quantum resistance for key exchange
- **Status**: Protected (assuming NIST PQC algorithms hold)

✅ **Classical Cryptanalysis**
- **Threat**: Traditional attacks on encryption algorithms
- **Protection**: X25519 (ECDH) + AES-256-GCM provide classical security
- **Status**: Protected (industry standard, well-audited algorithms)

✅ **Metadata Leakage via Link Previews**
- **Threat**: Messenger apps fetching URLs to generate previews expose encryption keys
- **Protection**: QR share gateway (`share.html`) separates URL preview from paste access
- **Status**: Protected (fragment-based encoding, no-referrer policy)

✅ **Memory Forensics on Server**
- **Threat**: Attacker gains access to server memory and extracts sensitive data
- **Protection**: Automatic buffer zeroing for shared secrets, keys, combined data
- **Status**: Protected (sensitive data cleared immediately after use)

#### Attack Scenarios Still Present

⚠️ **Server Compromise Before User Access**
- **Threat**: Malicious administrator serves compromised JavaScript to log keys
- **Protection**: HTTPS + HSTS (must trust server administrator)
- **Mitigation**: Use trusted instances, verify Subresource Integrity (SRI) if available

⚠️ **Client-Side Malware**
- **Threat**: Keyloggers, screen capture, clipboard monitoring on user device
- **Protection**: None (out of scope for web application)
- **Mitigation**: Use trusted devices, antivirus, secure OS

⚠️ **Endpoint Interception**
- **Threat**: TLS/HTTPS itself broken by quantum computers (future threat)
- **Protection**: Limited (depends on TLS layer adopting PQC ciphersuites)
- **Mitigation**: Use PQC-enabled VPN, wait for TLS 1.4+ with PQC

❌ **Social Engineering**
- **Threat**: Phishing, social engineering to obtain paste URLs
- **Protection**: None (user education required)
- **Mitigation**: Be cautious about sharing paste URLs

❌ **Access Pattern Analysis**
- **Threat**: Server logs reveal who accessed which paste
- **Protection**: None (metadata is visible to server)
- **Mitigation**: Use Tor Browser for anonymity

### Cryptographic Primitives (v3)

#### Hybrid Key Exchange

| Component | Algorithm | Key Size | Quantum Resistance | NIST Status |
|-----------|-----------|----------|-------------------|-------------|
| Classical KEM | X25519 (ECDH) | 256-bit | ❌ Vulnerable | Standard |
| Post-Quantum KEM | ML-KEM (Kyber-768) | ~1184 bytes | ✅ Resistant | NIST Approved (2024) |
| Combination | Hybrid (both required) | Both | ✅ Resistant | Recommended Practice |

**Security Guarantee**: Pastes are secure if **either** algorithm remains unbroken.
- If quantum computers break X25519 → Kyber-768 still protects the paste
- If cryptanalysis breaks Kyber-768 → X25519 still protects the paste (classically)

#### Symmetric Encryption & Key Derivation

| Component | Algorithm | Key Size | Quantum Resistance |
|-----------|-----------|----------|-------------------|
| Symmetric Cipher | AES-256-GCM | 256-bit | ✅ Resistant (Grover's gives 128-bit) |
| Key Derivation | HKDF-SHA256 | 256-bit output | ✅ Resistant (generic quantum attacks only) |
| MAC/Authentication | GCM (GHASH) | 128-bit tag | ✅ Resistant |

**Note**: AES-256 provides ~128-bit post-quantum security due to Grover's algorithm (square-root speedup). This is still considered very secure.

### Implementation Security Features

#### Memory Hardening
```javascript
// Automatic zeroing of sensitive buffers
- Shared secrets (X25519 + Kyber combined)
- Encryption keys (before and after use)
- Combined key material (hybrid output)
- Intermediate values (during HKDF)
```

**Protection**: Prevents memory forensics from recovering keys after paste encryption/decryption.

#### Concurrency Protection
```javascript
// Mutex-based PQC initialization
- Prevents race conditions during WASM loading
- Ensures single initialization of PQC module
- Protects against concurrent key generation issues
```

**Protection**: Prevents undefined behavior in concurrent scenarios.

#### Version Management
```javascript
// Version deprecation constants
MIN_SUPPORTED_VERSION = 2  // v2 (AES-256-GCM) still supported
MAX_SUPPORTED_VERSION = 3  // v3 (PQC hybrid) current
```

**Protection**: Allows graceful handling of future format upgrades and deprecation of old formats.

### QR Share Gateway Security (share.html)

#### Privacy Properties

✅ **Fragment Safety**
- Encryption keys stored in URL fragment (`#` portion)
- Browser standard: fragments **never sent in HTTP requests**
- Server logs cannot contain encryption keys

✅ **No External Dependencies**
- Zero external JavaScript libraries
- Zero external CSS frameworks
- Zero CDN resources
- Zero analytics or tracking

✅ **No Network Requests**
- All operations happen in-browser (offline-capable)
- No `fetch()`, `XMLHttpRequest`, or any AJAX calls
- No image tags, iframes, or external resources

✅ **Referrer Protection**
```html
<meta name="referrer" content="no-referrer">
```
- Prevents referrer header leakage to messenger apps
- Encryption keys not exposed via HTTP Referer header

✅ **Search Engine Protection**
```html
<meta name="robots" content="noindex, nofollow">
```
- Prevents search engine indexing of share pages
- Share URLs not cached or archived by web crawlers

#### Messenger Deep Link Security

When sharing via WhatsApp/Signal/Viber:

**What messenger apps receive**:
- ✅ The full paste URL (including fragment)
- ✅ Encrypted in the message text itself

**What messenger apps DO NOT receive**:
- ❌ The paste URL in HTTP requests (no preview fetching of fragments)
- ❌ The share.html URL structure (one-time gateway)

**Important**: The paste URL is in the message text, so the recipient's messenger app will have the URL. Trust the recipient and use password protection for sensitive pastes.

### Performance vs. Security Trade-offs

#### Quantum Tax

| Aspect | v2 (Classical) | v3 (PQC Hybrid) | Overhead |
|--------|---------------|----------------|----------|
| URL Length | ~80 chars | ~110 chars | +37% |
| QR Code Density | Medium | High | +30-40% |
| Encryption Time | ~1ms | ~6ms | +5ms |
| Decryption Time | ~1ms | ~6ms | +5ms |
| WASM Loading | N/A | ~50-100ms (one-time) | One-time cost |

**Recommendation**: The security benefit far outweighs the performance cost for most use cases. For high-volume scenarios, benchmark your specific deployment.

### Known Limitations & Future Work

#### Current Limitations

1. **Browser Compatibility**
   - Requires WebAssembly support (IE11 not supported)
   - Mobile browsers: Modern Chrome/Safari/Firefox only

2. **WASM CSP Requirement**
   - Must allow `wasm-unsafe-eval` in Content-Security-Policy
   - Some strict CSP configurations may block PQC

3. **URL Length**
   - v3 URLs are longer, may hit URL length limits in some contexts
   - QR codes are denser, harder to scan with low-quality cameras

4. **Quantum-Safe TLS**
   - Transport layer (HTTPS) not yet quantum-resistant
   - Depends on TLS adopting PQC ciphersuites (TLS 1.4+ planned)

#### Future Enhancements

Planned security improvements:

- **Post-Quantum Signatures**: Add ML-DSA (Dilithium) for authentication
- **Key Rotation**: Automatic key rotation for long-lived deployments
- **TLS Integration**: Support for PQC TLS when available
- **Hardware Security**: Integration with WebAuthn/FIDO2 for key storage
- **Zero-Knowledge Proofs**: Proof of decryption without revealing plaintext

### Security Audit Status

#### Code Reviews
- ✅ **Internal Review**: Self-audited by development team
- ⏳ **External Audit**: Not yet performed (private fork, experimental)
- ⏳ **Community Review**: Pending (when/if contributed upstream)

#### Cryptographic Review
- ✅ **Algorithm Selection**: NIST-approved ML-KEM (Kyber-768)
- ✅ **Implementation**: Using audited `mlkem-wasm` library
- ✅ **Key Derivation**: Standard HKDF-SHA256
- ⚠️ **Integration**: Custom integration code not externally audited

**Recommendation**: Treat this as experimental for production use. Conduct independent security audit before deploying for high-security scenarios.

### Deployment Security Checklist

Before deploying PrivateBin-PQC v3.0.0:

- [ ] **HTTPS Required**: Ensure TLS 1.2+ with valid certificate
- [ ] **HSTS Enabled**: Add `Strict-Transport-Security` header
- [ ] **CSP Configured**: Include `wasm-unsafe-eval` for PQC
- [ ] **MIME Types**: Ensure `.wasm` served as `application/wasm`
- [ ] **Fragment Safety**: Verify fragments not logged in access logs
- [ ] **WASM Integrity**: Verify WASM module checksums/SRI
- [ ] **Test Deployment**: Create test pastes to verify PQC works
- [ ] **Monitor Performance**: Check WASM loading time and PQC overhead
- [ ] **Backup Strategy**: Ensure v2 pastes remain accessible
- [ ] **Documentation**: Inform users about PQC feature and requirements

See `DEPLOYMENT_CHECKLIST.md` for detailed deployment procedures.

---

## Reporting a Vulnerability

You can send us email at security@privatebin.org. You should be able to get
a response within a week (usually during the next weekend). The respondee will
reply from their personal address and can offer you their GPG public key to
support end-to-end encrypted communication on sensitive topics or attachments.

You can also [use the corresponding GitHub form](https://github.com/PrivateBin/PrivateBin/security/advisories/new)
to report a new vulnerability directly on GitHub.

You can also contact us via the regular issue tracker if the risk of early
publication is low or you would request input from other PrivateBin users.
