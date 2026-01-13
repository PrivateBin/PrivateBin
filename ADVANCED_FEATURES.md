# PrivateBin-PQC Advanced Features Guide

This document covers the advanced UX and security features implemented in PrivateBin-PQC v3.0+.

---

## UX Enhancements

### Overview

Three user experience enhancements have been implemented to improve security awareness and user guidance:

1. **V3 Sharing Warning** - One-time tooltip educating users about secure URL sharing
2. **Quantum Badge** - Visual indicator (⚛️ PQC) for quantum-protected pastes
3. **Browser Fallback Notice** - Informative message when PQC is unavailable

---

### 1. V3 Sharing Warning

#### Purpose
Educate users that v3 paste URLs contain quantum-resistant encryption keys and should be shared securely.

#### Behavior
- Shown automatically after creating a v3 paste
- Displayed once per browser (using localStorage)
- Auto-dismisses after 10 seconds
- Can be manually dismissed by user

#### Message Content
```
Quantum-Protected Paste Created: This link contains a post-quantum encryption key.
Share it only via ephemeral channels (Signal, in-person, verbal).
Avoid email, ticketing systems, or chat logs with long retention.
```

#### Configuration
No configuration needed - works automatically when v3 pastes are created.

#### Disabling (if needed)
To disable this warning, edit `js/privatebin.js` and comment out the call in `showCreatedPaste()`:

```javascript
// if (data.pasteVersion === 3) {
//     UxEnhancements.showQuantumBadge();
//     UxEnhancements.showV3SharingWarning(); // <-- Comment this line
// }
```

---

### 2. Quantum Badge Indicator

#### Purpose
Provide visual confirmation that a paste is protected by post-quantum cryptography.

