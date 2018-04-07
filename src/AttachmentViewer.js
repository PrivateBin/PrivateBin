import $ from 'jquery'

/**
 * (view) Show attachment and preview if possible
 *
 * @name   AttachmentViewer
 * @class
 */

var $attachmentLink,
    $attachmentPreview,
    $attachment;

var attachmentHasPreview = false;

/**
 * sets the attachment but does not yet show it
 *
 * @name   AttachmentViewer.setAttachment
 * @function
 * @param {string} attachmentData - base64-encoded data of file
 * @param {string} fileName - optional, file name
 */
export function setAttachment(attachmentData, fileName)
{
    var imagePrefix = 'data:image/';

    $attachmentLink.attr('href', attachmentData);
    if (typeof fileName !== 'undefined') {
        $attachmentLink.attr('download', fileName);
    }

    // if the attachment is an image, display it
    if (attachmentData.substring(0, imagePrefix.length) === imagePrefix) {
        $attachmentPreview.html(
            $(document.createElement('img'))
                .attr('src', attachmentData)
                .attr('class', 'img-thumbnail')
        );
        attachmentHasPreview = true;
    }
}

/**
 * displays the attachment
 *
 * @name AttachmentViewer.showAttachment
 * @function
 */
export function showAttachment()
{
    $attachment.removeClass('hidden');

    if (attachmentHasPreview) {
        $attachmentPreview.removeClass('hidden');
    }
}

/**
 * removes the attachment
 *
 * This automatically hides the attachment containers to, to
 * prevent an inconsistent display.
 *
 * @name AttachmentViewer.removeAttachment
 * @function
 */
export function removeAttachment()
{
    hideAttachment();
    hideAttachmentPreview();
    $attachmentLink.prop('href', '');
    $attachmentLink.prop('download', '');
    $attachmentPreview.html('');
}

/**
 * hides the attachment
 *
 * This will not hide the preview (see AttachmentViewer.hideAttachmentPreview
 * for that) nor will it hide the attachment link if it was moved somewhere
 * else (see AttachmentViewer.moveAttachmentTo).
 *
 * @name AttachmentViewer.hideAttachment
 * @function
 */
export function hideAttachment()
{
    $attachment.addClass('hidden');
}

/**
 * hides the attachment preview
 *
 * @name AttachmentViewer.hideAttachmentPreview
 * @function
 */
export function hideAttachmentPreview()
{
    $attachmentPreview.addClass('hidden');
}

/**
 * checks if there is an attachment
 *
 * @name   AttachmentViewer.hasAttachment
 * @function
 */
export function hasAttachment()
{
    var link = $attachmentLink.prop('href');
    return typeof link !== 'undefined' && link !== '';
}

/**
 * return the attachment
 *
 * @name   AttachmentViewer.getAttachment
 * @function
 * @returns {array}
 */
export function getAttachment()
{
    return [
        $attachmentLink.prop('href'),
        $attachmentLink.prop('download')
    ];
}

/**
 * moves the attachment link to another element
 *
 * It is advisable to hide the attachment afterwards (AttachmentViewer.hideAttachment)
 *
 * @name   AttachmentViewer.moveAttachmentTo
 * @function
 * @param {jQuery} $element - the wrapper/container element where this should be moved to
 * @param {string} label - the text to show (%s will be replaced with the file name), will automatically be translated
 */
export function moveAttachmentTo($element, label)
{
    // move elemement to new place
    $attachmentLink.appendTo($element);

    // update text
    I18n._($attachmentLink, label, $attachmentLink.attr('download'));
}

/**
 * initiate
 *
 * preloads jQuery elements
 *
 * @name   AttachmentViewer.init
 * @function
 */
export function init()
{
    $attachment = $('#attachment');
    $attachmentLink = $('#attachment a');
    $attachmentPreview = $('#attachmentPreview');
    attachmentHasPreview = false;
}
