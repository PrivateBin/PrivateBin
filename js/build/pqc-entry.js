/**
 * Browser adapter for the ML-KEM-768 implementation bundled from
 * @noble/post-quantum. The generated ml-kem-0.7.0.js is committed so a
 * PrivateBin deployment never needs Node.js at runtime.
 *
 * @license MIT (bundled dependency); see ../../LICENSES/ML-KEM.md
 */
import { ml_kem768 } from '@noble/post-quantum/ml-kem.js';

globalThis.PrivateBinPQC = Object.freeze({
    keygen: function () {
        return ml_kem768.keygen();
    },
    encapsulate: function (publicKey) {
        return ml_kem768.encapsulate(publicKey);
    },
    decapsulate: function (cipherText, secretKey) {
        return ml_kem768.decapsulate(cipherText, secretKey);
    },
    lengths: Object.freeze({
        publicKey: ml_kem768.lengths.publicKey,
        secretKey: ml_kem768.lengths.secretKey,
        cipherText: ml_kem768.lengths.cipherText,
        sharedSecret: 32
    })
});
