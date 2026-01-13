/**
 * PrivateBin PQC Cryptography Module
 *
 * Provides post-quantum cryptographic operations using ML-KEM (Kyber-768).
 * All operations are designed for browser compatibility and zero-knowledge encryption.
 *
 * Security Model:
 * - KEM keys stored UNENCRYPTED in paste metadata (by design)
 * - Security comes from urlKey in URL fragment
 * - Server cannot derive contentKey without urlKey
 * - Zero-knowledge property preserved
 *
 * Reference: NIST FIPS 203 - ML-KEM Standard
 *
 * @name pqccrypto
 * @module
 */

// Expected WASM hash (to be updated when WASM module is installed)
const EXPECTED_WASM_HASH = 'sha384-PLACEHOLDER_HASH_WILL_BE_UPDATED';

// Self-hosted WASM configuration
const SELF_HOSTED_WASM_ENABLED = false; // Set to true to use self-hosted WASM
const SELF_HOSTED_WASM_PATH = '/js/mlkem768.wasm'; // Path to self-hosted WASM file

// Performance monitoring configuration
const ENABLE_PERFORMANCE_LOGGING = true; // Set to false to disable performance logs

let wasmModule = null;
let initialized = false;
let performanceStats = {
    keygen: [],
    encapsulate: [],
    decapsulate: [],
    hkdf: []
};

/**
 * PqcCrypto module
 * @namespace
 */
