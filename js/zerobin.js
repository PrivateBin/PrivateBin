/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.15
 */

// Immediately start random number generator collector.
sjcl.random.startCollectors();

/**
 *  Converts a duration (in seconds) into human readable format.
 *
 *  @param int seconds
 *  @return string
 */
function secondsToHuman(seconds)
{
    if (seconds<60) { var v=Math.floor(seconds); return v+' second'+((v>1)?'s':''); }
    if (seconds<60*60) { var v=Math.floor(seconds/60); return v+' minute'+((v>1)?'s':''); }
    if (seconds<60*60*24) { var v=Math.floor(seconds/(60*60)); return v+' hour'+((v>1)?'s':''); }
    // If less than 2 months, display in days:
    if (seconds<60*60*24*60) { var v=Math.floor(seconds/(60*60*24)); return v+' day'+((v>1)?'s':''); }
    var v=Math.floor(seconds/(60*60*24*30)); return v+' month'+((v>1)?'s':'');
}

/**
 * Compress a message (deflate compression). Returns base64 encoded data.
 *
 * @param string message
 * @return base64 string data
 */
function compress(message) {
    return Base64.toBase64( RawDeflate.deflate( Base64.utob(message) ) );
}

/**
 * Decompress a message compressed with compress().
 */
function decompress(data) {
    return Base64.btou( RawDeflate.inflate( Base64.fromBase64(data) ) );
}

/**
 * Compress, then encrypt message with key.
 *
 * @param string key
 * @param string message
 * @return encrypted string data
 */
function zeroCipher(key, message) {
    return sjcl.encrypt(key,compress(message));
}
/**
 *  Decrypt message with key, then decompress.
 *
 *  @param key
 *  @param encrypted string data
 *  @return string readable message
 */
function zeroDecipher(key, data) {
    return decompress(sjcl.decrypt(key,data));
}

/**
 * @return the current script location (without search or hash part of the URL).
 *   eg. http://server.com/zero/?aaaa#bbbb --> http://server.com/zero/
 */
function scriptLocation() {
    return window.location.href.substring(0,window.location.href.length
               -window.location.search.length -window.location.hash.length);
}

/**
 * @return the paste unique identifier from the URL
 *   eg. 'c05354954c49a487'
 */
function pasteID() {
    return window.location.search.substring(1);
}

/**
 * Set text of a DOM element (required for IE)
 * This is equivalent to element.text(text)
 * @param object element : a DOM element.
 * @param string text : the text to enter.
 */
function setElementText(element, text) {
    // For IE<10.
    if ($('div#oldienotice').is(":visible")) {
        // IE<10 do not support white-space:pre-wrap; so we have to do this BIG UGLY STINKING THING.
        element.text(text.replace(/\n/ig,'{BIG_UGLY_STINKING_THING__OH_GOD_I_HATE_IE}'));
        element.html(element.text().replace(/{BIG_UGLY_STINKING_THING__OH_GOD_I_HATE_IE}/ig,"\n<br />"));
    }
    // for other (sane) browsers:
    else {
        element.text(text);
    }
}

/**
 * Show decrypted text in the display area, including discussion (if open)
 *
 * @param string key : decryption key
 * @param array comments : Array of messages to display (items = array with keys ('data','meta')
 */
