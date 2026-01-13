# Pre-Open Share via QR - Deployment Checklist

This document outlines the final deployment steps for the QR Share feature.

## ✅ Automated Checks (Completed)

- [x] **File Permissions**: share.html has 644 permissions (matches index.php)
- [x] **Code Security Audit**: Zero network requests from share.html verified
- [x] **Fragment Preservation**: All code uses window.location.hash (never .search)
- [x] **No External Dependencies**: share.html is fully self-contained
- [x] **Configuration**: qrshare defaults to false (opt-in feature)
- [x] **Documentation**: README updated with feature description

## 🔧 Manual Deployment Steps

### 1. MIME-Type Verification

Ensure your web server is serving share.html with the correct MIME type:

**Expected**: `Content-Type: text/html`

**How to verify**:
```bash
curl -I https://your-domain.com/share.html | grep Content-Type
```

**Expected output**:
```
Content-Type: text/html; charset=UTF-8
```

**Standard configurations** (should work automatically):
- Apache: `.html` files served as `text/html` by default
- Nginx: `.html` files served as `text/html` by default
- Most hosting providers: Configured correctly by default

**If misconfigured**:

Apache (.htaccess):
```apache
<Files "share.html">
    ForceType text/html
</Files>
```

Nginx (server block):
```nginx
location = /share.html {
    default_type text/html;
}
```

---

### 2. Enable the Feature

Edit your `cfg/conf.php`:

```php
[main]
qrcode = true      ; Enable QR code feature
qrshare = true     ; Enable pre-open share gateway
```

**Important**: Both settings must be `true` for the feature to work.

---

### 3. The "Quantum-Link" Test 🔬

This is the critical end-to-end test that verifies the feature works correctly with PQC v3 pastes.

**Why this matters**: Post-Quantum Cryptography (v3) pastes use longer hybrid keys. This test ensures these longer fragments survive the entire share flow.

**Test procedure**:

1. **Create a v3 PQC paste**:
   - Create a new paste on your instance
   - Ensure it's using v3 encryption (check the URL for a longer fragment)
   - Example URL: `https://your-domain.com/?pasteid=abc123#very_long_v3_hybrid_key_here`

2. **Generate QR code**:
   - Click the QR code button
   - Verify the QR shows a URL like: `https://your-domain.com/share.html#base64url_encoded_string`
   - Take note of the full paste URL before scanning

3. **Scan and share**:
   - Scan the QR code with your mobile device
   - Should land on share.html showing 5 buttons
   - Click "Share via Signal" or "Share via WhatsApp"
   - Send the message to yourself or a test contact

4. **Verify round-trip**:
   - Open the message in Signal/WhatsApp
   - Click the paste URL in the message
   - Verify the paste decrypts successfully
   - **Critical**: Check that the URL fragment matches the original (full v3 key intact)

**Expected result**: ✅ Paste opens and decrypts without errors

**If test fails**:
- Check browser console for JavaScript errors
- Verify base64url encoding/decoding is working
- Ensure messenger app didn't truncate the URL
- Check server logs for any errors

---

### 4. Additional Testing

**Test burn-after-read pastes**:
```
1. Create a burn-after-read paste
2. Generate QR code with qrshare enabled
3. Scan QR → should show share.html
4. Click "Open Paste"
5. Verify confirmation screen appears before paste opens
6. Confirm and verify paste decrypts
```

**Test subdirectory installations**:
```
If PrivateBin is in a subdirectory (e.g., /bin/):
1. Create paste
2. Generate QR code
3. Verify QR URL is: https://domain.com/bin/share.html#...
   (NOT https://domain.com/share.html#...)
4. Test that scan → share → open flow works correctly
```

**Test feature toggle**:
```
1. Set qrshare = false
2. Create paste and generate QR
3. Verify QR encodes direct paste URL (not share.html)
4. Verify scanning QR opens paste immediately (original behavior)
```

---

### 5. Browser Compatibility Check

Test the share page in multiple browsers:

- [ ] Desktop Chrome/Chromium (latest)
- [ ] Desktop Firefox (latest)
- [ ] Mobile Android Chrome
- [ ] Mobile iOS Safari

