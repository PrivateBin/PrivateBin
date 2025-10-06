'use strict';
const common = require('../common');

describe('AttachmentViewer', function () {
    describe('setAttachment, showAttachment, removeAttachment, hideAttachment, hideAttachmentPreview, hasAttachment, getAttachment & moveAttachmentTo', function () {
        this.timeout(30000);

        jsc.property(
            'displays & hides data as requested',
            common.jscMimeTypes(),
            'string',
            'string',
            'string',
            'string',
             // eslint-disable-next-line complexity
            function (mimeType, rawdata, filename, prefix, postfix) {
                let clean = jsdom(),
                    data = 'data:' + mimeType + ';base64,' + common.btoa(rawdata),
                    mimePrefix = mimeType.substring(0, 6),
                    previewSupported = (
                        mimePrefix === 'image/' ||
                        mimePrefix === 'audio/' ||
                        mimePrefix === 'video/' ||
                        mimeType.match(/\/pdf/i)
                    ),
                    results = [],
                    result = '';
                // text node of attachment will truncate at null byte
                if (filename === '\u0000') {
                    filename = '';
                }
                prefix  = prefix.replace(/%(s|d)/g, '%%');
                postfix = postfix.replace(/%(s|d)/g, '%%').replace(/<|>/g, '');
                $('body').html(
                    '<div id="attachmentPreview" class="col-md-12 text-center hidden"></div>' +
                    '<div id="attachment" class="hidden"></div>' +
                    '<div id="templates">' +
                        '<div id="attachmenttemplate" role="alert" class="attachment hidden alert alert-info">' +
                            '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>' +
                            '<a class="alert-link">Download attachment</a>' +
                        '</div>' +
                    '</div>'
                );
                // mock createObjectURL for jsDOM
                if (typeof window.URL.createObjectURL === 'undefined') {
                    Object.defineProperty(
                        window.URL,
                        'createObjectURL',
                        {value: function(blob) {
                            return 'blob:' + location.origin + '/1b9d6bcd-bbfd-4b2d-9b5d-ab8dfbbd4bed';
                        }}
                    )
                }
                $.PrivateBin.AttachmentViewer.init();
                $.PrivateBin.Model.init();
                results.push(
                    !$.PrivateBin.AttachmentViewer.hasAttachment() &&
                    $('#attachment').hasClass('hidden') &&
                    $('#attachment').children().length === 0 &&
                    $('#attachmenttemplate').hasClass('hidden') &&
                    $('#attachmentPreview').hasClass('hidden')
                );
                global.atob = common.atob;
                if (filename.length) {
                    $.PrivateBin.AttachmentViewer.setAttachment(data, filename);
                } else {
                    $.PrivateBin.AttachmentViewer.setAttachment(data);
                }
                // // beyond this point we will get the blob URL instead of the data
                data = window.URL.createObjectURL(data);
                const attachment = $.PrivateBin.AttachmentViewer.getAttachments();
                results.push(
                    $.PrivateBin.AttachmentViewer.hasAttachment() &&
                    $('#attachment').hasClass('hidden') &&
                    $('#attachment').children().length > 0 &&
                    $('#attachmentPreview').hasClass('hidden') &&
                    attachment[0][0] === data &&
                    attachment[0][1] === filename
                );
                $.PrivateBin.AttachmentViewer.showAttachment();
                results.push(
                    !$('#attachment').hasClass('hidden') &&
                    $('#attachment').children().length > 0 &&
                    (previewSupported ? !$('#attachmentPreview').hasClass('hidden') : $('#attachmentPreview').hasClass('hidden'))
                );
                $.PrivateBin.AttachmentViewer.hideAttachment();
                results.push(
                    $('#attachment').hasClass('hidden') &&
                    (previewSupported ? !$('#attachmentPreview').hasClass('hidden') : $('#attachmentPreview').hasClass('hidden'))
                );
                if (previewSupported) {
                    $.PrivateBin.AttachmentViewer.hideAttachmentPreview();
                    results.push($('#attachmentPreview').hasClass('hidden'));
                }
                $.PrivateBin.AttachmentViewer.showAttachment();
                results.push(
                    !$('#attachment').hasClass('hidden') &&
                    (previewSupported ? !$('#attachmentPreview').hasClass('hidden') : $('#attachmentPreview').hasClass('hidden'))
                );
                let element = $('<div>');
                $.PrivateBin.AttachmentViewer.moveAttachmentTo(element, attachment[0], prefix + '%s' + postfix);
                // messageIDs with links get a relaxed treatment
                if (prefix.indexOf('<a') === -1 && postfix.indexOf('<a') === -1) {
                    result = $('<textarea>').text((prefix + filename + postfix)).text();
                } else {
                    result = DOMPurify.sanitize(
                        prefix + $.PrivateBin.Helper.htmlEntities(filename) + postfix, {
                            ALLOWED_TAGS: ['a', 'i', 'span'],
                            ALLOWED_ATTR: ['href', 'id']
                        }
                    );
                }
                if (filename.length) {
                    results.push(
                        element.find('a')[0].href === data &&
                        element.find('a')[0].getAttribute('download') === filename &&
                        element.find('a')[0].text === result
                    );
                } else {
                    results.push(element.find('a')[0].href === data);
                }
                $.PrivateBin.AttachmentViewer.removeAttachment();
                results.push(
                    $('#attachment').hasClass('hidden') &&
                    $('#attachment').children().length === 0 &&
                    $('#attachmentPreview').hasClass('hidden')
                );
                clean();
                return results.every(element => element);
            }
        );
    });
});
