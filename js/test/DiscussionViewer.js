'use strict';
var common = require('../common');

describe('DiscussionViewer', function () {
    describe('handleNotification, addComment, finishDiscussion, prepareNewDiscussion, getReplyMessage, getReplyNickname, getReplyCommentId & highlightComment', function () {
        this.timeout(30000);
        before(function () {
            cleanup();
        });

        jsc.property(
            'displays & hides comments as requested',
            jsc.array(
                jsc.record({
                    id: jsc.nearray(common.jscAlnumString()),
                    parentid: jsc.nearray(common.jscAlnumString()),
                    data: jsc.string,
                    meta: jsc.record({
                        nickname: jsc.string,
                        postdate: jsc.nat,
                        vizhash: jsc.string
                    })
                })
            ),
            function (comments) {
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
                $.PrivateBin.DiscussionViewer.init();
                results.push(
                    !$('#discussion').hasClass('hidden')
                );
                $.PrivateBin.DiscussionViewer.prepareNewDiscussion();
                results.push(
                    $('#discussion').hasClass('hidden')
                );
                comments.forEach(function (originalComment) {
                    var comment = {
                        id: originalComment.id.join(''),
                        parentid: originalComment.parentid.join(''),
                        data: originalComment.data,
                        meta: originalComment.meta
                    }
                    $.PrivateBin.DiscussionViewer.addComment(comment, comment.data, comment.meta.nickname);
                });
                results.push(
                    $('#discussion').hasClass('hidden')
                );
                clean();
                return results.every(element => element);
            }
        );
    });
});

