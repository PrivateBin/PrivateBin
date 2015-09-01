/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.19
 */

// Immediately start random number generator collector.
sjcl.random.startCollectors();

/**
 * Converts a duration (in seconds) into human readable format.
 *
 * @param int seconds
 * @return string
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
 * Converts an associative array to an encoded string
 * for appending to the anchor.
 *
 * @param object associative_array Object to be serialized
 * @return string
 */
function hashToParameterString(associativeArray)
{
  var parameterString = "";
  for (key in associativeArray)
  {
    if( parameterString === "" )
    {
      parameterString = encodeURIComponent(key);
      parameterString += "=" + encodeURIComponent(associativeArray[key]);
    } else {
      parameterString += "&" + encodeURIComponent(key);
      parameterString += "=" + encodeURIComponent(associativeArray[key]);
    }
  }
  //padding for URL shorteners
  parameterString += "&p=p";

  return parameterString;
}

/**
 * Converts a string to an associative array.
 *
 * @param string parameter_string String containing parameters
 * @return object
 */
function parameterStringToHash(parameterString)
{
  var parameterHash = {};
  var parameterArray = parameterString.split("&");
  for (var i = 0; i < parameterArray.length; i++) {
    //var currentParamterString = decodeURIComponent(parameterArray[i]);
    var pair = parameterArray[i].split("=");
    var key = decodeURIComponent(pair[0]);
    var value = decodeURIComponent(pair[1]);
    parameterHash[key] = value;
  }

  return parameterHash;
}

/**
 * Get an associative array of the parameters found in the anchor
 *
 * @return object
 */
function getParameterHash()
{
  var hashIndex = window.location.href.indexOf("#");
  if (hashIndex >= 0) {
    return parameterStringToHash(window.location.href.substring(hashIndex + 1));
  } else {
    return {};
  }
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
    if ($('#passwordinput').val().length == 0) {
        return sjcl.encrypt(key, compress(message));
    }
    return sjcl.encrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash($("#passwordinput").val())), compress(message));
}

/**
 * Decrypt message with key, then decompress.
 *
 * @param string key
 * @param encrypted string data
 * @return string readable message
 */
function zeroDecipher(key, data) {
    if (data != undefined) {
        try {
            return decompress(sjcl.decrypt(key, data));
        } catch (err) {
            try {
                if ($('#passwordinput').val().length > 0) {
                    password = $('#passwordinput').val();
                } else {
                    password = prompt("Please enter the password for this paste:", "");
                    if (password == null) return null;
                }
                data = decompress(sjcl.decrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), data));
                $('#passwordinput').val(password);
                return data;
            } catch (err) {
                return zeroDecipher(key, data);
            }
        }
    }
}

/**
 * Get the current script location (without search or hash part of the URL).
 * eg. http://server.com/zero/?aaaa#bbbb --> http://server.com/zero/
 * 
 * @return string current script location
 */
function scriptLocation() {
  var scriptLocation = window.location.href.substring(0,window.location.href.length
    - window.location.search.length - window.location.hash.length);
  var hashIndex = scriptLocation.indexOf("#");
  if (hashIndex !== -1) {
    scriptLocation = scriptLocation.substring(0, hashIndex);
  }
  return scriptLocation;
}

/**
 * Get the pastes unique identifier from the URL
 * eg. http://server.com/zero/?c05354954c49a487#xxx --> c05354954c49a487
 * 
 * @return string unique identifier
 */
function pasteID() {
    return window.location.search.substring(1);
}

/**
 * Convert all applicable characters to HTML entities
 * 
 * @param string str
 * @returns string encoded string
 */
function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
/**
 * Set text of a DOM element (required for IE)
 * This is equivalent to element.text(text)
 * 
 * @param object element : a DOM element.
 * @param string text : the text to enter.
 */