#### Appearance
- Badge displays: **⚛️ PQC**
- Styled with light blue background (#5bc0de)
- Tooltip shows "Post-Quantum Protected" on hover
- Appears next to the paste URL

#### Behavior
- Only shown for v3 pastes
- Created dynamically after paste creation
- Prevents duplicate badges

#### Customization
To customize badge appearance, edit `js/privatebin.js` in the `UxEnhancements.showQuantumBadge()` function:

```javascript
const $badge = $('<span>')
    .addClass('pqc-badge')
    .attr('title', 'Post-Quantum Protected')
    .css({
        'margin-left': '8px',
        'padding': '2px 8px',
        'background-color': '#5bc0de', // <-- Change color here
        'color': '#fff',
        'border-radius': '10px',
        'font-size': '12px',
        'font-weight': 'bold',
        'display': 'inline-block',
        'vertical-align': 'middle'
    })
    .text('\u269b\ufe0f PQC'); // <-- Change text here
```

---

### 3. Browser Fallback Notice

#### Purpose
Inform users when their browser doesn't support post-quantum cryptography, explaining why v2 encryption is being used instead.

#### Behavior
- Shown during page load if PQC initialization fails
- Displayed once per browser session (using sessionStorage)
- Lists specific missing features (e.g., "WebAssembly", "HKDF")
- Auto-dismisses after 8 seconds
- Provides browser upgrade recommendations

#### Message Example
```
Your browser does not support post-quantum cryptography.
Missing features: webAssembly, hkdf.
This paste will use classical encryption (v2 format).
For quantum protection, use Chrome 90+, Firefox 88+, or Safari 15+.
```

#### Configuration
No configuration needed - automatically triggers when PQC support is unavailable.

---

## Advanced Security Features

### Overview

Three advanced security features protect against supply chain attacks and enable air-gapped deployments:

1. **Subresource Integrity (SRI)** - Runtime WASM integrity verification
2. **Self-Hosted WASM** - Load WASM from custom path instead of npm
3. **WASM Hash Pinning** - Cryptographic verification of WASM module

---

### 1. Subresource Integrity (SRI) for WASM

#### Purpose
Verify the integrity of the ML-KEM WASM module at runtime to detect tampering or supply chain attacks.

#### How It Works
1. WASM module bytes are hashed using SHA-384
2. Computed hash is compared against expected hash
3. Initialization fails if hashes don't match
4. Falls back to v2 encryption (safe fallback)

#### Configuration

**Step 1: Generate WASM Hash**

```bash
# Method 1: Using OpenSSL
openssl dgst -sha384 -binary js/node_modules/mlkem-wasm/mlkem768.wasm | openssl base64 -A
echo ""

# Method 2: Using Node.js
node -e "
const fs = require('fs');
const crypto = require('crypto');
const wasm = fs.readFileSync('js/node_modules/mlkem-wasm/mlkem768.wasm');
const hash = crypto.createHash('sha384').update(wasm).digest('base64');
console.log('sha384-' + hash);
"

# Example output:
# sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8wC
```

**Step 2: Update Hash in pqccrypto.js**

Edit `js/pqccrypto.js` and replace the placeholder:

```javascript
// Before:
const EXPECTED_WASM_HASH = 'sha384-PLACEHOLDER_HASH_WILL_BE_UPDATED';

// After:
const EXPECTED_WASM_HASH = 'sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8wC';
```

**Step 3: Test Verification**

1. Reload your PrivateBin instance
2. Open browser console (F12)
3. Look for: `[PQC] WASM integrity verified`
4. If hash mismatches, you'll see: `WASM integrity check failed`

#### Troubleshooting

**Problem:** "Cannot verify WASM integrity: bytes not accessible"

**Solution:** This is expected for npm-installed WASM modules that don't expose their binary. The system will log a warning but continue. For full integrity verification, use self-hosted WASM (see below).

**Problem:** "WASM integrity check failed: expected X, got Y"

**Solution:** The WASM file has been modified. Possible causes:
- Incorrect hash configured
- WASM module updated (regenerate hash)
- Supply chain attack detected (investigate immediately)

---

### 2. Self-Hosted WASM Support

#### Purpose
Load WASM module from your own server instead of npm packages, providing:
- Full control over WASM binary
- Air-gapped deployment support
- Enhanced supply chain security
- Guaranteed integrity verification

#### Configuration

**Step 1: Extract WASM File**

```bash
# Copy WASM file to your web root
cp js/node_modules/mlkem-wasm/mlkem768.wasm js/mlkem768.wasm

# Or for production deployment:
cp js/node_modules/mlkem-wasm/mlkem768.wasm /var/www/privatebin/js/mlkem768.wasm
```

**Step 2: Enable Self-Hosted Mode**

Edit `js/pqccrypto.js`:

```javascript
// Before:
const SELF_HOSTED_WASM_ENABLED = false;
const SELF_HOSTED_WASM_PATH = '/js/mlkem768.wasm';

// After:
const SELF_HOSTED_WASM_ENABLED = true;
const SELF_HOSTED_WASM_PATH = '/js/mlkem768.wasm'; // Adjust path as needed
```

**Step 3: Configure Web Server**

Ensure your web server serves WASM with correct MIME type (already configured if you followed DEPLOYMENT.md):

**Nginx:**
```nginx
location ~ \.wasm$ {
    types { application/wasm wasm; }
    add_header Content-Type application/wasm;
}
```

**Apache:**
```apache
<IfModule mod_mime.c>
    AddType application/wasm .wasm
</IfModule>
```

**Step 4: Test**

1. Reload PrivateBin
2. Check browser console for: `[PQC] Using self-hosted WASM from: /js/mlkem768.wasm`
3. Verify initialization succeeds

#### Benefits

✅ **Air-Gapped Deployments** - No internet connection needed
✅ **Full Control** - You control the WASM binary
✅ **Integrity Verification** - Can verify hash at runtime
✅ **Supply Chain Security** - Eliminates npm CDN dependency

#### Trade-offs

⚠️ **Manual Updates** - Must manually update WASM when mlkem-wasm updates
⚠️ **Increased Maintenance** - You're responsible for WASM binary updates

---

### 3. WASM Hash Pinning

#### Purpose
Cryptographically verify WASM module integrity using SHA-384 hash pinning.

#### How It Works

1. **Hash Generation:** WASM binary is hashed with SHA-384
2. **Hash Pinning:** Expected hash is hard-coded in source
3. **Runtime Verification:** On initialization, WASM is hashed and compared
4. **Fail-Closed:** If mismatch, initialization fails (safe default)

#### Configuration

See "Subresource Integrity (SRI) for WASM" section above - hash pinning is part of SRI implementation.

#### Security Considerations

**When to Pin Hash:**
- Production deployments
- Security-sensitive environments
- After verifying WASM source

**When NOT to Pin Hash:**
- Development environments
- When testing new WASM versions
- If you want automatic npm updates

**Best Practice:**
```javascript
// Development
const EXPECTED_WASM_HASH = 'sha384-PLACEHOLDER_HASH_WILL_BE_UPDATED'; // Disabled

// Production
const EXPECTED_WASM_HASH = 'sha384-<actual-hash-here>'; // Enabled
```

---

## Testing

### UX Features Testing

**Test V3 Sharing Warning:**
1. Create a paste (ensure PQC is working)
2. Verify warning appears
3. Refresh page, create another paste
4. Warning should NOT appear (shown once)
5. Clear localStorage and test again

**Test Quantum Badge:**
1. Create a v3 paste
2. Verify badge (⚛️ PQC) appears next to URL
3. Hover to see tooltip
4. Create v2 paste (disable PQC temporarily)
5. Badge should NOT appear

**Test Browser Fallback Notice:**
1. Open browser without WASM support (e.g., IE)
2. Verify fallback notice appears
3. Lists missing features
4. Refresh page - notice should NOT appear again (sessionStorage)

### Security Features Testing

**Test WASM Integrity Verification:**
1. Configure valid hash
2. Reload page - should initialize successfully
3. Configure invalid hash
4. Reload page - should fail with clear error message

**Test Self-Hosted WASM:**
1. Enable self-hosted mode
2. Verify WASM loads from custom path (check console)
3. Verify integrity verification still works
4. Test paste creation and decryption

**Test Hash Pinning:**
1. Modify WASM file (change one byte)
2. Reload page
3. Should see "WASM integrity check failed" error
4. Restore original WASM
5. Should initialize successfully

---

## Deployment Checklist

Before deploying to production:

- [ ] Configure WASM hash (if using SRI)
- [ ] Test all UX features in target browsers
- [ ] Verify WASM MIME type configured correctly
- [ ] Test self-hosted WASM (if enabled)
- [ ] Verify hash pinning works (if configured)
- [ ] Test fallback to v2 when PQC unavailable
- [ ] Review browser console for warnings/errors
- [ ] Test localStorage and sessionStorage functionality
- [ ] Verify quantum badge appears on v3 pastes
- [ ] Confirm sharing warning displays correctly

---

## Troubleshooting

### UX Issues

**Warning doesn't show:**
- Check browser console for errors
- Verify localStorage is enabled
- Clear localStorage: `localStorage.clear()`

**Badge doesn't appear:**
- Check if paste is v3 (look for `data.pasteVersion`)
- Verify `#pastesuccess` element exists
- Check browser console for errors

**Fallback notice shows incorrectly:**
- Check PQC initialization status
- Verify browser support detection works
- Clear sessionStorage: `sessionStorage.clear()`

### Security Issues

**Hash verification fails:**
- Regenerate hash from actual WASM file
- Ensure hash includes `sha384-` prefix
- Check WASM file hasn't been modified

**Self-hosted WASM fails to load:**
- Verify WASM file exists at specified path
- Check web server MIME type configuration
- Inspect network tab for 404 errors
- Verify CORS headers (if cross-origin)

**Integrity check always skipped:**
- Check if hash is still placeholder value
- Verify `EXPECTED_WASM_HASH` constant is set
- Look for warning in console

---

## Security Best Practices

1. **Always verify WASM hash in production**
2. **Use self-hosted WASM for maximum control**
3. **Monitor console logs for integrity failures**
4. **Update hashes when upgrading WASM**
5. **Test fallback behavior regularly**
6. **Review security logs for tampering attempts**
7. **Keep WASM module updated (security patches)**
8. **Document your configuration for team**

---

## Configuration Summary

### UX Features (Automatic)
- No configuration required
- Works out-of-the-box
- Can be customized in `js/privatebin.js`

### Security Features (Manual Configuration)

**SRI / Hash Pinning:**
```javascript
// In js/pqccrypto.js
const EXPECTED_WASM_HASH = 'sha384-<your-hash-here>';
```

**Self-Hosted WASM:**
```javascript
// In js/pqccrypto.js
const SELF_HOSTED_WASM_ENABLED = true;
const SELF_HOSTED_WASM_PATH = '/js/mlkem768.wasm';
```

---

## Support

For issues or questions:
- Review [SECURITY.md](SECURITY.md) for threat model
- Check [DEPLOYMENT.md](DEPLOYMENT.md) for deployment guide
- See [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) for technical details
- File issues on GitHub repository

---

**Last Updated:** 2026-01-13
**PrivateBin-PQC Version:** 3.0.0+
