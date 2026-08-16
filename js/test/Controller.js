'use strict';
require('../common');

describe('Controller', function () {
    describe('newPaste', function () {
        function stubViewUpdates() {
            const methods = {
                TopNav: [
                    'hideAllButtons',
                    'resetInput',
                    'setFormat',
                    'showCreateButtons',
                    'hideCustomAttachment'
                ],
                Alert: ['showLoading', 'hideLoading'],
                PasteStatus: ['hideMessages'],
                PasteViewer: ['hide', 'setFormat'],
                Editor: ['resetInput', 'show', 'focusInput'],
                AttachmentViewer: [
                    'removeAttachment',
                    'clearDragAndDrop',
                    'removeAttachmentData'
                ],
                DiscussionViewer: ['prepareNewDiscussion']
            };

            Object.entries(methods).forEach(([module, names]) => {
                names.forEach(name => {
                    PrivateBin[module][name] = function () {};
                });
            });
        }

        it('does not add another history entry on the new document URL', function () {
            const clean = globalThis.cleanup('', {url: 'https://example.com/path/'});
            stubViewUpdates();
            const initialHistoryLength = history.length;

            PrivateBin.Controller.newPaste();

            assert.strictEqual(history.length, initialHistoryLength);
            assert.strictEqual(window.location.href, 'https://example.com/path/');
            clean();
        });

        it('adds a history entry when leaving a viewed document', function () {
            const clean = globalThis.cleanup('', {
                url: 'https://example.com/path/?0123456789abcdef#key'
            });
            stubViewUpdates();
            const initialHistoryLength = history.length;

            PrivateBin.Controller.newPaste();

            assert.strictEqual(history.length, initialHistoryLength + 1);
            assert.strictEqual(window.location.href, 'https://example.com/path/');
            assert.deepStrictEqual(history.state, {type: 'create'});
            clean();
        });
    });
});
