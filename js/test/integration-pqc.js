'use strict';
const common = require('../common');

describe('PQC Integration Tests', function () {
    // Increase timeout for WASM operations
    this.timeout(30000);

    describe('V3 Format Validation', function () {
        it('v3 paste has correct structure', async function () {
            const clean = jsdom();
            $.PrivateBin.Controller.initZ();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            const message = 'Test message for v3 format validation';
            const password = '';
            const result = await $.PrivateBin.CryptTool.cipherV3(message, password);

            // Validate structure
            assert.strictEqual(result.encrypted.v, 3, 'Version should be 3');
            assert.ok(result.encrypted.ct, 'Ciphertext should exist');
            assert.ok(result.encrypted.adata, 'Adata should exist');
            assert.ok(result.encrypted.kem, 'KEM object should exist');
            assert.strictEqual(result.encrypted.kem.algo, 'kyber768', 'Algorithm should be kyber768');
            assert.strictEqual(result.encrypted.kem.param, '768', 'Parameter should be 768');
            assert.ok(result.encrypted.kem.ciphertext, 'KEM ciphertext should exist');
            assert.ok(result.encrypted.kem.privkey, 'KEM private key should exist');
            assert.ok(result.urlKey, 'URL key should be returned');

            // Validate KEM field sizes (base64 encoded)
            const kemCtLen = result.encrypted.kem.ciphertext.length;
            const kemPkLen = result.encrypted.kem.privkey.length;
            assert.ok(kemCtLen > 1000 && kemCtLen < 2000,
                `KEM ciphertext length ${kemCtLen} should be ~1450 chars`);
            assert.ok(kemPkLen > 2500 && kemPkLen < 5000,
                `KEM private key length ${kemPkLen} should be ~3200 chars`);

            clean();
        });
    });

    describe('V3 Round-Trip Encryption/Decryption', function () {
        afterEach(async function () {
            // pause to let async functions conclude
            await new Promise(resolve => setTimeout(resolve, 1900));
        });

        it('can encrypt and decrypt with v3 format', async function () {
            const clean = jsdom();
            $.PrivateBin.Controller.initZ();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            const originalMessage = 'Secret message protected by post-quantum cryptography';
            const password = '';

            // Encrypt with v3
            const encrypted = await $.PrivateBin.CryptTool.cipherV3(originalMessage, password);

            // Decrypt with v3
            const decrypted = await $.PrivateBin.CryptTool.decipherV3(
                encrypted.encrypted,
                encrypted.urlKey
            );

            assert.strictEqual(decrypted, originalMessage, 'Decrypted message should match original');

            clean();
        });

        it('can encrypt and decrypt large messages (1MB)', async function () {
            const clean = jsdom();
            $.PrivateBin.Controller.initZ();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            // Create 1MB message
            const size = 1024 * 1024; // 1MB
            const originalMessage = 'A'.repeat(size);
            const password = '';

            // Measure performance
            const startTime = performance.now();
            const encrypted = await $.PrivateBin.CryptTool.cipherV3(originalMessage, password);
            const encryptTime = performance.now() - startTime;

            const decryptStart = performance.now();
            const decrypted = await $.PrivateBin.CryptTool.decipherV3(
                encrypted.encrypted,
                encrypted.urlKey
            );
            const decryptTime = performance.now() - decryptStart;

            assert.strictEqual(decrypted, originalMessage, 'Large message should decrypt correctly');

            // Performance check - should complete within reasonable time
            // Kyber operations typically take < 100ms, AES-GCM scales with size
            // For 1MB, total time should be < 5 seconds
            assert.ok(encryptTime < 5000,
                `Encryption of 1MB took ${encryptTime.toFixed(0)}ms (should be < 5000ms)`);
            assert.ok(decryptTime < 5000,
                `Decryption of 1MB took ${decryptTime.toFixed(0)}ms (should be < 5000ms)`);

            console.log(`[Performance] 1MB encrypt: ${encryptTime.toFixed(0)}ms, decrypt: ${decryptTime.toFixed(0)}ms`);

            clean();
        });

        it('fails decryption with wrong urlKey', async function () {
            const clean = jsdom();
            $.PrivateBin.Controller.initZ();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            const originalMessage = 'Secret message';
            const password = '';

            // Encrypt with v3
            const encrypted = await $.PrivateBin.CryptTool.cipherV3(originalMessage, password);

            // Try to decrypt with wrong urlKey
            const wrongKey = 'A'.repeat(32); // Wrong key

            let errorThrown = false;
            try {
                await $.PrivateBin.CryptTool.decipherV3(
                    encrypted.encrypted,
                    wrongKey
                );
            } catch (e) {
                errorThrown = true;
                assert.ok(e.name === 'DecryptionError' || e.message.includes('decrypt'),
                    'Should throw decryption error');
            }

            assert.ok(errorThrown, 'Decryption with wrong key should fail');

            clean();
        });
    });

    describe('V2 Backward Compatibility', function () {
        it('v2 client should gracefully handle v3 paste', async function () {
            const clean = jsdom();
            $.PrivateBin.Controller.initZ();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            const originalMessage = 'Message in v3 format';
            const password = '';

            // Create v3 paste
            const encrypted = await $.PrivateBin.CryptTool.cipherV3(originalMessage, password);

            // Simulate v2 client (no PQC support) trying to decrypt v3 paste
            // This should throw a clear error, not crash
            let errorThrown = false;
            try {
                // v2 decipher function should reject v3 format
                await $.PrivateBin.CryptTool.decipher(
                    encrypted.urlKey,
                    password,
                    [encrypted.encrypted.ct, encrypted.encrypted.adata]
                );
            } catch (e) {
                errorThrown = true;
                // Should get a meaningful error, not a crash
                assert.ok(e, 'Should throw an error for incompatible format');
            }

            // Note: This test validates that v2 clients fail gracefully
            // In production, version detection happens before decryption attempt
            assert.ok(errorThrown, 'V2 decipher should reject v3 format');

            clean();
        });
    });

    describe('Browser Support Detection', function () {
        it('detects required browser APIs', async function () {
            const clean = jsdom();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });

            const support = await $.PrivateBin.PqcCrypto.checkBrowserSupport();

            assert.ok(typeof support === 'object', 'Should return support object');
            assert.ok(typeof support.supported === 'boolean', 'Should have supported boolean');
            assert.ok(Array.isArray(support.missing), 'Should have missing array');

            // In our test environment with WebCrypto, support should be true
            // (assuming mlkem-wasm is available)
            console.log('[Browser Support]', support);

            clean();
        });
    });

    describe('PQC Performance Benchmarks', function () {
        it('measures keygen, encapsulate, decapsulate performance', async function () {
            const clean = jsdom();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            // Benchmark keygen
            const keygenStart = performance.now();
            const keypair = await $.PrivateBin.PqcCrypto.generateKeypair();
            const keygenTime = performance.now() - keygenStart;

            // Benchmark encapsulation
            const encapStart = performance.now();
            const encapResult = await $.PrivateBin.PqcCrypto.encapsulate(keypair.publicKey);
            const encapTime = performance.now() - encapStart;

            // Benchmark decapsulation
            const decapStart = performance.now();
            const sharedSecret = await $.PrivateBin.PqcCrypto.decapsulate(
                encapResult.ciphertext,
                keypair.privateKey
            );
            const decapTime = performance.now() - decapStart;

            // Log performance metrics
            console.log(`[PQC Performance Benchmarks]
  Keygen:        ${keygenTime.toFixed(2)}ms
  Encapsulate:   ${encapTime.toFixed(2)}ms
  Decapsulate:   ${decapTime.toFixed(2)}ms
  Total KEM:     ${(keygenTime + encapTime + decapTime).toFixed(2)}ms`);

            // Performance expectations (these are generous bounds)
            // Kyber-768 operations typically take 1-50ms each
            assert.ok(keygenTime < 1000, `Keygen took ${keygenTime.toFixed(0)}ms (should be < 1000ms)`);
            assert.ok(encapTime < 1000, `Encapsulate took ${encapTime.toFixed(0)}ms (should be < 1000ms)`);
            assert.ok(decapTime < 1000, `Decapsulate took ${decapTime.toFixed(0)}ms (should be < 1000ms)`);

            // Validate results
            assert.ok(keypair.publicKey instanceof Uint8Array, 'Public key should be Uint8Array');
            assert.ok(keypair.privateKey instanceof Uint8Array, 'Private key should be Uint8Array');
            assert.ok(sharedSecret instanceof Uint8Array, 'Shared secret should be Uint8Array');
            assert.strictEqual(sharedSecret.length, 32, 'Shared secret should be 32 bytes');

            clean();
        });
    });

    describe('HKDF Key Derivation', function () {
        it('derives consistent contentKey from same inputs', async function () {
            const clean = jsdom();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            // Create test inputs
            const sharedSecret = new Uint8Array(32);
            window.crypto.getRandomValues(sharedSecret);

            const urlKey = new Uint8Array(32);
            window.crypto.getRandomValues(urlKey);

            // Derive key twice with same inputs
            const key1 = await $.PrivateBin.PqcCrypto.deriveContentKey(sharedSecret, urlKey);
            const key2 = await $.PrivateBin.PqcCrypto.deriveContentKey(sharedSecret, urlKey);

            // Should be identical
            assert.strictEqual(key1.length, 32, 'Derived key should be 32 bytes');
            assert.strictEqual(key2.length, 32, 'Derived key should be 32 bytes');
            assert.deepStrictEqual(key1, key2, 'Same inputs should produce same key');

            clean();
        });

        it('derives different keys from different inputs', async function () {
            const clean = jsdom();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            // Create test inputs
            const sharedSecret = new Uint8Array(32);
            window.crypto.getRandomValues(sharedSecret);

            const urlKey1 = new Uint8Array(32);
            window.crypto.getRandomValues(urlKey1);

            const urlKey2 = new Uint8Array(32);
            window.crypto.getRandomValues(urlKey2);

            // Derive keys with different urlKeys
            const key1 = await $.PrivateBin.PqcCrypto.deriveContentKey(sharedSecret, urlKey1);
            const key2 = await $.PrivateBin.PqcCrypto.deriveContentKey(sharedSecret, urlKey2);

            // Should be different
            assert.notDeepStrictEqual(key1, key2, 'Different inputs should produce different keys');

            clean();
        });
    });

    describe('Negative Testing: Corrupted Data', function () {
        it('rejects v3 paste with modified KEM ciphertext', async function () {
            const clean = jsdom();
            $.PrivateBin.Controller.initZ();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            const originalMessage = 'Secret message';
            const password = '';

            // Encrypt with v3
            const encrypted = await $.PrivateBin.CryptTool.cipherV3(originalMessage, password);

            // Corrupt the KEM ciphertext (flip some bits)
            const corruptedCt = encrypted.encrypted.kem.ciphertext.split('').map((c, i) =>
                i === 10 ? (c === 'A' ? 'B' : 'A') : c
            ).join('');
            encrypted.encrypted.kem.ciphertext = corruptedCt;

            // Decryption should fail with DecryptionError
            let errorThrown = false;
            try {
                await $.PrivateBin.CryptTool.decipherV3(
                    encrypted.encrypted,
                    encrypted.urlKey
                );
            } catch (e) {
                errorThrown = true;
                assert.ok(e.name === 'DecryptionError' || e.message.includes('decrypt'),
                    'Should throw DecryptionError for corrupted ciphertext');
            }

            assert.ok(errorThrown, 'Corrupted KEM ciphertext should cause decryption failure');

            clean();
        });

        it('rejects v3 paste with missing kem object', async function () {
            const clean = jsdom();
            $.PrivateBin.Controller.initZ();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            // Create malformed v3 paste (missing kem object)
            const malformedPaste = {
                v: 3,
                ct: btoa('some ciphertext'),
                adata: [[btoa('iv'), btoa('salt'), 10000, 256, 128, 'aes', 'gcm', 'none']]
                // Missing: kem object
            };

            // Decryption should fail with DecryptionError
            let errorThrown = false;
            try {
                await $.PrivateBin.CryptTool.decipherV3(
                    malformedPaste,
                    'A'.repeat(32)
                );
            } catch (e) {
                errorThrown = true;
                assert.strictEqual(e.code, 'MISSING_KEM_DATA',
                    'Should throw MISSING_KEM_DATA error');
            }

            assert.ok(errorThrown, 'Missing kem object should cause decryption failure');

            clean();
        });

        it('rejects v3 paste with unsupported algorithm', async function () {
            const clean = jsdom();
            $.PrivateBin.Controller.initZ();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            const originalMessage = 'Secret message';
            const password = '';

            // Encrypt with v3
            const encrypted = await $.PrivateBin.CryptTool.cipherV3(originalMessage, password);

            // Change algorithm to unsupported one
            encrypted.encrypted.kem.algo = 'kyber1024'; // Not supported yet

            // Decryption should fail with DecryptionError
            let errorThrown = false;
            try {
                await $.PrivateBin.CryptTool.decipherV3(
                    encrypted.encrypted,
                    encrypted.urlKey
                );
            } catch (e) {
                errorThrown = true;
                assert.strictEqual(e.code, 'UNSUPPORTED_VERSION',
                    'Should throw UNSUPPORTED_VERSION error');
            }

            assert.ok(errorThrown, 'Unsupported algorithm should cause decryption failure');

            clean();
        });
    });

    describe('Large Paste Validation', function () {
        it('handles paste near size limit (2MB)', async function () {
            this.timeout(60000); // Increase timeout for large paste

            const clean = jsdom();
            $.PrivateBin.Controller.initZ();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            global.atob = common.atob;
            global.btoa = common.btoa;

            // Initialize PQC
            await $.PrivateBin.PqcCrypto.initialize();

            // Create paste just under 2MB (2MB - 4KB for KEM overhead)
            const size = (2 * 1024 * 1024) - (4 * 1024); // 2MB - 4KB
            const originalMessage = 'A'.repeat(size);
            const password = '';

            console.log(`[Large Paste Test] Testing ${(size / 1024 / 1024).toFixed(2)}MB paste`);

            // Encrypt with v3
            const startTime = performance.now();
            const encrypted = await $.PrivateBin.CryptTool.cipherV3(originalMessage, password);
            const encryptTime = performance.now() - startTime;

            console.log(`[Large Paste Test] Encryption: ${encryptTime.toFixed(0)}ms`);

            // Verify KEM overhead
            const kemOverhead = encrypted.encrypted.kem.ciphertext.length + encrypted.encrypted.kem.privkey.length;
            console.log(`[Large Paste Test] KEM overhead: ${(kemOverhead / 1024).toFixed(2)}KB`);

            // Decrypt
            const decryptStart = performance.now();
            const decrypted = await $.PrivateBin.CryptTool.decipherV3(
                encrypted.encrypted,
                encrypted.urlKey
            );
            const decryptTime = performance.now() - decryptStart;

            console.log(`[Large Paste Test] Decryption: ${decryptTime.toFixed(0)}ms`);

            // Verify correctness
            assert.strictEqual(decrypted, originalMessage, 'Large paste should decrypt correctly');

            // Performance assertion: should complete in reasonable time
            assert.ok(encryptTime < 30000, `Encryption took ${encryptTime.toFixed(0)}ms (should be < 30s)`);
            assert.ok(decryptTime < 30000, `Decryption took ${decryptTime.toFixed(0)}ms (should be < 30s)`);

            clean();
        });
    });
});