function setElementText(element, text) {
    // For IE<10.
    if ($('#oldienotice').is(":visible")) {
        // IE<10 does not support white-space:pre-wrap; so we have to do this BIG UGLY STINKING THING.
        var html = htmlEntities(text).replace(/\n/ig,"\r\n<br>");
        element.html('<pre>'+html+'</pre>');
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
        if (cleartext == null) throw "password prompt canceled";
    } catch(err) {
        $('#cleartext').addClass('hidden');
        $('#prettymessage').addClass('hidden');
        $('#clonebutton').addClass('hidden');
        showError('Could not decrypt data (Wrong key ?)');
        return;
    }
    setElementText($('#cleartext'), cleartext);
    setElementText($('#prettyprint'), cleartext);
    // Convert URLs to clickable links.
    urls2links($('#cleartext'));
    urls2links($('#prettyprint'));
    if (typeof prettyPrint == 'function') prettyPrint();

    // Display paste expiration.
    if (comments[0].meta.expire_date) $('#remainingtime').removeClass('foryoureyesonly').text('This document will expire in '+secondsToHuman(comments[0].meta.remaining_time)+'.').removeClass('hidden');
    if (comments[0].meta.burnafterreading) {
        $.get(scriptLocation() + "?pasteid=" + pasteID() + '&deletetoken=burnafterreading', 'json')
        .fail(function() {
            showError('Could not delete the paste, it was not stored in burn after reading mode.');
        });
        $('#remainingtime').addClass('foryoureyesonly').text('FOR YOUR EYES ONLY.  Don\'t close this window, this message can\'t be displayed again.').removeClass('hidden');
        $('#clonebutton').addClass('hidden'); // Discourage cloning (as it can't really be prevented).
    }

    // If the discussion is opened on this paste, display it.
    if (comments[0].meta.opendiscussion) {
        $('#comments').html('');
        // iterate over comments
        for (var i = 1; i < comments.length; i++) {
            var comment=comments[i];
            var cleartext="[Could not decrypt comment ; Wrong key ?]";
            try {
                cleartext = zeroDecipher(key, comment.data);
            } catch(err) { }
            var place = $('#comments');
            // If parent comment exists, display below (CSS will automatically shift it right.)
            var cname = '#comment_'+comment.meta.parentid;

            // If the element exists in page
            if ($(cname).length) {
                place = $(cname);
            }
            var divComment = $('<article><div class="comment" id="comment_' + comment.meta.commentid+'">'
                               + '<div class="commentmeta"><span class="nickname"></span><span class="commentdate"></span></div><div class="commentdata"></div>'
                               + '<button onclick="open_reply($(this),\'' + comment.meta.commentid + '\');return false;" class="btn btn-default">Reply</button>'
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
                divComment.find('span.nickname').before('<img src="' + comment.meta.vizhash + '" class="vizhash" title="Anonymous avatar (Vizhash of the IP address)" /> ');
            }

            place.append(divComment);
        }
        $('#comments').append('<div class="comment"><button onclick="open_reply($(this),\'' + pasteID() + '\');return false;" class="btn btn-default">Add comment</button></div>');
        $('#discussion').removeClass('hidden');
    }
}

/**
 * Open the comment entry when clicking the "Reply" button of a comment.
 * 
 * @param object source : element which emitted the event.
 * @param string commentid = identifier of the comment we want to reply to.
 */
function open_reply(source, commentid) {
    $('div.reply').remove(); // Remove any other reply area.
    source.after('<div class="reply">'
                + '<input type="text" id="nickname" class="form-control" title="Optional nickname..." value="Optional nickname..." />'
                + '<textarea id="replymessage" class="replymessage form-control" cols="80" rows="7"></textarea>'
                + '<br /><button id="replybutton" onclick="send_comment(\'' + commentid + '\');return false;" class="btn btn-default">Post comment</button>'
                + '<div id="replystatus"> </div>'
                + '</div>');
    $('#nickname').focus(function() {
        if ($(this).val() == $(this).attr('title')) {
            $(this).val('');
        }
    });
    $('#replymessage').focus();
}

/**
 * Send a reply in a discussion.
 * 
 * @param string parentid : the comment identifier we want to send a reply to.
 */
