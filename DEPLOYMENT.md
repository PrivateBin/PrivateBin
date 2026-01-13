# PrivateBin-PQC Deployment Guide

## Overview

This document provides deployment guidelines specific to the post-quantum cryptography (PQC) implementation in PrivateBin v3.0+. For general PrivateBin installation, see the [standard installation guide](https://github.com/PrivateBin/PrivateBin/blob/master/doc/Installation.md).

## Prerequisites

### Server Requirements

- PHP 7.4+ (same as standard PrivateBin)
- Web server (Nginx, Apache, or similar)
- HTTPS enabled (required for Web Crypto API)

### Client Requirements

For v3 (PQC) paste support:
- Modern browser (Chrome 90+, Firefox 88+, Safari 15+, Edge 90+)
- WebAssembly support
- Web Crypto API with HKDF support

Older browsers automatically fall back to v2 (classical) encryption.

---

## 🚀 Day Zero: Production Readiness Checklist

**Before you go live**, verify these "silent" failure points to ensure v3 (PQC) pastes work for all users. Each check takes < 30 seconds.

### ☑️ 1. The CSP Check

**Issue:** Without `wasm-unsafe-eval` in your Content-Security-Policy, browsers will block ML-KEM WASM execution, causing silent fallback to v2.

**Verification (30 seconds):**
```bash
# Check your live site's CSP header
curl -I https://privatebin.example.com | grep -i content-security-policy

# Expected output should include:
# Content-Security-Policy: ... script-src 'self' 'wasm-unsafe-eval'; ...
```

**Fix if missing:**
```nginx
# Nginx: Add to your server block
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'wasm-unsafe-eval'; style-src 'self' 'unsafe-inline'; object-src 'none'" always;
```

```apache
# Apache: Add to .htaccess
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'wasm-unsafe-eval'; style-src 'self' 'unsafe-inline'; object-src 'none'"
```

### ☑️ 2. The MIME Verification

**Issue:** If WASM files aren't served as `application/wasm`, browsers refuse to compile them.

**Verification (10 seconds):**
```bash
curl -I https://privatebin.example.com/js/node_modules/mlkem-wasm/mlkem768.wasm | grep -i content-type

# Expected output:
# content-type: application/wasm

# ❌ FAIL if you see:
# content-type: application/octet-stream
# content-type: text/plain
```

**Fix if wrong:** See "Critical Configuration: WASM MIME Type" section below.

### ☑️ 3. The "Quantum Tax" Audit

**Issue:** v3 pastes add ~4.3KB of KEM metadata. Tight size limits may reject large pastes.

**Verification (20 seconds):**
```bash
# Check PHP post size limit
php -i | grep post_max_size
# Should be: post_max_size => 10M (or higher)

# Check your PrivateBin config
grep sizelimit /var/www/privatebin/cfg/conf.php
# Should show at least 2MB buffer above expected paste size
```

**Impact Example:**
- Default limit: 2MB
- User pastes: 1.997MB of text
- KEM overhead: +4.3KB
- Total: 2.001MB → **REJECTED**

**Fix:**
```php
// In cfg/conf.php, set generous buffer
sizelimit = 10485760  // 10MB (gives 8MB usable after KEM overhead)
```

### ☑️ 4. Log Scrubbing Verification

**Issue:** URL fragments (#key...) should never be logged, but misconfigured proxies or analytics might capture them.

**Verification (15 seconds):**
```bash
# Test that your web server doesn't log the fragment
tail -f /var/log/nginx/access.log &
# In browser, navigate to: https://privatebin.example.com/?pasteid#ThisIsATestKey
# Check log output - should NOT contain "ThisIsATestKey"

# Expected (good):
# 192.168.1.1 - - [13/Jan/2026:12:00:00] "GET /?pasteid HTTP/2.0" 200 1234

# ❌ FAIL if you see:
# 192.168.1.1 - - [13/Jan/2026:12:00:00] "GET /?pasteid#ThisIsATestKey HTTP/2.0" 200 1234
```

**Note:** URL fragments are not sent to servers by browsers, but custom clients or JavaScript analytics might log them. Verify your analytics (if any) excludes fragments.

---

**✅ All 4 checks passed?** You're ready to deploy with confidence. PQC will work for all supported browsers.

**❌ Any check failed?** Fix before going live, or users will silently fall back to v2 encryption without knowing.

---

## Critical Configuration: WASM MIME Type

**REQUIRED:** Configure your web server to serve WebAssembly files with the correct MIME type.

### Why This Matters

The ML-KEM (Kyber-768) implementation uses WebAssembly. Browsers require WASM files to be served with `application/wasm` MIME type for security reasons. **If misconfigured, PQC will fail to initialize and all clients will fall back to v2 encryption.**

### Nginx Configuration

Add to your `nginx.conf` or site configuration:

```nginx
server {
    server_name privatebin.example.com;
    root /var/www/privatebin;

    # Critical: WASM MIME type for PQC support
    location ~ \.wasm$ {
        types { application/wasm wasm; }
        add_header Content-Type application/wasm;
        # Optional: Enable compression
        gzip on;
        gzip_types application/wasm;
    }

    # Standard PrivateBin configuration
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; object-src 'none'" always;

    # Optional: Exclude URL fragments from logs (defense-in-depth)
    # Note: Fragments are not sent to server by browsers anyway
    log_format no_fragment '$remote_addr - $remote_user [$time_local] '
                           '"$request_method $uri $server_protocol" '
                           '$status $body_bytes_sent';
    access_log /var/log/nginx/privatebin-access.log no_fragment;

    listen 443 ssl http2;
    ssl_certificate /etc/letsencrypt/live/privatebin.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/privatebin.example.com/privkey.pem;
}
```

### Apache Configuration

Add to your `.htaccess` or Apache configuration:

```apache
# Critical: WASM MIME type for PQC support
<IfModule mod_mime.c>
    AddType application/wasm .wasm
</IfModule>

# Optional: Enable compression for WASM files
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE application/wasm
</IfModule>

# Standard PrivateBin rewrite rules
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^.*$ index.php [QSA,L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; object-src 'none'"
</IfModule>

# Optional: Exclude URL fragments from logs (defense-in-depth)
# Note: Fragments are not logged by default (not sent to server)
# Ensure custom logging doesn't capture them
LogFormat "%h %l %u %t \"%r\" %>s %b" common
CustomLog /var/log/apache2/privatebin-access.log common
```

### Caddy Configuration

Add to your `Caddyfile`:

```caddy
privatebin.example.com {
    root * /var/www/privatebin
    php_fastcgi unix//var/run/php/php8.1-fpm.sock

    # Critical: WASM MIME type for PQC support
    @wasm path *.wasm
    header @wasm Content-Type application/wasm

    # Security headers
    header X-Content-Type-Options "nosniff"
    header X-Frame-Options "DENY"
    header Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; object-src 'none'"

    # Logging (fragments not sent to server)
    log {
        output file /var/log/caddy/privatebin-access.log
    }

    file_server
}
```

## Verification

### 1. Check WASM MIME Type

Test that WASM files are served correctly:

```bash
curl -I https://privatebin.example.com/js/node_modules/mlkem-wasm/mlkem768.wasm

# Expected output should include:
# HTTP/2 200
# content-type: application/wasm
```

**If you see `content-type: application/octet-stream` or anything else, PQC will not work.**

### 1b. Verify WASM Compression (Recommended)

Check that WASM files are being compressed for faster loading:

```bash
# Test with compression headers
curl -H "Accept-Encoding: gzip, deflate, br" -I https://privatebin.example.com/js/node_modules/mlkem-wasm/mlkem768.wasm

# Look for compression headers:
# content-encoding: gzip (or br for Brotli)
```

**Without compression:** WASM download is ~54KB
**With Gzip:** WASM download is ~20-25KB (2-3x faster on slow connections)

If compression is missing:
- **Nginx:** Ensure `gzip_types application/wasm;` is set
- **Apache:** Ensure `mod_deflate` is enabled and configured for `application/wasm`
- **Impact:** Slower initial load, especially on mobile/3G connections

### 2. Check Browser Console

After deploying, open your PrivateBin instance in a browser and check the developer console (F12):

**Success:**
```
[PQC] Initializing post-quantum cryptography...
[PQC] Checking browser capabilities...
[PQC] Browser support confirmed
[PQC] Loading ML-KEM WASM module (Kyber-768)...
[PQC] Initialized successfully in 234ms (v3 encryption available)
```

**Failure (MIME type issue):**
```
[PQC] Initializing post-quantum cryptography...
[PQC] Checking browser capabilities...
[PQC] Initialization failed, falling back to v2: Failed to instantiate WASM module
```

### 3. Test Paste Creation

Create a test paste and verify it uses v3 format:

1. Create a paste with any content
2. Open browser developer tools → Network tab
3. Find the POST request to create the paste
4. Check the request payload - it should contain:
   - `"v": 3`
   - `"kem": { "algo": "kyber768", ... }`

If you see `"v": 2`, PQC is not working (check MIME type configuration).

## Installation Steps

### 1. Install Dependencies

```bash
cd /var/www/privatebin/js
npm install
```

This will install `mlkem-wasm` and other dependencies from `package.json`.

### 2. Configure Web Server

Apply one of the MIME type configurations above based on your web server.

### 3. Restart Web Server

```bash
# Nginx
sudo systemctl restart nginx

# Apache
sudo systemctl restart apache2

# Caddy
sudo systemctl restart caddy
```

### 4. Verify Deployment

Follow the verification steps above to confirm PQC is working.

## Performance Considerations

### WASM Initialization Time

- **First load:** 100-500ms (WASM module download + initialization)
- **Cached loads:** < 50ms (browser caches WASM module)
- **Impact:** Slight delay on first page load, negligible thereafter

### Paste Size Impact

PQC adds ~3.5KB to each paste (KEM ciphertext + private key):

- Kyber-768 ciphertext: ~1088 bytes (base64: ~1450 chars)
- Kyber-768 private key: ~2400 bytes (base64: ~3200 chars)

For small pastes (< 1KB), this is significant overhead. For larger pastes (> 10KB), the impact is minimal.

### Encryption/Decryption Performance

Typical timings on modern hardware:

- **Keygen:** 1-20ms
- **Encapsulation:** 1-10ms
- **Decapsulation:** 1-10ms
- **AES-GCM:** Scales with message size (~1ms per 100KB)

**Total overhead for PQC:** 3-40ms per paste (negligible for user experience)

## Security Best Practices

### 1. HTTPS Required

PQC **requires HTTPS** (Web Crypto API restriction). HTTP will not work.

### 2. Content Security Policy

Use restrictive CSP to prevent JavaScript injection:

```
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; object-src 'none'
```

### 3. Subresource Integrity (Optional)

For production, consider adding SRI hashes for WASM modules:

```html
<script src="js/privatebin.js"
        integrity="sha384-..."
        crossorigin="anonymous"></script>
```

### 4. WASM Supply Chain Security

The `mlkem-wasm` package is installed from npm. For production:

1. **Pin versions** in `package-lock.json` (already done)
2. **Run npm audit** regularly: `npm audit`
3. **Consider self-hosting WASM:** Copy WASM file to your server instead of using npm CDN

### 5. URL Retention Warnings

Educate users that URLs contain decryption keys:

- ❌ Avoid email, ticketing systems, chat logs
- ✅ Use ephemeral messaging, in-person sharing, or password-protected pastes

See [SECURITY.md](SECURITY.md) for complete threat model.

## Monitoring

### Key Metrics to Monitor

1. **PQC initialization success rate**
   - Monitor browser console logs
   - Track `[PQC] Initialized successfully` vs `[PQC] Initialization failed`

2. **Paste version distribution**
   - Monitor server logs for v2 vs v3 paste creation
   - Track adoption of PQC-enabled browsers

3. **Performance metrics**
   - Monitor WASM load time (should be < 500ms)
   - Track paste creation time (should be < 2s total)

4. **Error rates**
   - Monitor `DecryptionError` occurrences
   - Track v2/v3 compatibility issues

### Example Monitoring Setup

Add to your application monitoring:

```javascript
// Track PQC initialization
window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (window.pqcInitialized) {
            // Send success metric to monitoring
            console.log('[Monitoring] PQC initialized successfully');
        } else {
            // Send failure metric to monitoring
            console.log('[Monitoring] PQC initialization failed, v2 fallback active');
        }
    }, 2000);
});
```

## Troubleshooting

### Problem: PQC Not Initializing

**Symptoms:**
- Browser console shows `[PQC] Initialization failed`
- All pastes use v2 format

**Solutions:**
1. Check WASM MIME type: `curl -I https://your-instance/js/node_modules/mlkem-wasm/mlkem768.wasm`
2. Verify HTTPS is enabled (Web Crypto API requires it)
3. Check browser compatibility (Chrome 90+, Firefox 88+, Safari 15+)
4. Verify `npm install` completed successfully

### Problem: "Failed to fetch WASM module"

**Symptoms:**
- Browser console shows fetch errors
- Network tab shows 404 for WASM files

**Solutions:**
1. Verify `npm install` installed dependencies: `ls js/node_modules/mlkem-wasm/`
2. Check web server serves static files from `js/node_modules/`
3. Verify no CDN/proxy is blocking WASM files

### Problem: Slow Paste Creation

**Symptoms:**
- Paste creation takes > 5 seconds
- Browser becomes unresponsive

**Solutions:**
1. Check browser console for WASM initialization errors
2. Monitor network tab for slow WASM download
3. Consider enabling WASM compression (gzip) in web server config
4. For very large pastes (> 10MB), this is expected (AES-GCM scales with size)

### Problem: v2 Clients Can't Read v3 Pastes

**Symptoms:**
- Old browsers show "Cannot decrypt paste" errors
- Decryption fails on older clients

**Expected Behavior:**
- This is intentional - v3 pastes require PQC support
- Users on old browsers should upgrade or the paste creator should use compatibility mode
- Server should show helpful error message directing users to upgrade

**Mitigation:**
- Document browser requirements clearly
- Consider adding browser version detection with upgrade prompts
- For critical communications, use password protection + v2 compatibility mode

## Upgrading from PrivateBin 1.x

### Database Compatibility

PrivateBin-PQC v3.0+ is **fully compatible** with existing PrivateBin databases:

- **Existing v1/v2 pastes:** Continue to work unchanged
- **New pastes:** Use v3 format (PQC) on supported browsers
- **No migration needed:** Old and new formats coexist

### Upgrade Steps

1. **Backup your data:**
   ```bash
   # For filesystem storage
   cp -r /var/www/privatebin/data /backup/privatebin-data-$(date +%Y%m%d)

   # For database storage
   mysqldump -u privatebin -p privatebin > /backup/privatebin-$(date +%Y%m%d).sql
   ```

2. **Replace files:**
   ```bash
   cd /var/www/privatebin
   git pull  # or extract new version
   ```

3. **Install dependencies:**
   ```bash
   cd js
   npm install
   ```

4. **Update web server config** (add WASM MIME type - see above)

5. **Restart web server:**
   ```bash
   sudo systemctl restart nginx  # or apache2/caddy
   ```

6. **Verify:** Check browser console for `[PQC] Initialized successfully`

### Rollback

If you need to rollback:

1. Replace files with previous version
2. No database changes needed (v2 format still supported)
3. Existing v3 pastes will be unreadable until you upgrade again

## Advanced: Self-Hosting WASM Binary

For maximum supply chain security, self-host the WASM binary:

```bash
# Copy WASM file to your server directory
cp js/node_modules/mlkem-wasm/mlkem768.wasm js/

# Update import in js/pqccrypto.js to use local path
# (Modify import statement to reference ./mlkem768.wasm)
```

This eliminates dependency on npm CDN but requires manual updates when `mlkem-wasm` is updated.

## Support

For issues specific to PQC implementation:
- Review [SECURITY.md](SECURITY.md) for threat model and design details
- Check [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) for technical overview
- File issues on GitHub repository

For general PrivateBin support:
- See upstream PrivateBin documentation: https://github.com/PrivateBin/PrivateBin