function displayMessages(key, comments) {
    try { // Try to decrypt the paste.
        var cleartext = zeroDecipher(key, comments[0].data);
    } catch(err) {
        $('div#cleartext').addClass('hidden');
        $('div#prettymessage').addClass('hidden');
        $('button#clonebutton').addClass('hidden');
        showError('Could not decrypt data (Wrong key ?)');
        return;
    }
    setElementText($('div#cleartext'), cleartext);
    setElementText($('pre#prettyprint'), cleartext);
    urls2links($('div#cleartext')); // Convert URLs to clickable links.
    prettyPrint();

    // Display paste expiration.
    if (comments[0].meta.expire_date) $('div#remainingtime').removeClass('foryoureyesonly').text('This document will expire in '+secondsToHuman(comments[0].meta.remaining_time)+'.').removeClass('hidden');
    if (comments[0].meta.burnafterreading) {
        $('div#remainingtime').addClass('foryoureyesonly').text('FOR YOUR EYES ONLY.  Don\'t close this window, this message can\'t be displayed again.').removeClass('hidden');
        $('button#clonebutton').addClass('hidden'); // Discourage cloning (as it can't really be prevented).
    }

    // If the discussion is opened on this paste, display it.
    if (comments[0].meta.opendiscussion) {
        $('div#comments').html('');
        // For each comment.
        for (var i = 1; i < comments.length; i++) {
            var comment=comments[i];
            var cleartext="[Could not decrypt comment ; Wrong key ?]";
            try {
                cleartext = zeroDecipher(key, comment.data);
            } catch(err) { }
            var place = $('div#comments');
            // If parent comment exists, display below (CSS will automatically shift it right.)
            var cname = 'div#comment_'+comment.meta.parentid

            // If the element exists in page
            if ($(cname).length) {
                place = $(cname);
            }
            var divComment = $('<article><div class="comment" id="comment_' + comment.meta.commentid+'">'
                               + '<div class="commentmeta"><span class="nickname"></span><span class="commentdate"></span></div><div class="commentdata"></div>'
                               + '<button onclick="open_reply($(this),\'' + comment.meta.commentid + '\');return false;">Reply</button>'
                               + '</div></article>');
            setElementText(divComment.find('div.commentdata'), cleartext);
            // Convert URLs to clickable links in comment.
            urls2links(divComment.find('div.commentdata'));
            divComment.find('span.nickname').html('<i>(Anonymous)</i>');

            // Try to get optional nickname:
            try {
                divComment.find('span.nickname').text(zeroDecipher(key, comment.meta.nickname));
            } catch(err) { }
            divComment.find('span.commentdate').text('  ('+(new Date(comment.meta.postdate*1000).toString())+')').attr('title','CommentID: ' + comment.meta.commentid);

            // If an avatar is available, display it.
            if (comment.meta.vizhash) {
                divComment.find('span.nickname').before('<img src="' + comment.meta.vizhash + '" class="vizhash" title="Anonymous avatar (Vizhash of the IP address)" />');
            }

            place.append(divComment);
        }
        $('div#comments').append('<div class="comment"><button onclick="open_reply($(this),\'' + pasteID() + '\');return false;">Add comment</button></div>');
        $('div#discussion').removeClass('hidden');
    }
}

/**
 * Open the comment entry when clicking the "Reply" button of a comment.
 * @param object source : element which emitted the event.
 * @param string commentid = identifier of the comment we want to reply to.
 */
function open_reply(source, commentid) {
    $('div.reply').remove(); // Remove any other reply area.
    source.after('<div class="reply">'
                + '<input type="text" id="nickname" title="Optional nickname..." value="Optional nickname..." />'
                + '<textarea id="replymessage" class="replymessage" cols="80" rows="7"></textarea>'
                + '<br /><button id="replybutton" onclick="send_comment(\'' + commentid + '\');return false;">Post comment</button>'
                + '<div id="replystatus"> </div>'
                + '</div>');
    $('input#nickname').focus(function() {
        if ($(this).val() == $(this).attr('title')) {
            $(this).val('');
        }
    });
    $('textarea#replymessage').focus();
}

/**
 * Send a reply in a discussion.
 * @param string parentid : the comment identifier we want to send a reply to.
 */
