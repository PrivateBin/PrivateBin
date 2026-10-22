'use strict';
const common = require('../common');
const bodyTemplate = '<div id="attachmentPreview" class="col-md-12 text-center hidden"></div>' +
    '<div id="attachment" class="hidden"></div>' +
    '<div id="templates">' +
        '<div id="attachmenttemplate" role="alert" class="attachment hidden alert alert-info">' +
            '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>' +
            '<a class="alert-link">Download attachment</a>' +
        '</div>' +
    '</div>';
const fc = require('fast-check');

describe('AttachmentViewer', function () {
    beforeEach(() => {
        mockCreateObjectUrl();
    });

    afterEach(() => {
        globalThis.cleanup()
    });

    describe('whole run (setAttachment, showAttachment, removeAttachment, hideAttachment, hideAttachmentPreview, hasAttachment, getAttachment & moveAttachmentTo)', function () {
        this.timeout(30000);

        it('displays & hides data as requested', () => {
            fc.assert(fc.property(
                common.fcMimeTypes(),
                fc.string(),
                fc.string(),
                fc.string(),
                fc.string(),
                // eslint-disable-next-line complexity
                function (mimeType, rawdata, filename, prefix, postfix) {
                    let data = 'data:' + mimeType + ';base64,' + common.btoa(rawdata),
                        mimePrefix = mimeType.substring(0, 6),
                        previewSupported = (
                            mimePrefix === 'image/' ||
                            mimePrefix === 'audio/' ||
                            mimePrefix === 'video/' ||
                            mimeType.match(/\/pdf/i)
                        ) && mimeType.match(/^[a-z0-9][a-z0-9.-]*[a-z0-9]\/[a-z0-9][a-z0-9.+-]*[a-z0-9]$/),
                        results = [],
                        result = '';
                    // text node of attachment will truncate at null byte
                    if (filename === '\u0000') {
                        filename = '';
                    }
                    prefix  = prefix.replace(/%(s|d)/g, '%%');
                    postfix = postfix.replace(/%(s|d)/g, '%%').replace(/<|>/g, '');
                    document.body.innerHTML = bodyTemplate;
                    mockCreateObjectUrl(false);
                    PrivateBin.AttachmentViewer.init();
                    PrivateBin.Model.init();
                    results.push(
                        !PrivateBin.AttachmentViewer.hasAttachment() &&
                        document.getElementById('attachment').classList.contains('hidden') &&
                        document.getElementById('attachment').children.length === 0 &&
                        document.getElementById('attachmenttemplate').classList.contains('hidden') &&
                        document.getElementById('attachmentPreview').classList.contains('hidden')
                    );
                    global.atob = common.atob;
                    if (filename.length) {
                        PrivateBin.AttachmentViewer.setAttachment(data, filename);
                    } else {
                        PrivateBin.AttachmentViewer.setAttachment(data);
                    }
                    // // beyond this point we will get the blob URL instead of the data
                    data = window.URL.createObjectURL(data);
                    const attachment = PrivateBin.AttachmentViewer.getAttachments();
                    results.push(
                        PrivateBin.AttachmentViewer.hasAttachment() &&
                        document.getElementById('attachment').classList.contains('hidden') &&
                        document.getElementById('attachment').children.length > 0 &&
                        document.getElementById('attachmentPreview').classList.contains('hidden') &&
                        attachment[0][0] === data &&
                        attachment[0][1] === filename
                    );
                    PrivateBin.AttachmentViewer.showAttachment();
                    results.push(
                        !document.getElementById('attachment').classList.contains('hidden') &&
                        document.getElementById('attachment').children.length > 0 &&
                        (previewSupported ? !document.getElementById('attachmentPreview').classList.contains('hidden') : document.getElementById('attachmentPreview').classList.contains('hidden'))
                    );
                    PrivateBin.AttachmentViewer.hideAttachment();
                    results.push(
                        document.getElementById('attachment').classList.contains('hidden') &&
                        (previewSupported ? !document.getElementById('attachmentPreview').classList.contains('hidden') : document.getElementById('attachmentPreview').classList.contains('hidden'))
                    );
                    if (previewSupported) {
                        PrivateBin.AttachmentViewer.hideAttachmentPreview();
                        results.push(document.getElementById('attachmentPreview').classList.contains('hidden'));
                    }
                    PrivateBin.AttachmentViewer.showAttachment();
                    results.push(
                        !document.getElementById('attachment').classList.contains('hidden') &&
                        (previewSupported ? !document.getElementById('attachmentPreview').classList.contains('hidden') : document.getElementById('attachmentPreview').classList.contains('hidden'))
                    );
                    let element = document.createElement('div');
                    PrivateBin.AttachmentViewer.moveAttachmentTo(element, attachment[0], prefix + '%s' + postfix);
                    // messageIDs with links get a relaxed treatment
                    if (prefix.indexOf('<a') === -1 && postfix.indexOf('<a') === -1) {
                        const tempTA = document.createElement('textarea');
                        tempTA.textContent = (prefix + filename + postfix);
                        result = tempTA.textContent;
                    } else {
                        result = DOMPurify.sanitize(
                            prefix + PrivateBin.Helper.htmlEntities(filename) + postfix, {
                                ALLOWED_TAGS: ['a', 'i', 'span'],
                                ALLOWED_ATTR: ['href', 'id']
                            }
                        );
                    }
                    if (filename.length) {
                        results.push(
                            element.querySelector('a').href === data &&
                            element.querySelector('a').getAttribute('download') === filename &&
                            element.querySelector('a').textContent === result
                        );
                    } else {
                        results.push(element.querySelector('a').href === data);
                    }
                    PrivateBin.AttachmentViewer.removeAttachment();
                    results.push(
                        document.getElementById('attachment').classList.contains('hidden') &&
                        document.getElementById('attachment').children.length === 0 &&
                        document.getElementById('attachmentPreview').classList.contains('hidden')
                    );
                    return results.every(element => element);
                }
            ));
        });

        it(
            'sanitizes file names',
            function() {
                document.body.innerHTML = bodyTemplate;
                PrivateBin.AttachmentViewer.init();
                PrivateBin.Model.init();
                global.atob = common.atob;

                const maliciousFileNames = [
                    '<script>alert("☹️");//<a',
                    '"><meta http-equiv="refresh" content="0;url=http://example.com/">.txt'
                ];
                for (const filename of maliciousFileNames) {
                    PrivateBin.AttachmentViewer.setAttachment('data:;base64,', filename);
                    assert.ok(!document.body.innerHTML.includes(filename));
                    PrivateBin.AttachmentViewer.removeAttachment();
                }
            }
        );

        it(
            'sanitizes MIME types in attachments',
            function() {
                document.body.innerHTML = bodyTemplate;
                PrivateBin.AttachmentViewer.init();
                PrivateBin.Model.init();
                global.atob = common.atob;

                const maliciousMimeTypes = [
                    // PDF bypasses
                    'application/x-pdf',    // legacy, we don't need to support this
                    'text/html /pdf',       // trips up Firefox and Chromium
                    'text/html(/pdf',       // Chromium, see: https://chromium.googlesource.com/chromium/src/+/refs/tags/152.0.7949.0/net/base/mime_util.cc#521

                    // SVG bypass
                    'text/html svg',
                    'text/html(svg',

                    // invalid bytes after string
                    'image/png\x01'
                ];
                for (const mimeType of maliciousMimeTypes) {
                    assert.ok(!PrivateBin.AttachmentViewer.isSafeMimeType(mimeType), 'does not treat as safe MIME type: '+ mimeType);
                    PrivateBin.AttachmentViewer.setAttachment('data:' + mimeType + ';base64,', 'example file name');
                    assert.ok(!document.body.innerHTML.includes(mimeType), 'does not allow MIME type: ' + mimeType);
                    assert.ok(!document.body.innerHTML.includes(mimeType.toLowerCase()), 'does not allow lower cased MIME type: ' + mimeType);
                    assert.ok(!document.body.innerHTML.includes('<img'), 'does not allow image MIME type: ' + mimeType);
                    PrivateBin.AttachmentViewer.removeAttachment();
                }
            }
        );

        it(
            'supports safe MIME types in attachments',
            function() {
                document.body.innerHTML = bodyTemplate;
                PrivateBin.AttachmentViewer.init();
                PrivateBin.Model.init();
                global.atob = common.atob;

                const supportedSafeMimeTypes = [
                    'text/plain',
                    'image/png',
                    'image/jpeg'
                ];
                for (const mimeType of supportedSafeMimeTypes) {
                    assert.ok(PrivateBin.AttachmentViewer.isSafeMimeType(mimeType), 'treats as safe MIME type: '+ mimeType);
                }
            }
        );

        it(
            'supports safe MIME type previews in attachments',
            function() {
                document.body.innerHTML = bodyTemplate;
                PrivateBin.AttachmentViewer.init();
                PrivateBin.Model.init();
                global.atob = common.atob;

                const supportedPreviewMimeTypes = [
                    'application/pdf',
                    'audio/wav',
                    'video/avi'
                ];
                for (const mimeType of supportedPreviewMimeTypes) {
                    assert.ok(PrivateBin.AttachmentViewer.isSafeMimeType(mimeType), 'treats as safe preview MIME type: '+ mimeType);
                    PrivateBin.AttachmentViewer.setAttachment('data:' + mimeType + ';base64,', 'example file name');
                    assert.ok(document.body.innerHTML.includes(mimeType), 'allows MIME type: ' + mimeType);
                    PrivateBin.AttachmentViewer.removeAttachment();
                }
            }
        );

        it(
            'sanitize potentially unsafe SVG previews',
            function() {
                document.body.innerHTML = bodyTemplate;
                PrivateBin.AttachmentViewer.init();
                PrivateBin.Model.init();
                global.atob = common.atob;

                const svgMimeTypes = [
                    'image/svg+xml',
                    'image/SVG+xml',
                    'image/SVG',
                    'image/sVg'
                ];
                for (const mimeType of svgMimeTypes) {
                    assert.ok(!PrivateBin.AttachmentViewer.isSafeMimeType(mimeType), 'treats as unsafe MIME type: '+ mimeType);
                    PrivateBin.AttachmentViewer.setAttachment('data:' + mimeType + ';base64,', 'example file name');
                    assert.ok(document.body.innerHTML.includes('image/svg+xml'), 'allows sanitized MIME type: ' + mimeType);
                    PrivateBin.AttachmentViewer.removeAttachment();
                }
            }
        );
    });

    describe('showAttachment()', function () {
        it('displays attachment even when attachmentPreview element is missing',
            function() {
                document.body.innerHTML = (
                    '<div id="attachment" class="hidden"></div>' +
                    '<div id="templates">' +
                        '<div id="attachmenttemplate" role="alert" class="attachment hidden alert alert-info">' +
                            '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>' +
                            '<a class="alert-link">Download attachment</a>' +
                        '</div>' +
                    '</div>'
                );
                // Note: attachmentPreview element is intentionally NOT created

                PrivateBin.AttachmentViewer.init();
                PrivateBin.Model.init();
                global.atob = common.atob;

                // Set attachment without preview element
                PrivateBin.AttachmentViewer.setAttachment('data:text/plain;base64,', 'test.txt');

                // Show attachment should work even without attachmentPreview
                PrivateBin.AttachmentViewer.showAttachment();

                const attachment = document.getElementById('attachment');
                assert.ok(!attachment.classList.contains('hidden'), 'Attachment should be visible');
                assert.ok(attachment.children.length > 0, 'Attachment should have content');
            }
        )
    });

    function mockCreateObjectUrl(includeType = true) {
        Object.defineProperty(
            window.URL,
            'createObjectURL',
            {
                configurable: true,
                value: function (blob) {
                    return 'blob:' + (includeType ? blob.type : location.origin) + '/1b9d6bcd-bbfd-4b2d-9b5d-ab8dfbbd4bed';
                }
            }
        );
    }
});
