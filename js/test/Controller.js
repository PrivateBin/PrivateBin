'use strict';
require('../common');

describe('Controller', function () {
    afterEach(function () {
        globalThis.cleanup();
    });

    describe('newPaste', function () {
        it('does not add a redundant history entry on the home page', function () {
            globalThis.cleanup(undefined, {url: 'https://example.com/'});

            [
                [PrivateBin.TopNav, [
                    'hideAllButtons',
                    'resetInput',
                    'setFormat',
                    'showCreateButtons',
                    'hideCustomAttachment'
                ]],
                [PrivateBin.Alert, ['showLoading', 'hideLoading']],
                [PrivateBin.PasteStatus, ['hideMessages']],
                [PrivateBin.PasteViewer, ['hide', 'setFormat']],
                [PrivateBin.Editor, ['resetInput', 'show', 'focusInput']],
                [PrivateBin.AttachmentViewer, [
                    'removeAttachment',
                    'clearDragAndDrop',
                    'removeAttachmentData'
                ]],
                [PrivateBin.DiscussionViewer, ['prepareNewDiscussion']]
            ].forEach(([module, methods]) => {
                methods.forEach(method => {
                    module[method] = function () {};
                });
            });

            let pushCount = 0;
            history.pushState = function () {
                ++pushCount;
            };

            PrivateBin.Controller.newPaste();

            assert.strictEqual(pushCount, 0);
        });
    });
});