function send_comment(parentid) {
    // Do not send if no data.
    if ($('textarea#replymessage').val().length==0) {
        return;
    }

    showStatus('Sending comment...', spin=true);
    var cipherdata = zeroCipher(pageKey(), $('textarea#replymessage').val());
    var ciphernickname = '';
    var nick=$('input#nickname').val();
    if (nick != '' && nick != 'Optional nickname...') {
        ciphernickname = zeroCipher(pageKey(), nick);
    }
    var data_to_send = { data:cipherdata,
                         parentid: parentid,
                         pasteid:  pasteID(),
                         nickname: ciphernickname
                       };

    $.post(scriptLocation(), data_to_send, 'json')
        .error(function() {
            showError('Comment could not be sent (serveur error or not responding).');
        })
        .success(function(data) {
            if (data.status == 0) {
                showStatus('Comment posted.');
                location.reload();
            }
            else if (data.status==1) {
                showError('Could not post comment: '+data.message);
            }
            else {
                showError('Could not post comment.');
            }
        });
    }

/**
 *  Send a new paste to server
 */
function send_data() {
    // Do not send if no data.
    if ($('textarea#message').val().length == 0) {
        return;
    }
    showStatus('Sending paste...', spin=true);
    var randomkey = sjcl.codec.base64.fromBits(sjcl.random.randomWords(8, 0), 0);
    var cipherdata = zeroCipher(randomkey, $('textarea#message').val());
    var data_to_send = { data:           cipherdata,
                         expire:         $('select#pasteExpiration').val(),
                         opendiscussion: $('input#opendiscussion').is(':checked') ? 1 : 0
                       };
    $.post(scriptLocation(), data_to_send, 'json')
        .error(function() {
            showError('Data could not be sent (serveur error or not responding).');
        })
        .success(function(data) {
            if (data.status == 0) {
                stateExistingPaste();
                var url = scriptLocation() + "?" + data.id + '#' + randomkey;
                showStatus('');
                $('div#pastelink').html('Your paste is <a href="' + url + '">' + url + '</a>').removeClass('hidden');
                setElementText($('div#cleartext'), $('textarea#message').val());
                setElementText($('pre#prettyprint'), $('textarea#message').val());
                urls2links($('div#cleartext'));
                showStatus('');
                prettyPrint();
            }
            else if (data.status==1) {
                showError('Could not create paste: '+data.message);
            }
            else {
                showError('Could not create paste.');
            }
        });
}

/**
 * Put the screen in "New paste" mode.
 */
function stateNewPaste() {
    $('button#sendbutton').removeClass('hidden');
    $('button#clonebutton').addClass('hidden');
    $('div#expiration').removeClass('hidden');
    $('div#remainingtime').addClass('hidden');
    $('div#language').addClass('hidden'); // $('#language').removeClass('hidden');
    $('input#password').addClass('hidden'); //$('#password').removeClass('hidden');
    $('div#opendisc').removeClass('hidden');
    $('button#newbutton').removeClass('hidden');
    $('div#pastelink').addClass('hidden');
    $('textarea#message').text('');
    $('textarea#message').removeClass('hidden');
    $('div#cleartext').addClass('hidden');
    $('textarea#message').focus();
    $('div#discussion').addClass('hidden');
    $('div#prettymessage').addClass('hidden');
}

/**
 * Put the screen in "Existing paste" mode.
 */
function stateExistingPaste() {
    $('button#sendbutton').addClass('hidden');

    // No "clone" for IE<10.
    if ($('div#oldienotice').is(":visible")) {
        $('button#clonebutton').addClass('hidden');
    }
    else {
        $('button#clonebutton').removeClass('hidden');
    }

    $('div#expiration').addClass('hidden');
    $('div#language').addClass('hidden');
    $('input#password').addClass('hidden');
    $('div#opendisc').addClass('hidden');
    $('button#newbutton').removeClass('hidden');
    $('div#pastelink').addClass('hidden');
    $('textarea#message').addClass('hidden');
    $('div#cleartext').addClass('hidden');
    $('div#prettymessage').removeClass('hidden');
}

/**
 * Clone the current paste.
 */
function clonePaste() {
    stateNewPaste();
    showStatus('');
    $('textarea#message').text($('div#cleartext').text());
}

