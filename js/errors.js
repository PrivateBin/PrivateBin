/**
 * PrivateBin PQC Error Classes
 *
 * Provides explicit error types for post-quantum cryptographic operations.
 * All errors are designed to be user-friendly and non-leaky (no internal details exposed).
 *
 * @name errors
 * @module
 */

/**
 * Base class for all PQC-related errors
 *
 * @class
 */
class PqcError extends Error {
    /**
     * @constructor
     * @param {string} code - Error code (e.g., 'KEYGEN_FAILED')
     * @param {string} message - Human-readable error message
     * @param {*} details - Optional additional details (for debugging, not user-facing)
     */
    constructor(code, message, details = null) {
        super(message);
        this.code = code;
        this.details = details;
        this.name = 'PqcError';
    }
}

/**
 * Decryption-specific errors
 *
 * Used when paste decryption fails for any reason.
 * Messages are generic to avoid information leakage.
 *
 * @class
 * @extends PqcError
 */
class DecryptionError extends PqcError {
    /**
     * @constructor
     * @param {string} code - Error code identifying failure type
     * @param {Error} originalError - Original error that caused this (optional)
     */
    constructor(code, originalError = null) {
        const messages = {
            'V3_DECRYPTION_FAILED': 'Could not decrypt this paste. It may be corrupted.',
            'V2_DECRYPTION_FAILED': 'Could not decrypt this paste. It may be corrupted.',
            'UNSUPPORTED_VERSION': 'This paste was created with a newer version of PrivateBin. Please update.',
            'MISSING_KEM_DATA': 'Paste data is incomplete or corrupted.',
            'INVALID_KEM_DATA': 'Post-quantum data is corrupted or invalid.',
            'KEY_DERIVATION_FAILED': 'Failed to derive encryption key.',
            'BROWSER_NOT_SUPPORTED': 'Your browser doesn\'t support post-quantum cryptography. Try a modern browser.'
        };

        super(code, messages[code] || 'Unknown decryption error', originalError?.message);
        this.name = 'DecryptionError';
        this.originalError = originalError;
    }
}

/**
 * Encryption-specific errors
 *
 * Used when paste encryption fails during creation.
 * These errors trigger fallback to v2 encryption when possible.
 *
 * @class
 * @extends PqcError
 */
class EncryptionError extends PqcError {
    /**
     * @constructor
     * @param {string} code - Error code identifying failure type
     * @param {Error} originalError - Original error that caused this (optional)
     */
    constructor(code, originalError = null) {
        const messages = {
            'KEYGEN_FAILED': 'Failed to generate encryption keys.',
            'ENCAPSULATE_FAILED': 'Failed to encapsulate shared secret.',
            'ENCRYPTION_FAILED': 'Failed to encrypt paste.',
            'PRIVATE_KEY_ENCRYPT_FAILED': 'Failed to secure private key.'
        };

        super(code, messages[code] || 'Unknown encryption error', originalError?.message);
        this.name = 'EncryptionError';
        this.originalError = originalError;
    }
}

// Export for use in other modules
// Using CommonJS-style exports for compatibility with existing PrivateBin code
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PqcError, DecryptionError, EncryptionError };
}

// Also support browser global for direct script inclusion
if (typeof window !== 'undefined') {
    window.PqcError = PqcError;
    window.DecryptionError = DecryptionError;
    window.EncryptionError = EncryptionError;
}