function send_comment(parentid) {
    // Do not send if no data.
    if ($('#replymessage').val().length==0) {
        return;
    }

    showStatus('Sending comment...', spin=true);
    var cipherdata = zeroCipher(pageKey(), $('#replymessage').val());
    var ciphernickname = '';
    var nick = $('#nickname').val();
    if (nick != '' && nick != 'Optional nickname...') {
        ciphernickname = zeroCipher(pageKey(), nick);
    }
    var data_to_send = { data:cipherdata,
                         parentid: parentid,
                         pasteid:  pasteID(),
                         nickname: ciphernickname
                       };

    $.post(scriptLocation(), data_to_send, function(data) {
        if (data.status == 0) {
            showStatus('Comment posted.');
            $.get(scriptLocation() + "?" + pasteID() + "&json", function(data) {
                if (data.status == 0) {
                    displayMessages(pageKey(), data.messages);
                }
                else if (data.status == 1) {
                    showError('Could not refresh display: ' + data.message);
                }
                else
                {
                    showError('Could not refresh display: unknown status');
                }
            }, 'json')
            .fail(function() {
                showError('Could not refresh display (server error or not responding).');
            });
        }
        else if (data.status == 1) {
            showError('Could not post comment: ' + data.message);
        }
        else
        {
            showError('Could not post comment: unknown status');
        }
    }, 'json')
    .fail(function() {
        showError('Comment could not be sent (server error or not responding).');
    });
}

/**
 * Send a new paste to server
 */
function send_data() {
    // Do not send if no data.
    if ($('#message').val().length == 0) {
        return;
    }

    // If sjcl has not collected enough entropy yet, display a message.
    if (!sjcl.random.isReady())
    {
        showStatus('Sending paste (Please move your mouse for more entropy)...', spin=true);
        sjcl.random.addEventListener('seeded', function(){ send_data(); });
        return;
    }

    showStatus('Sending paste...', spin=true);

    var randomkey = sjcl.codec.base64.fromBits(sjcl.random.randomWords(8, 0), 0);
    var cipherdata = zeroCipher(randomkey, $('#message').val());
    var data_to_send = { data:           cipherdata,
                         expire:         $('#pasteExpiration').val(),
                         burnafterreading: $('#burnafterreading').is(':checked') ? 1 : 0,
                         opendiscussion: $('#opendiscussion').is(':checked') ? 1 : 0
                       };
    $.post(scriptLocation(), data_to_send, function(data) {
        if (data.status == 0) {
            stateExistingPaste();
            var url = scriptLocation() + "?" + data.id + '#' + randomkey;
            var deleteUrl = scriptLocation() + "?pasteid=" + data.id + '&deletetoken=' + data.deletetoken;
            showStatus('');

            $('#pastelink').html('Your paste is <a id="pasteurl" href="' + url + '">' + url + '</a> <span id="copyhint">(Hit CTRL+C to copy)</span>');
            $('#deletelink').html('<a href="' + deleteUrl + '">Delete data</a>');
            $('#pasteresult').removeClass('hidden');
            selectText('pasteurl'); // We pre-select the link so that the user only has to CTRL+C the link.

            setElementText($('#cleartext'), $('#message').val());
            setElementText($('#prettyprint'), $('#message').val());
            // Convert URLs to clickable links.
            urls2links($('#cleartext'));
            urls2links($('#prettyprint'));
            showStatus('');
            if (typeof prettyPrint == 'function') prettyPrint();
        }
        else if (data.status==1) {
            showError('Could not create paste: ' + data.message);
        }
        else {
            showError('Could not create paste.');
        }
    }, 'json')
    .fail(function() {
        showError('Data could not be sent (server error or not responding).');
    });
}

/**
 * Text range selection.
 * From: http://stackoverflow.com/questions/985272/jquery-selecting-text-in-an-element-akin-to-highlighting-with-your-mouse
 * 
 * @param string element : Indentifier of the element to select (id="").
 */
function selectText(element) {
    var doc = document
        , text = doc.getElementById(element)
        , range, selection
    ;
    if (doc.body.createTextRange) { // MS
        range = doc.body.createTextRange();
        range.moveToElementText(text);
        range.select();
    } else if (window.getSelection) { // all others
        selection = window.getSelection();
        range = doc.createRange();
        range.selectNodeContents(text);
        selection.removeAllRanges();
        selection.addRange(range);
    }
}

/**
 * Put the screen in "New paste" mode.
 */
function stateNewPaste() {
    $('#sendbutton').removeClass('hidden');
    $('#clonebutton').addClass('hidden');
    $('#rawtextbutton').addClass('hidden');
    $('#expiration').removeClass('hidden');
    $('#remainingtime').addClass('hidden');
    $('#burnafterreadingoption').removeClass('hidden');
    $('#opendisc').removeClass('hidden');
    $('#newbutton').removeClass('hidden');
    $('#pasteresult').addClass('hidden');
    $('#message').text('');
    $('#message').removeClass('hidden');
    $('#cleartext').addClass('hidden');
    $('#message').focus();
    $('#discussion').addClass('hidden');
    $('#prettymessage').addClass('hidden');
    // Show password field
    $('#password').removeClass('hidden');
}

