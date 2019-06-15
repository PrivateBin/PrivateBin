'use strict';
var common = require('../common');

describe('AttachmentViewer', function () {
    describe('setAttachment, showAttachment, removeAttachment, hideAttachment, hideAttachmentPreview, hasAttachment, getAttachment & moveAttachmentTo', function () {
        this.timeout(30000);
        before(function () {
            cleanup();
        });

        jsc.property(
            'displays & hides data as requested',
            common.jscMimeTypes(),
            'string',
            'string',
            'string',
            'string',
            function (mimeType, rawdata, filename, prefix, postfix) {
                var clean = jsdom(),
                    data = 'data:' + mimeType + ';base64,' + btoa(rawdata),
                    previewSupported = (
                        mimeType.substring(0, 6) === 'image/' ||
                        mimeType.substring(0, 6) === 'audio/' ||
                        mimeType.substring(0, 6) === 'video/' ||
                        mimeType.match(/\/pdf/i)
                    ),
                    results = [];
                prefix = prefix.replace(/%(s|d)/g, '%%');
                postfix = postfix.replace(/%(s|d)/g, '%%');
                $('body').html(
                    '<div id="attachment" role="alert" class="hidden alert ' +
                    'alert-info"><span class="glyphicon glyphicon-download-' +
                    'alt" aria-hidden="true"></span> <a class="alert-link">' +
                    'Download attachment</a></div><div id="attachmentPrevie' +
                    'w" class="hidden"></div>'
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
                results.push(
                    !$.PrivateBin.AttachmentViewer.hasAttachment() &&
                    $('#attachment').hasClass('hidden') &&
                    $('#attachmentPreview').hasClass('hidden')
                );
                if (filename.length) {
                    $.PrivateBin.AttachmentViewer.setAttachment(data, filename);
                } else {
                    $.PrivateBin.AttachmentViewer.setAttachment(data);
                }
                // beyond this point we will get the blob URL instead of the data
                data = window.URL.createObjectURL(data);
                var attachment = $.PrivateBin.AttachmentViewer.getAttachment();
                results.push(
                    $.PrivateBin.AttachmentViewer.hasAttachment() &&
                    $('#attachment').hasClass('hidden') &&
                    $('#attachmentPreview').hasClass('hidden') &&
                    attachment[0] === data &&
                    attachment[1] === filename
                );
                $.PrivateBin.AttachmentViewer.showAttachment();
                results.push(
                    !$('#attachment').hasClass('hidden') &&
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
                var element = $('<div></div>');
                $.PrivateBin.AttachmentViewer.moveAttachmentTo(element, prefix + '%s' + postfix);
                if (filename.length) {
                    results.push(
                        element.children()[0].href === data &&
                        element.children()[0].getAttribute('download') === filename &&
                        element.children()[0].text === prefix + filename + postfix
                    );
                } else {
                    results.push(element.children()[0].href === data);
                }
                $.PrivateBin.AttachmentViewer.removeAttachment();
                results.push(
                    $('#attachment').hasClass('hidden') &&
                    $('#attachmentPreview').hasClass('hidden')
                );
                clean();
                return results.every(element => element);
            }
        );
    });
});