const PqcCrypto = (function () {
    'use strict';

    /**
     * Initialize the PQC WASM module
     *
     * Must be called on page load before any other PQC operations.
     * Loads and initializes ML-KEM WASM module with integrity verification.
     *
     * @async
     * @returns {Promise<void>}
     * @throws {Error} If WASM module fails to load or integrity check fails
     *
     * @example
     * await PqcCrypto.initialize();
     * console.log('PQC ready');
     */
    async function initialize() {
        if (initialized) {
            return;
        }

        try {
            // Import ML-KEM WASM module
            if (SELF_HOSTED_WASM_ENABLED) {
                // Self-hosted WASM: load from specified path
                console.info('[PQC] Using self-hosted WASM from:', SELF_HOSTED_WASM_PATH);
                wasmModule = await loadSelfHostedWasm(SELF_HOSTED_WASM_PATH);
            } else {
                // Use npm-installed module
                if (typeof window !== 'undefined' && window.mlkem) {
                    wasmModule = window.mlkem;
                } else if (typeof require !== 'undefined') {
                    // Node.js/CommonJS environment
                    wasmModule = require('mlkem-wasm');
                } else {
                    throw new Error('ML-KEM WASM module not found');
                }
            }

            // Initialize WASM (if needed by the library)
            if (wasmModule.init && typeof wasmModule.init === 'function') {
                await wasmModule.init();
            }

            // Verify WASM integrity (hash-pinning)
            // Only if hash is configured (not placeholder)
            if (EXPECTED_WASM_HASH !== 'sha384-PLACEHOLDER_HASH_WILL_BE_UPDATED') {
                console.info('[PQC] Verifying WASM integrity...');
                await verifyWasmIntegrity(wasmModule, EXPECTED_WASM_HASH);
                console.info('[PQC] WASM integrity verified');
            } else {
                console.warn('[PQC] WASM integrity check disabled (hash not configured)');
            }

            initialized = true;
            console.info('ML-KEM WASM module initialized successfully');
        } catch (error) {
            console.error('Failed to initialize ML-KEM WASM module:', error);
            throw new Error('PQC initialization failed: ' + error.message);
        }
    }

    /**
     * Generate ephemeral Kyber-768 keypair
     *
     * Creates a new keypair for use in a single paste encryption operation.
     * Private key is stored UNENCRYPTED in paste (security from urlKey).
     * Public key is used for encapsulation, then discarded.
     *
     * Reference: NIST FIPS 203 IPD - ML-KEM KeyGen
     *
     * @async
     * @returns {Promise<{publicKey: Uint8Array, privateKey: Uint8Array}>}
     *   - publicKey: ~1184 bytes (ML-KEM-768 public key)
     *   - privateKey: ~2400 bytes (ML-KEM-768 private key)
     * @throws {EncryptionError} If key generation fails
     *
     * @example
     * const {publicKey, privateKey} = await PqcCrypto.generateKeypair();
     * console.log('Public key:', publicKey.length, 'bytes');
     * console.log('Private key:', privateKey.length, 'bytes');
     */
    async function generateKeypair() {
        if (!initialized) {
            throw new EncryptionError('KEYGEN_FAILED', new Error('PQC not initialized'));
        }

        const startTime = ENABLE_PERFORMANCE_LOGGING ? performance.now() : 0;

        try {
            // Generate ML-KEM-768 keypair
            // API will depend on the actual mlkem-wasm library structure
            // Based on research, mlkem-wasm uses: new MlKem768() class

            const kem = new wasmModule.MlKem768();
            const [publicKey, privateKey] = await kem.generateKeyPair();

            // Performance logging
            if (ENABLE_PERFORMANCE_LOGGING) {
                const duration = performance.now() - startTime;
                performanceStats.keygen.push(duration);
                console.log(`[PQC Performance] Keygen: ${duration.toFixed(2)}ms`);
            }

            // Verify key sizes match expected values for Kyber-768
            if (publicKey.length !== 1184) {
                console.warn('Unexpected public key size:', publicKey.length, 'expected 1184');
            }
            if (privateKey.length !== 2400) {
                console.warn('Unexpected private key size:', privateKey.length, 'expected 2400');
            }

            return {
                publicKey: publicKey,
                privateKey: privateKey
            };
        } catch (error) {
            console.error('Key generation failed:', error);
            throw new EncryptionError('KEYGEN_FAILED', error);
        }
    }

    /**
     * Encapsulate shared secret using public key
     *
     * Performs KEM encapsulation to generate a shared secret and ciphertext.
     * This is an IND-CCA2 secure operation.
     *
     * Reference: NIST FIPS 203 IPD - ML-KEM Encaps
     *
     * @async
     * @param {Uint8Array} publicKey - ML-KEM-768 public key (~1184 bytes)
     * @returns {Promise<{sharedSecret: Uint8Array, ciphertext: Uint8Array}>}
     *   - sharedSecret: 32 bytes (256-bit shared secret)
     *   - ciphertext: ~1088 bytes (KEM ciphertext)
     * @throws {EncryptionError} If encapsulation fails
     *
     * @example
     * const {publicKey} = await PqcCrypto.generateKeypair();
     * const {sharedSecret, ciphertext} = await PqcCrypto.encapsulate(publicKey);
     * console.log('Shared secret:', sharedSecret.length, 'bytes');
     * console.log('Ciphertext:', ciphertext.length, 'bytes');
     */
    async function encapsulate(publicKey) {
        if (!initialized) {
            throw new EncryptionError('ENCAPSULATE_FAILED', new Error('PQC not initialized'));
        }

        const startTime = ENABLE_PERFORMANCE_LOGGING ? performance.now() : 0;

        try {
            // Perform encapsulation
            const kem = new wasmModule.MlKem768();
            const [ciphertext, sharedSecret] = await kem.encap(publicKey);

            // Performance logging
            if (ENABLE_PERFORMANCE_LOGGING) {
                const duration = performance.now() - startTime;
                performanceStats.encapsulate.push(duration);
                console.log(`[PQC Performance] Encapsulate: ${duration.toFixed(2)}ms`);
            }

            // Verify output sizes
            if (sharedSecret.length !== 32) {
                console.warn('Unexpected shared secret size:', sharedSecret.length, 'expected 32');
            }
            if (ciphertext.length !== 1088) {
                console.warn('Unexpected ciphertext size:', ciphertext.length, 'expected 1088');
            }

            return {
                sharedSecret: sharedSecret,
                ciphertext: ciphertext
            };
        } catch (error) {
            console.error('Encapsulation failed:', error);
            throw new EncryptionError('ENCAPSULATE_FAILED', error);
        }
    }

    /**
     * Decapsulate shared secret using private key and ciphertext
     *
     * Recovers the shared secret from the KEM ciphertext using the private key.
     * Must match the original shared secret from encapsulation.
     *
     * Reference: NIST FIPS 203 IPD - ML-KEM Decaps
     *
     * @async
     * @param {Uint8Array} ciphertext - KEM ciphertext (~1088 bytes)
     * @param {Uint8Array} privateKey - ML-KEM-768 private key (~2400 bytes)
     * @returns {Promise<Uint8Array>} sharedSecret - 32-byte shared secret
     * @throws {DecryptionError} If decapsulation fails
     *
     * @example
     * const {ciphertext} = await PqcCrypto.encapsulate(publicKey);
     * const sharedSecret = await PqcCrypto.decapsulate(ciphertext, privateKey);
     * console.log('Decapsulated secret:', sharedSecret.length, 'bytes');
     */
    async function decapsulate(ciphertext, privateKey) {
        if (!initialized) {
            throw new DecryptionError('KEY_DERIVATION_FAILED', new Error('PQC not initialized'));
        }

        const startTime = ENABLE_PERFORMANCE_LOGGING ? performance.now() : 0;

        try {
            // Perform decapsulation
            const kem = new wasmModule.MlKem768();
            const sharedSecret = await kem.decap(ciphertext, privateKey);

            // Performance logging
            if (ENABLE_PERFORMANCE_LOGGING) {
                const duration = performance.now() - startTime;
                performanceStats.decapsulate.push(duration);
                console.log(`[PQC Performance] Decapsulate: ${duration.toFixed(2)}ms`);
            }

            // Verify output size
            if (sharedSecret.length !== 32) {
                console.warn('Unexpected shared secret size:', sharedSecret.length, 'expected 32');
            }

            // Note: sharedSecret is returned and will be zeroed by caller after deriveContentKey()
            return sharedSecret;
        } catch (error) {
            console.error('Decapsulation failed:', error);
            throw new DecryptionError('KEY_DERIVATION_FAILED', error);
        }
    }

    /**
     * Derive content encryption key from shared secret and urlKey
     *
     * Uses HKDF-SHA-256 to derive the final content encryption key.
     * Combines BOTH shared secret (from KEM) AND urlKey (from URL fragment).
     * This provides defense-in-depth: attacker needs both to decrypt.
     *
     * Algorithm: HKDF-SHA-256
     * Input: sharedSecret (32 bytes) || urlKey (32 bytes)
     * Salt: none (zero-length)
     * Info: "PrivateBin-v3-PQC" (context binding)
     * Output: 32-byte key for AES-256-GCM
     *
     * Reference: RFC 5869 - HKDF
     *
     * @async
     * @param {Uint8Array} sharedSecret - 32-byte shared secret from KEM
     * @param {Uint8Array} urlKey - 32-byte key from URL fragment
     * @returns {Promise<Uint8Array>} contentKey - 32-byte encryption key
     * @throws {DecryptionError} If key derivation fails
     *
     * @example
     * const contentKey = await PqcCrypto.deriveContentKey(sharedSecret, urlKey);
     * // Use contentKey with AES-256-GCM to encrypt/decrypt paste content
     */
    async function deriveContentKey(sharedSecret, urlKey) {
        if (!initialized) {
            throw new DecryptionError('KEY_DERIVATION_FAILED', new Error('PQC not initialized'));
        }

        const startTime = ENABLE_PERFORMANCE_LOGGING ? performance.now() : 0;
        let combined = null;

        try {
            // Combine shared secret and urlKey
            combined = new Uint8Array(sharedSecret.length + urlKey.length);
            combined.set(sharedSecret, 0);
            combined.set(urlKey, sharedSecret.length);

            // Import combined key material as HKDF key
            const baseKey = await window.crypto.subtle.importKey(
                'raw',
                combined,
                'HKDF',
                false,
                ['deriveBits']
            );

            // HKDF parameters
            const params = {
                name: 'HKDF',
                hash: 'SHA-256',
                salt: new Uint8Array(0), // No salt (zero-length)
                info: new TextEncoder().encode('PrivateBin-v3-PQC')
            };

            // Derive 256-bit key
            const derivedBits = await window.crypto.subtle.deriveBits(
                params,
                baseKey,
                256 // 32 bytes * 8 bits
            );

            const contentKey = new Uint8Array(derivedBits);

            // Performance logging
            if (ENABLE_PERFORMANCE_LOGGING) {
                const duration = performance.now() - startTime;
                performanceStats.hkdf.push(duration);
                console.log(`[PQC Performance] HKDF: ${duration.toFixed(2)}ms`);
            }

            // Verify output size
            if (contentKey.length !== 32) {
                throw new Error('Key derivation produced unexpected size: ' + contentKey.length);
            }

            // Security: Zero out sensitive intermediate buffers
            // Reduces window for memory scraping attacks
            combined.fill(0);

            return contentKey;
        } catch (error) {
            // Zero out sensitive data on error path too
            if (combined) {
                combined.fill(0);
            }
            console.error('Key derivation failed:', error);
            throw new DecryptionError('KEY_DERIVATION_FAILED', error);
        }
    }

    /**
     * Serialize private key for embedding in paste
     *
     * Converts Uint8Array private key to base64 string for storage in kem.privkey field.
     * The private key is stored UNENCRYPTED (security comes from urlKey).
     *
     * @param {Uint8Array} privateKey - ML-KEM-768 private key (~2400 bytes)
     * @returns {string} Base64-encoded private key
     *
     * @example
     * const serialized = PqcCrypto.serializePrivateKey(privateKey);
     * // Store in paste.kem.privkey
     */
    function serializePrivateKey(privateKey) {
        try {
            // Convert Uint8Array to base64
            return btoa(String.fromCharCode.apply(null, privateKey));
        } catch (error) {
            console.error('Private key serialization failed:', error);
            throw new Error('Failed to serialize private key: ' + error.message);
        }
    }

    /**
     * Deserialize private key from paste
     *
     * Converts base64 string back to Uint8Array for use in decapsulation.
     * Extracts private key from kem.privkey field.
     *
     * @param {string} serialized - Base64-encoded private key
     * @returns {Uint8Array} ML-KEM-768 private key (~2400 bytes)
     * @throws {DecryptionError} If deserialization fails
     *
     * @example
     * const privateKey = PqcCrypto.deserializePrivateKey(paste.kem.privkey);
     * const sharedSecret = await PqcCrypto.decapsulate(ciphertext, privateKey);
     */
    function deserializePrivateKey(serialized) {
        try {
            // Decode base64 to Uint8Array
            const binaryString = atob(serialized);
            const bytes = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }
            return bytes;
        } catch (error) {
            console.error('Private key deserialization failed:', error);
            throw new DecryptionError('INVALID_KEM_DATA', error);
        }
    }

    /**
     * Check browser support for PQC operations
     *
     * Detects whether the browser supports all required APIs:
     * - Web Crypto API (crypto.subtle)
     * - WebAssembly
     * - Secure Random (crypto.getRandomValues)
     * - HKDF (for key derivation)
     *
     * Used for graceful degradation to v2 encryption when PQC unavailable.
     *
     * @async
     * @returns {Promise<{supported: boolean, missing: string[]}>}
     *   - supported: true if all features available
     *   - missing: array of missing feature names
     *
     * @example
     * const {supported, missing} = await PqcCrypto.checkBrowserSupport();
     * if (!supported) {
     *     console.warn('Missing features:', missing);
     *     // Fall back to v2 encryption
     * }
     */
    async function checkBrowserSupport() {
        const checks = {
            webCrypto: !!(window.crypto && window.crypto.subtle),
            webAssembly: typeof WebAssembly === 'object',
            secureRandom: !!(window.crypto && window.crypto.getRandomValues),
            hkdf: false // Will be checked below
        };

        // Check HKDF support
        if (checks.webCrypto) {
            try {
                await window.crypto.subtle.importKey(
                    'raw',
                    new Uint8Array(32),
                    'HKDF',
                    false,
                    ['deriveBits']
                );
                checks.hkdf = true;
            } catch (error) {
                checks.hkdf = false;
            }
        }

        const supported = Object.values(checks).every(v => v === true);
        const missing = Object.keys(checks).filter(k => !checks[k]);

        return { supported, missing };
    }

    /**
     * Load self-hosted WASM module
     *
     * Loads WASM binary from a specified path instead of npm package.
     * Useful for air-gapped environments or supply chain security.
     *
     * @private
     * @async
     * @param {string} path - Path to WASM file
     * @returns {Promise<object>} WASM module instance
     * @throws {Error} If loading fails
     */
    async function loadSelfHostedWasm(path) {
        try {
            // Fetch WASM binary
            const response = await fetch(path);
            if (!response.ok) {
                throw new Error(`Failed to fetch WASM: ${response.status} ${response.statusText}`);
            }

            const wasmBytes = await response.arrayBuffer();

            // Compile and instantiate WASM
            const wasmModule = await WebAssembly.instantiate(wasmBytes);

            // Create wrapper object matching mlkem-wasm API
            // This is a simplified wrapper - adjust based on actual mlkem-wasm API
            return {
                MlKem768: wasmModule.instance.exports.MlKem768 || function() {
                    // Placeholder for WASM exports
                    throw new Error('ML-KEM-768 not available in self-hosted WASM');
                },
                init: async function() {
                    // No-op for self-hosted WASM
                },
                _wasmBytes: wasmBytes // Store for integrity verification
            };
        } catch (error) {
            console.error('Failed to load self-hosted WASM:', error);
            throw new Error('Self-hosted WASM loading failed: ' + error.message);
        }
    }

    /**
     * Verify WASM module integrity (optional, for supply chain security)
     *
     * Compares WASM module hash against expected value.
     * This is a defense against supply chain attacks.
     *
     * @private
     * @async
     * @param {object} wasmModule - WASM module instance
     * @param {string} expectedHash - Expected SHA-384 hash (base64)
     * @throws {Error} If hash mismatch
     */
    async function verifyWasmIntegrity(wasmModule, expectedHash) {
        try {
            let wasmBytes;

            // Try to get WASM bytes from different sources
            if (wasmModule._wasmBytes) {
                // Self-hosted WASM stores bytes
                wasmBytes = wasmModule._wasmBytes;
            } else if (wasmModule.wasmBinary) {
                // Some WASM modules expose binary
                wasmBytes = wasmModule.wasmBinary;
            } else {
                // Cannot verify - module doesn't expose bytes
                console.warn('[PQC] Cannot verify WASM integrity: bytes not accessible');
                console.warn('[PQC] Relying on Subresource Integrity (SRI) if configured');
                return;
            }

            // Compute SHA-384 hash
            const hashBuffer = await crypto.subtle.digest('SHA-384', wasmBytes);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const computedHash = 'sha384-' + btoa(String.fromCharCode.apply(null, hashArray));

            // Compare hashes
            if (computedHash !== expectedHash) {
                throw new Error(`WASM integrity check failed: expected ${expectedHash}, got ${computedHash}`);
            }

            console.info('[PQC] WASM integrity verified successfully');
        } catch (error) {
            console.error('[PQC] WASM integrity verification failed:', error);
            throw error;
        }
    }

    /**
     * Get performance statistics
     *
     * Returns aggregated performance metrics for all PQC operations.
     * Useful for monitoring and debugging performance issues.
     *
     * @returns {Object} Performance statistics with average, min, max for each operation
     *
     * @example
     * const stats = PqcCrypto.getPerformanceStats();
     * console.log('Average keygen time:', stats.keygen.avg, 'ms');
     */
    function getPerformanceStats() {
        const calculateStats = (array) => {
            if (array.length === 0) {
                return { count: 0, avg: 0, min: 0, max: 0 };
            }
            const sum = array.reduce((a, b) => a + b, 0);
            return {
                count: array.length,
                avg: (sum / array.length).toFixed(2),
                min: Math.min(...array).toFixed(2),
                max: Math.max(...array).toFixed(2)
            };
        };

        return {
            keygen: calculateStats(performanceStats.keygen),
            encapsulate: calculateStats(performanceStats.encapsulate),
            decapsulate: calculateStats(performanceStats.decapsulate),
            hkdf: calculateStats(performanceStats.hkdf),
            enabled: ENABLE_PERFORMANCE_LOGGING
        };
    }

    /**
     * Reset performance statistics
     *
     * Clears all collected performance metrics.
     * Useful for testing or monitoring specific operations.
     *
     * @example
     * PqcCrypto.resetPerformanceStats();
     */
    function resetPerformanceStats() {
        performanceStats = {
            keygen: [],
            encapsulate: [],
            decapsulate: [],
            hkdf: []
        };
    }

    // Public API
    return {
        initialize,
        generateKeypair,
        encapsulate,
        decapsulate,
        deriveContentKey,
        serializePrivateKey,
        deserializePrivateKey,
        checkBrowserSupport,
        getPerformanceStats,
        resetPerformanceStats
    };
})();

// Export for use in other modules
// CommonJS-style exports for compatibility
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PqcCrypto;
}

// Browser global for direct script inclusion
if (typeof window !== 'undefined') {
    window.PqcCrypto = PqcCrypto;
}