/**
 * Put the screen in "Existing paste" mode.
 */
function stateExistingPaste() {
    $('#sendbutton').addClass('hidden');

    // No "clone" for IE<10.
    if ($('#oldienotice').is(":visible")) {
        $('#clonebutton').addClass('hidden');
    }
    else {
        $('#clonebutton').removeClass('hidden');
    }
    $('#rawtextbutton').removeClass('hidden');

    $('#expiration').addClass('hidden');
    $('#burnafterreadingoption').addClass('hidden');
    $('#opendisc').addClass('hidden');
    $('#newbutton').removeClass('hidden');
    $('#pasteresult').addClass('hidden');
    $('#message').addClass('hidden');
    $('#cleartext').addClass('hidden');
    $('#prettymessage').removeClass('hidden');
}

/**
 * Return raw text
 */
function rawText()
{
    var paste = $('#cleartext').html();
    var newDoc = document.open('text/html', 'replace');
    newDoc.write('<pre>'+paste+'</pre>');
    newDoc.close();
}

/**
 * Clone the current paste.
 */
function clonePaste() {
    stateNewPaste();
    
    //Erase the id and the key in url
    history.replaceState(document.title, document.title, scriptLocation());
    
    showStatus('');
    $('#message').text($('#cleartext').text());
}

/**
 * Create a new paste.
 */
function newPaste() {
    stateNewPaste();
    showStatus('');
    $('#message').text('');
}

/**
 * Display an error message
 * (We use the same function for paste and reply to comments)
 */
function showError(message) {
    if ($('#status').length) {
        $('#status').addClass('errorMessage').text(message);
    } else {
        $('#errormessage').removeClass('hidden').append(message);
    }
    $('#replystatus').addClass('errorMessage').text(message);
}

/**
 * Display status
 * (We use the same function for paste and reply to comments)
 *
 * @param string message : text to display
 * @param boolean spin (optional) : tell if the "spinning" animation should be displayed.
 */
function showStatus(message, spin) {
    $('#replystatus').removeClass('errorMessage');
    $('#replystatus').text(message);
    if (!message) {
        $('#status').html(' ');
        return;
    }
    if (message == '') {
        $('#status').html(' ');
        return;
    }
    $('#status').removeClass('errorMessage');
    $('#status').text(message);
    if (spin) {
        var img = '<img src="img/busy.gif" style="width:16px;height:9px;margin:0px 4px 0px 0px;" />';
        $('#status').prepend(img);
        $('#replystatus').prepend(img);
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

/**
 * main application start, called when DOM is fully loaded
 */
$(function() {
    // hide "no javascript" message
    $('#noscript').hide();

    // If "burn after reading" is checked, disable discussion.
    $('#burnafterreading').change(function() {
        if ($(this).is(':checked') ) {
            $('#opendisc').addClass('buttondisabled');
            $('#opendiscussion').attr({checked: false});
            $('#opendiscussion').attr('disabled',true);
        }
        else {
            $('#opendisc').removeClass('buttondisabled');
            $('#opendiscussion').removeAttr('disabled');
        }
    });

    // Display status returned by php code if any (eg. Paste was properly deleted.)
    if ($('#status').text().length > 0) {
        showStatus($('#status').text(),false);
        return;
    }

    $('#status').html(' '); // Keep line height even if content empty.

    // Display an existing paste
    if ($('#cipherdata').text().length > 1) {
        // Missing decryption key in URL ?
        if (window.location.hash.length == 0) {
            showError('Cannot decrypt paste: Decryption key missing in URL (Did you use a redirector or an URL shortener which strips part of the URL ?)');
            return;
        }

        // List of messages to display
        var messages = jQuery.parseJSON($('#cipherdata').text());

        // Show proper elements on screen.
        stateExistingPaste();

        displayMessages(pageKey(), messages);
    }
    // Display error message from php code.
    else if ($('#errormessage').text().length>1) {
        showError($('#errormessage').text());
    }
    // Create a new paste.
    else {
        newPaste();
    }
});
