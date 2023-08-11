'use strict';
var common = require('../common');

describe('DiscussionViewer', function () {
    describe('handleNotification, prepareNewDiscussion, addComment, finishDiscussion, getReplyMessage, getReplyNickname, getReplyCommentId & highlightComment', function () {
        this.timeout(30000);

        jsc.property(
            'displays & hides comments as requested',
            jsc.array(
                jsc.record({
                    idArray: jsc.nearray(common.jscAlnumString()),
                    parentidArray: jsc.nearray(common.jscAlnumString()),
                    data: jsc.string,
                    meta: jsc.record({
                        nickname: jsc.string,
                        postdate: jsc.nat,
                        vizhash: jsc.string
                    })
                })
            ),
            'nat',
            'bool',
            'string',
            'string',
            jsc.elements(['loading', 'danger', 'other']),
            'nestring',
            function (comments, commentKey, fadeOut, nickname, message, alertType, alert) {
                var clean = jsdom(),
                    results = [];
                $('body').html(
                    '<div id="discussion"><h4>Discussion</h4>' +
                    '<div id="commentcontainer"></div></div><div id="templates">' +
                    '<article id="commenttemplate" class="comment">' +
                    '<div class="commentmeta"><span class="nickname">name</span>' +
                    '<span class="commentdate">0000-00-00</span></div>' +
                    '<div class="commentdata">c</div>' +
                    '<button class="btn btn-default btn-sm">Reply</button>' +
                    '</article><p id="commenttailtemplate" class="comment">' +
                    '<button class="btn btn-default btn-sm">Add comment</button>' +
                    '</p><div id="replytemplate" class="reply hidden">' +
                    '<input type="text" id="nickname" class="form-control" ' +
                    'title="Optional nickname…" placeholder="Optional ' +
                    'nickname…" /><textarea id="replymessage" ' +
                    'class="replymessage form-control" cols="80" rows="7">' +
                    '</textarea><br /><div id="replystatus" role="alert" ' +
                    'class="statusmessage hidden alert"><span class="glyphicon" ' +
                    'aria-hidden="true"></span> </div><button id="replybutton" ' +
                    'class="btn btn-default btn-sm">Post comment</button></div></div>'
                );
                $.PrivateBin.Model.init();
                $.PrivateBin.DiscussionViewer.init();
                results.push(
                    !$('#discussion').hasClass('hidden')
                );
                $.PrivateBin.DiscussionViewer.prepareNewDiscussion();
                results.push(
                    $('#discussion').hasClass('hidden')
                );
                comments.forEach(function (comment) {
                    comment.id = comment.idArray.join('');
                    comment.parentid = comment.parentidArray.join('');
                    $.PrivateBin.DiscussionViewer.addComment($.PrivateBin.Helper.CommentFactory(comment), comment.data, comment.meta.nickname);
                });
                results.push(
                    $('#discussion').hasClass('hidden')
                );
                $.PrivateBin.DiscussionViewer.finishDiscussion();
                results.push(
                    !$('#discussion').hasClass('hidden') &&
                    comments.length + 1 >= $('#commentcontainer').children().length
                );
                if (comments.length > 0) {
                    if (commentKey >= comments.length) {
                        commentKey = commentKey % comments.length;
                    }
                    $.PrivateBin.DiscussionViewer.highlightComment(comments[commentKey].id, fadeOut);
                    results.push(
                        $('#comment_' + comments[commentKey].id).hasClass('highlight')
                    );
                }
                $('#commentcontainer').find('button')[0].click();
                results.push(
                    !$('#reply').hasClass('hidden')
                );
                $('#reply #nickname').val(nickname);
                $('#reply #replymessage').val(message);
                $.PrivateBin.DiscussionViewer.getReplyCommentId();
                results.push(
                    $.PrivateBin.DiscussionViewer.getReplyNickname() === $('#reply #nickname').val() &&
                    $.PrivateBin.DiscussionViewer.getReplyMessage() === $('#reply #replymessage').val()
                );
                var notificationResult = $.PrivateBin.DiscussionViewer.handleNotification(alertType === 'other' ? alert : alertType);
                if (alertType === 'loading') {
                    results.push(notificationResult === false);
                } else {
                    results.push(
                        alertType === 'danger' ? (
                            notificationResult.hasClass('alert-danger') &&
                            !notificationResult.hasClass('alert-info')
                        ) : (
                            !notificationResult.hasClass('alert-danger') &&
                            notificationResult.hasClass('alert-info')
                        )
                    );
                }
                clean();
                return results.every(element => element);
            }
        );
    });
});