**For each browser, verify**:
- [ ] share.html page loads correctly
- [ ] All 5 buttons are visible and enabled
- [ ] "Copy Link" button works
- [ ] Messenger buttons open deep links or show appropriate errors
- [ ] "Open Paste" navigates correctly with fragment preserved

---

### 6. Network Security Audit

**Using Browser DevTools**:

1. Open DevTools → Network tab
2. Enable "Preserve log"
3. Navigate to a share.html URL (with encoded paste in fragment)
4. Click all buttons: Copy, WhatsApp, Signal, Viber, Open
5. Review Network tab

**Expected result**:
- ✅ **Exactly 1 network request**: Initial share.html page load
- ✅ **Zero additional requests**: No requests from button clicks
- ✅ **No fragment in logs**: Server logs should NOT show the base64url-encoded URL

**If you see additional requests**: ❌ STOP and investigate - this is a security issue

---

### 7. Server Log Inspection

Check your web server access logs after completing the test:

**What you should see**:
```
[timestamp] GET /share.html HTTP/1.1 200 - "Mozilla/5.0..."
[timestamp] GET /?pasteid=abc123 HTTP/1.1 200 - "Mozilla/5.0..."
```

**What you should NOT see**:
- ❌ The base64url-encoded URL in any log entry
- ❌ The paste encryption key in any log entry
- ❌ The full paste URL with fragment in any log entry

**Why**: URL fragments (everything after #) are never sent in HTTP requests by browsers. The server only sees requests for share.html and the paste ID, never the encryption keys.

---

## 🎯 Success Criteria

The feature is production-ready when ALL of the following are true:

- [x] Code deployed and file permissions correct
- [ ] MIME type verified as text/html
- [ ] Configuration enabled (qrcode + qrshare = true)
- [ ] Quantum-Link test passed (v3 paste round-trip successful)
- [ ] Browser compatibility confirmed (4+ browsers tested)
- [ ] Network security audit passed (zero requests from share.html)
- [ ] Server logs clean (no encryption keys visible)
- [ ] Feature toggle tested (disabled mode works correctly)

---

## 🚨 Rollback Procedure

If any issues arise:

**Quick disable** (recommended first step):
```php
# In cfg/conf.php
qrshare = false  ; Disables feature immediately
```

**Remove share.html** (if needed):
```bash
mv share.html share.html.disabled
```

**Full rollback** (last resort):
```bash
git revert <commit-hash>  # Revert all QR share changes
```

---

## 📊 Monitoring

After deployment, monitor for:

1. **404 errors for share.html**: Would indicate file not accessible
2. **JavaScript errors**: Check browser console and error logs
3. **User reports**: Issues with QR codes not working
4. **Performance**: share.html load time (should be <100ms)

---

## 🎉 Deployment Complete

Once all checklist items are verified, the feature is production-ready!

**Post-deployment**:
- Consider adding this feature to your instance's about/help page
- Update any user documentation or tutorials
- Monitor initial usage for any issues

---

## 📝 Technical Reference

**Files deployed**:
- `/share.html` (12.5KB, standalone)
- `/js/privatebin.js` (modified, +80 lines)
- `/lib/Configuration.php` (modified, +1 line)
- `/lib/Controller.php` (modified, +1 line)
- `/tpl/bootstrap5.php` (modified, +1 attribute)
- `/tpl/bootstrap.php` (modified, +1 attribute)
- `/cfg/conf.sample.php` (modified, +4 lines documentation)

**Zero-knowledge guarantee**:
- Encryption keys never leave fragments
- Fragments never sent in HTTP requests
- All encoding/decoding client-side JavaScript
- No server-side processing of decrypted data
- No external dependencies or tracking

**Privacy properties**:
- `<meta name="referrer" content="no-referrer">` prevents referrer leakage
- `<meta name="robots" content="noindex, nofollow">` prevents search indexing
- No cookies, no session tracking, no analytics
- Deep links contain only the full paste URL (which user controls)

---

For questions or issues, refer to the planning.md document for detailed implementation specifications.
