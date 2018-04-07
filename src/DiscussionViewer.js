import $ from 'jquery'

/**
 * (view) Shows discussion thread and handles replies
 *
 * @name   DiscussionViewer
 * @class
 */

var $commentTail,
    $discussion,
    $reply,
    $replyMessage,
    $replyNickname,
    $replyStatus,
    $commentContainer;

var replyCommentId;

/**
 * initializes the templates
 *
 * @name   DiscussionViewer.initTemplates
 * @private
 * @function
 */
function initTemplates()
{
    $reply = Model.getTemplate('reply');
    $replyMessage = $reply.find('#replymessage');
    $replyNickname = $reply.find('#nickname');
    $replyStatus = $reply.find('#replystatus');

    // cache jQuery elements
    $commentTail = Model.getTemplate('commenttail');
}

/**
 * open the comment entry when clicking the "Reply" button of a comment
 *
 * @name   DiscussionViewer.openReply
 * @private
 * @function
 * @param  {Event} event
 */
function openReply(event)
{
    var $source = $(event.target);

    // clear input
    $replyMessage.val('');
    $replyNickname.val('');

    // get comment id from source element
    replyCommentId = $source.parent().prop('id').split('_')[1];

    // move to correct position
    $source.after($reply);

    // show
    $reply.removeClass('hidden');
    $replyMessage.focus();

    event.preventDefault();
}

/**
 * custom handler for displaying notifications in own status message area
 *
 * @name   DiscussionViewer.handleNotification
 * @function
 * @param  {string} alertType
 * @return {bool|jQuery}
 */
export function handleNotification(alertType)
{
    // ignore loading messages
    if (alertType === 'loading') {
        return false;
    }

    if (alertType === 'danger') {
        $replyStatus.removeClass('alert-info');
        $replyStatus.addClass('alert-danger');
        $replyStatus.find(':first').removeClass('glyphicon-alert');
        $replyStatus.find(':first').addClass('glyphicon-info-sign');
    } else {
        $replyStatus.removeClass('alert-danger');
        $replyStatus.addClass('alert-info');
        $replyStatus.find(':first').removeClass('glyphicon-info-sign');
        $replyStatus.find(':first').addClass('glyphicon-alert');
    }

    return $replyStatus;
}

/**
 * adds another comment
 *
 * @name   DiscussionViewer.addComment
 * @function
 * @param {object} comment
 * @param {string} commentText
 * @param {string} nickname
 */
export function addComment(comment, commentText, nickname)
{
    if (commentText === '') {
        commentText = 'comment decryption failed';
    }

    // create new comment based on template
    var $commentEntry = Model.getTemplate('comment');
    $commentEntry.prop('id', 'comment_' + comment.id);
    var $commentEntryData = $commentEntry.find('div.commentdata');

    // set & parse text
    import('dompurify').then(function (module) {
        var DOMPurify = module.default;

        $commentEntryData.html(
            DOMPurify.sanitize(
                Helper.urls2links(commentText)
            )
        );
    });

    // set nickname
    if (nickname.length > 0) {
        $commentEntry.find('span.nickname').text(nickname);
    } else {
        $commentEntry.find('span.nickname').html('<i></i>');
        I18n._($commentEntry.find('span.nickname i'), 'Anonymous');
    }

    // set date
    $commentEntry.find('span.commentdate')
              .text(' (' + (new Date(comment.meta.postdate * 1000).toLocaleString()) + ')')
              .attr('title', 'CommentID: ' + comment.id);

    // if an avatar is available, display it
    if (comment.meta.vizhash) {
        $commentEntry.find('span.nickname')
                     .before(
                        '<img src="' + comment.meta.vizhash + '" class="vizhash" /> '
                     );
        $(document).on('languageLoaded', function () {
            $commentEntry.find('img.vizhash')
                         .prop('title', I18n._('Avatar generated from IP address'));
        });
    }

    // starting point (default value/fallback)
    var $place = $commentContainer;

    // if parent comment exists
    var $parentComment = $('#comment_' + comment.parentid);
    if ($parentComment.length) {
        // use parent as position for new comment, so it is shifted
        // to the right
        $place = $parentComment;
    }

    // finally append comment
    $place.append($commentEntry);
}

/**
 * finishes the discussion area after last comment
 *
 * @name   DiscussionViewer.finishDiscussion
 * @function
 */
export function finishDiscussion()
{
    // add 'add new comment' area
    $commentContainer.append($commentTail);

    // show discussions
    $discussion.removeClass('hidden');
}

/**
 * removes the old discussion and prepares everything for creating a new
 * one.
 *
 * @name   DiscussionViewer.prepareNewDiscussion
 * @function
 */
export function prepareNewDiscussion()
{
    $commentContainer.html('');
    $discussion.addClass('hidden');

    // (re-)init templates
    initTemplates();
}

/**
 * returns the users message from the reply form
 *
 * @name   DiscussionViewer.getReplyMessage
 * @function
 * @return {String}
 */
export function getReplyMessage()
{
    return $replyMessage.val();
}

/**
 * returns the users nickname (if any) from the reply form
 *
 * @name   DiscussionViewer.getReplyNickname
 * @function
 * @return {String}
 */
export function getReplyNickname()
{
    return $replyNickname.val();
}

/**
 * returns the id of the parent comment the user is replying to
 *
 * @name   DiscussionViewer.getReplyCommentId
 * @function
 * @return {int|undefined}
 */
export function getReplyCommentId()
{
    return replyCommentId;
}

/**
 * highlights a specific comment and scrolls to it if necessary
 *
 * @name   DiscussionViewer.highlightComment
 * @function
 * @param {string} commentId
 * @param {bool} fadeOut - whether to fade out the comment
 */
export function highlightComment(commentId, fadeOut)
{
    var $comment = $('#comment_' + commentId);
    // in case comment does not exist, cancel
    if ($comment.length === 0) {
        return;
    }

    var highlightComment = function () {
        $comment.addClass('highlight');
        if (fadeOut === true) {
            setTimeout(function () {
                $comment.removeClass('highlight');
            }, 300);
        }
    };

    if (UiHelper.isVisible($comment)) {
        return highlightComment();
    }

    UiHelper.scrollTo($comment, 100, 'swing', highlightComment);
}

/**
 * initiate
 *
 * preloads jQuery elements
 *
 * @name   DiscussionViewer.init
 * @function
 */
export function init()
{
    // bind events to templates (so they are later cloned)
    $('#commenttailtemplate, #commenttemplate').find('button').on('click', openReply);
    $('#replytemplate').find('button').on('click', PasteEncrypter.sendComment);

    $commentContainer = $('#commentcontainer');
    $discussion = $('#discussion');
}