/**
 * Create a new paste.
 */
function newPaste() {
    stateNewPaste();
    showStatus('');
    $('textarea#message').text('');
}

/**
 * Display an error message
 * (We use the same function for paste and reply to comments)
 */
function showError(message) {
    $('div#status').addClass('errorMessage').text(message);
    $('div#replystatus').addClass('errorMessage').text(message);
}

/**
 * Display status
 * (We use the same function for paste and reply to comments)
 *
 * @param string message : text to display
 * @param boolean spin (optional) : tell if the "spinning" animation should be displayed.
 */
function showStatus(message, spin) {
    $('div#replystatus').removeClass('errorMessage');
    $('div#replystatus').text(message);
    if (!message) {
        $('div#status').html(' ');
        return;
    }
    if (message == '') {
        $('div#status').html(' ');
        return;
    }
    $('div#status').removeClass('errorMessage');
    $('div#status').text(message);
    if (spin) {
        var img = '<img src="img/busy.gif" style="width:16px;height:9px;margin:0px 4px 0px 0px;" />';
        $('div#status').prepend(img);
        $('div#replystatus').prepend(img);
    }
}

/**
 * Convert URLs to clickable links.
 * URLs to handle:
 * <code>
 *     magnet:?xt.1=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C&xt.2=urn:sha1:TXGCZQTH26NL6OUQAJJPFALHG2LTGBC7
 *     http://localhost:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
 *     http://user:password@localhost:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
 * </code>
 *
 * @param object element : a jQuery DOM element.
 * @FIXME: add ppa & apt links.
 */
function urls2links(element) {
    var re = /((http|https|ftp):\/\/[\w?=&.\/-;#@~%+-]+(?![\w\s?&.\/;#~%"=-]*>))/ig;
    element.html(element.html().replace(re,'<a href="$1" rel="nofollow">$1</a>'));
    var re = /((magnet):[\w?=&.\/-;#@~%+-]+)/ig;
    element.html(element.html().replace(re,'<a href="$1">$1</a>'));
}

/**
 * Return the deciphering key stored in anchor part of the URL
 */
function pageKey() {
    var key = window.location.hash.substring(1);  // Get key

    // Some stupid web 2.0 services and redirectors add data AFTER the anchor
    // (such as &utm_source=...).
    // We will strip any additional data.

    // First, strip everything after the equal sign (=) which signals end of base64 string.
    i = key.indexOf('='); if (i>-1) { key = key.substring(0,i+1); }

    // If the equal sign was not present, some parameters may remain:
    i = key.indexOf('&'); if (i>-1) { key = key.substring(0,i); }

    // Then add trailing equal sign if it's missing
    if (key.charAt(key.length-1)!=='=') key+='=';

    return key;
}

$(function() {
    // hide "no javascript" message
    $('#noscript').hide();

    $('select#pasteExpiration').change(function() {
        if ($(this).val() == 'burn') {
            $('div#opendisc').addClass('buttondisabled');
            $('input#opendiscussion').attr('disabled',true);
        }
        else {
            $('div#opendisc').removeClass('buttondisabled');
            $('input#opendiscussion').removeAttr('disabled');
        }
    });

    // Display an existing paste
    if ($('div#cipherdata').text().length > 1) {
        // Missing decryption key in URL ?
        if (window.location.hash.length == 0) {
            showError('Cannot decrypt paste: Decryption key missing in URL (Did you use a redirector or an URL shortener which strips part of the URL ?)');
            return;
        }

        // List of messages to display
        var messages = jQuery.parseJSON($('div#cipherdata').text());

        // Show proper elements on screen.
        stateExistingPaste();

        displayMessages(pageKey(), messages);
    }
    // Display error message from php code.
    else if ($('div#errormessage').text().length>1) {
        showError($('div#errormessage').text());
    }
    // Create a new paste.
    else {
        newPaste();
    }
});
