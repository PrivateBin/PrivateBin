import { Base64 } from 'js-base64'
import { deflateRaw, inflateRaw } from 'pako'
import { decrypt, encrypt, random, hash, codec } from 'sjcl'

const RawDeflate = {
  deflate: function deflate(s) { return deflateRaw(s, { to: 'string' }) },
  inflate: function inflate(s) { return inflateRaw(s, { to: 'string' }) }
}

/**
 * handles everything related to en/decryption
 *
 * @name CryptTool
 * @class
 */

/**
 * compress a message (deflate compression), returns base64 encoded data
 *
 * @name   CryptTool.compress
 * @function
 * @private
 * @param  {string} message
 * @return {string} base64 data
 */
function compress(message)
{
    return Base64.toBase64( RawDeflate.deflate( Base64.utob(message) ) );
}

/**
 * decompress a message compressed with cryptToolcompress()
 *
 * @name   CryptTool.decompress
 * @function
 * @private
 * @param  {string} data - base64 data
 * @return {string} message
 */
function decompress(data)
{
    return Base64.btou( RawDeflate.inflate( Base64.fromBase64(data) ) );
}

/**
 * compress, then encrypt message with given key and password
 *
 * @name   CryptTool.cipher
 * @function
 * @param  {string} key
 * @param  {string} password
 * @param  {string} message
 * @return {string} data - JSON with encrypted data
 */
export function cipher(key, password, message)
{
    // Galois Counter Mode, keysize 256 bit, authentication tag 128 bit
    var options = {
        mode: 'gcm',
        ks: 256,
        ts: 128
    };

    if ((password || '').trim().length === 0) {
        return encrypt(key, compress(message), options);
    }
    return encrypt(key + codec.hex.fromBits(hash.sha256.hash(password)), compress(message), options);
}

/**
 * decrypt message with key, then decompress
 *
 * @name   CryptTool.decipher
 * @function
 * @param  {string} key
 * @param  {string} password
 * @param  {string} data - JSON with encrypted data
 * @return {string} decrypted message, empty if decryption failed
 */
export function decipher(key, password, data)
{
    if (data !== undefined) {
        try {
            return decompress(decrypt(key, data));
        } catch(err) {
            try {
                return decompress(decrypt(key + codec.hex.fromBits(hash.sha256.hash(password)), data));
            } catch(e) {
                return '';
            }
        }
    }
}

/**
 * checks whether the crypt tool has collected enough entropy
 *
 * @name   CryptTool.isEntropyReady
 * @function
 * @return {bool}
 */
export function isEntropyReady()
{
    return random.isReady();
}

/**
 * add a listener function, triggered when enough entropy is available
 *
 * @name   CryptTool.addEntropySeedListener
 * @function
 * @param {function} func
 */
export function addEntropySeedListener(func)
{
    random.addEventListener('seeded', func);
}

/**
 * returns a random symmetric key
 *
 * @name   CryptTool.getSymmetricKey
 * @function
 * @return {string} func
 */
export function getSymmetricKey()
{
    return codec.base64.fromBits(random.randomWords(8, 0), 0);
}
