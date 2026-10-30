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

    describe('SVG preview encoding', function () {
        this.timeout(30000);

        // same as purifySvgConfig in privatebin.js
        const purifySvgConfig = {
            USE_PROFILES: {
                svg: true,
                svgFilters: true
            }
        };

        // runs setAttachment on the given data URI and returns the created blobs
        function getAttachmentBlobs(dataUri) {
            const blobs = [];
            document.body.innerHTML = bodyTemplate;
            Object.defineProperty(
                window.URL,
                'createObjectURL',
                {
                    configurable: true,
                    value: function (blob) {
                        blobs.push(blob);
                        return 'blob:' + blob.type + '/1b9d6bcd-bbfd-4b2d-9b5d-ab8dfbbd4bed';
                    }
                }
            );
            PrivateBin.AttachmentViewer.init();
            PrivateBin.Model.init();
            global.atob = common.atob;
            PrivateBin.AttachmentViewer.setAttachment(dataUri, 'attachment');
            return blobs;
        }

        // jsdom's Blob lacks arrayBuffer(), so read it using FileReader
        async function readBlob(blob) {
            const buffer = await new Promise((resolve, reject) => {
                const reader = new window.FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = () => reject(reader.error);
                reader.readAsArrayBuffer(blob);
            });
            return new Uint8Array(buffer);
        }

        // returns the bytes of a data URL, either base64 or percent-encoded
        function readDataUrl(url) {
            const match = /^data:([^,]*),(.*)$/s.exec(url);
            assert.ok(match, 'uses a data URL, got: ' + url.substring(0, 50));
            if (match[1].endsWith(';base64')) {
                return new Uint8Array(Buffer.from(match[2], 'base64'));
            }
            assert.ok(/^[\x21-\x7e]*$/.test(match[2]), 'percent-encoded data URL only contains ASCII');
            return new Uint8Array(Buffer.from(
                match[2].replace(
                    /%([0-9a-f]{2})/gi,
                    (_, hex) => String.fromCharCode(parseInt(hex, 16))
                ),
                'latin1'
            ));
        }

        // runs setAttachment on the given SVG source and returns the bytes of
        // the sanitized SVG, used for the preview
        async function getSvgPreviewBytes(svg) {
            getAttachmentBlobs(
                'data:image/svg+xml;base64,' + Buffer.from(svg, 'utf8').toString('base64')
            );
            const images = document.querySelectorAll('#attachmentPreview img');
            assert.strictEqual(images.length, 1, 'shows one SVG preview');
            return readDataUrl(images[0].getAttribute('src'));
        }

        const lookalikeVectors = {
            'literal ļ/ľ (U+013C/U+013E)': '<svg xmlns="http://www.w3.org/2000/svg">\u013cscript\u013ealert(1)\u013c/script\u013e</svg>',
            'character references &#x13c;/&#x13e; (U+013C/U+013E)': '<svg xmlns="http://www.w3.org/2000/svg">&#x13c;script&#x13e;alert(1)&#x13c;/script&#x13e;</svg>',
            'CJK 丼/举 (U+4E3C/U+4E3E)': '<svg xmlns="http://www.w3.org/2000/svg">\u4e3cscript\u4e3ealert(1)\u4e3c/script\u4e3e</svg>'
        };
        for (const [name, svg] of Object.entries(lookalikeVectors)) {
            it('does not turn look-alike characters into markup: ' + name, async function () {
                const bytes = await getSvgPreviewBytes(svg),
                    latin1 = Buffer.from(bytes).toString('latin1');
                assert.ok(
                    !/<\/?script/i.test(latin1),
                    'sanitized SVG preview must not contain script tags, got: ' + latin1
                );
            });
        }

        it('UTF-8 encodes the sanitized SVG without loss', async function () {
            await fc.assert(fc.asyncProperty(
                fc.string({unit: 'binary'}),
                async function (text) {
                    // escape markup, so the text ends up as a single text node
                    const escaped = text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;'),
                        svg = '<svg xmlns="http://www.w3.org/2000/svg"><text>' + escaped + '</text></svg>',
                        // lone surrogates can't be UTF-8 encoded, so normalize the input first
                        decodedSvg = Buffer.from(svg, 'utf8').toString('utf8'),
                        expected = DOMPurify.sanitize(decodedSvg, purifySvgConfig),
                        bytes = await getSvgPreviewBytes(svg),
                        actual = new TextDecoder('utf-8', {fatal: true}).decode(bytes);
                    PrivateBin.AttachmentViewer.removeAttachment();
                    return expected === actual;
                }
            ),
            {numRuns: 100});
        });

        it('previews SVGs using a data URL, not a same-origin blob URL', function () {
            const svg = '<svg xmlns="http://www.w3.org/2000/svg"><text>Gotcha</text></svg>',
                blobs = getAttachmentBlobs(
                    'data:image/svg+xml;base64,' + Buffer.from(svg, 'utf8').toString('base64')
                ),
                images = document.querySelectorAll('#attachmentPreview img');
            assert.ok(
                !blobs.some(blob => blob.type === 'image/svg+xml'),
                'creates no SVG blob'
            );
            assert.strictEqual(images.length, 1, 'shows one SVG preview');
            assert.ok(
                images[0].getAttribute('src').startsWith('data:image/svg+xml'),
                'previews the SVG using a data URL'
            );
            assert.strictEqual(
                Buffer.from(readDataUrl(images[0].getAttribute('src'))).toString('utf8'),
                svg,
                'previews the sanitized SVG'
            );
        });

        it('does not preview SVGs that are not valid UTF-8', async function () {
            // 0xC4 followed by 0x3C ("<") is not a valid UTF-8 sequence
            const svg = Buffer.concat([
                    Buffer.from('<svg xmlns="http://www.w3.org/2000/svg">', 'utf8'),
                    Buffer.from([0xc4, 0x3c]),
                    Buffer.from('</svg>', 'utf8')
                ]),
                blobs = getAttachmentBlobs(
                    'data:image/svg+xml;base64,' + svg.toString('base64')
                );
            assert.ok(
                !blobs.some(blob => blob.type === 'image/svg+xml'),
                'creates no SVG blob for preview'
            );
            assert.strictEqual(
                document.querySelectorAll('#attachmentPreview img').length, 0,
                'shows no preview'
            );
            assert.strictEqual(
                blobs[blobs.length - 1].type, 'application/octet-stream',
                'offers download only'
            );
            assert.deepStrictEqual(
                await readBlob(blobs[blobs.length - 1]),
                new Uint8Array(svg),
                'keeps the original bytes for download'
            );
        });

        it('keeps the bytes of attachments for download intact', async function () {
            await fc.assert(fc.asyncProperty(
                fc.constantFrom('image/png', 'application/pdf', 'application/octet-stream', 'image/svg+xml'),
                fc.uint8Array(),
                async function (mimeType, data) {
                    const blobs = getAttachmentBlobs(
                            'data:' + mimeType + ';base64,' + Buffer.from(data).toString('base64')
                        ),
                        // the first blob is used for the download link
                        bytes = await readBlob(blobs[0]);
                    PrivateBin.AttachmentViewer.removeAttachment();
                    return Buffer.from(bytes).equals(Buffer.from(data));
                }
            ),
            {numRuns: 100});
        });
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
