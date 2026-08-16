'use strict';
require('../common');

describe('PasteDecrypter', function () {
    describe('decryptComments', function () {
        it('skips malformed comments without rejecting the discussion', async function () {
            const clean = globalThis.cleanup();
            const added = [];

            PrivateBin.CryptTool.decipher = async function (key, password, cipherData) {
                if (cipherData[0] === 'valid') {
                    return '{"comment":"visible","nickname":"author"}';
                }
                if (cipherData[0] === 'rejected') {
                    throw new Error('decryption failed');
                }
                if (cipherData[0] === 'invalid-comment-type') {
                    return '{"comment":{},"nickname":"author"}';
                }
                if (cipherData[0] === 'invalid-nickname-type') {
                    return '{"comment":"hidden","nickname":[]}';
                }
                return 'not valid JSON';
            };
            PrivateBin.DiscussionViewer.prepareNewDiscussion = function () {};
            PrivateBin.DiscussionViewer.addComment = function (comment, message, nickname) {
                added.push([comment.id, message, nickname]);
            };

            const paste = {
                comments: [
                    {
                        id: 'aaaaaaaaaaaaaaaa',
                        parentid: 'bbbbbbbbbbbbbbbb',
                        ct: 'valid',
                        adata: [],
                        meta: {created: 1}
                    },
                    {
                        id: 'cccccccccccccccc',
                        parentid: 'bbbbbbbbbbbbbbbb',
                        ct: 'invalid',
                        adata: [],
                        meta: {created: 2}
                    },
                    {
                        id: 'dddddddddddddddd',
                        parentid: 'bbbbbbbbbbbbbbbb',
                        ct: 'rejected',
                        adata: [],
                        meta: {created: 3}
                    },
                    {
                        id: 'eeeeeeeeeeeeeeee',
                        parentid: 'bbbbbbbbbbbbbbbb',
                        ct: 'invalid-comment-type',
                        adata: [],
                        meta: {created: 4}
                    },
                    {
                        id: 'ffffffffffffffff',
                        parentid: 'bbbbbbbbbbbbbbbb',
                        ct: 'invalid-nickname-type',
                        adata: [],
                        meta: {created: 5}
                    }
                ]
            };

            await PrivateBin.PasteDecrypter.decryptComments(paste, 'key', 'password');

            assert.deepStrictEqual(added, [
                ['aaaaaaaaaaaaaaaa', 'visible', 'author']
            ]);
            clean();
        });
    });
});
