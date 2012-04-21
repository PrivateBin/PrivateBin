/* ZeroBin 0.15 - http://sebsauvage.net/wiki/doku.php?id=php:zerobin */

// Immediately start random number generator collector.
sjcl.random.startCollectors();

// Converts a duration (in seconds) into human readable format.
function secondsToHuman(seconds)
{
    if (seconds<60) { var v=Math.floor(seconds); return v+' second'+((v>1)?'s':''); }
    if (seconds<60*60) { var v=Math.floor(seconds/60); return v+' minute'+((v>1)?'s':''); }
    if (seconds<60*60*24) { var v=Math.floor(seconds/(60*60)); return v+' hour'+((v>1)?'s':''); }
    // If less than 2 months, display in days:
    if (seconds<60*60*24*60) { var v=Math.floor(seconds/(60*60*24)); return v+' day'+((v>1)?'s':''); }
    var v=Math.floor(seconds/(60*60*24*30)); return v+' month'+((v>1)?'s':'');
}
// Compress a message (deflate compression). Returns base64 encoded data.
function compress(message) { return Base64.toBase64(RawDeflate.deflate(Base64.utob(message))); }

// Decompress a message compressed with compress().
function decompress(data) { return Base64.btou(RawDeflate.inflate(Base64.fromBase64(data))) }

// Compress, then encrypt message with key.
function zeroCipher(key,message)
{
    return sjcl.encrypt(key,compress(message));
}
// Decrypt message with key, then decompress.
function zeroDecipher(key,data)
{
    return decompress(sjcl.decrypt(key,data));
}

// Returns the current script location (without search or hash part of the URL).
// eg. http://server.com/zero/?aaaa#bbbb --> http://server.com/zero/
function scriptLocation()
{
    return window.location.href.substring(0,window.location.href.length
               -window.location.search.length -window.location.hash.length);
}

// Returns the paste unique identifier from the URL
// eg. 'c05354954c49a487'
function pasteID()
{
    return window.location.search.substring(1);
}

// Set text of a DOM element (required for IE)
// This is equivalent to element.text(text)
// Input: element : a DOM element.
//        text : the text to enter.
function setElementText(element,text)
{
    if ($('div#oldienotice').is(":visible"))  // For IE<10.
    {
        // IE<10 do not support white-space:pre-wrap; so we have to do this BIG UGLY STINKING THING.
        element.text(text.replace(/\n/ig,'{BIG_UGLY_STINKING_THING__OH_GOD_I_HATE_IE}'));
        element.html(element.text().replace(/{BIG_UGLY_STINKING_THING__OH_GOD_I_HATE_IE}/ig,"\r\n<br>"));
    }
    else // for other (sane) browsers:
    {
        element.text(text);
    }
}

// Show decrypted text in the display area, including discussion (if open)
// Input: messages (Array) : Array of messages to display (items = array with keys ('data','meta')
//        key (string): decryption key
function displayMessages(key,comments)
{     
    try { // Try to decrypt the paste.
        var cleartext = zeroDecipher(key,comments[0].data); 
    } catch(err) { 
        $('div#cleartext').hide();
        $('button#clonebutton').hide();
        showError('Could not decrypt data (Wrong key ?)'); 
        return;
    }
    setElementText($('div#cleartext'),cleartext);
    urls2links($('div#cleartext')); // Convert URLs to clickable links.
    
    // Display paste expiration.
    if (comments[0].meta.expire_date) $('div#remainingtime').removeClass('foryoureyesonly').text('This document will expire in '+secondsToHuman(comments[0].meta.remaining_time)+'.').show();
    if (comments[0].meta.burnafterreading)
    {
        $('div#remainingtime').addClass('foryoureyesonly').text('FOR YOUR EYES ONLY.  Don\'t close this window, this message can\'t be displayed again.').show();
        $('button#clonebutton').hide(); // Discourage cloning (as it can't really be prevented).
    }
    
    // If the discussion is opened on this paste, display it.      
    if (comments[0].meta.opendiscussion)
    {
        $('div#comments').html('');
        for (var i = 1; i < comments.length; i++) // For each comment.
        {
            var comment=comments[i]; 
            var cleartext="[Could not decrypt comment ; Wrong key ?]";            
            try { cleartext = zeroDecipher(key,comment.data); } catch(err) { }                
            var place = $('div#comments');
            // If parent comment exists, display below (CSS will automatically shift it right.)
            var cname = 'div#comment_'+comment.meta.parentid
            if ($(cname).length)  place = $(cname); // If the element exists in page
            var divComment = $('<div class="comment" id="comment_'+comment.meta.commentid+'">'
                               +'<div class="commentmeta"><span class="nickname"></span><span class="commentdate"></span></div><div class="commentdata"></div>'
                               +'<button onclick="open_reply($(this),\''+comment.meta.commentid+'\');return false;">Reply</button>'
                               +'</div>');
            setElementText(divComment.find('div.commentdata'),cleartext);
            urls2links(divComment.find('div.commentdata')); // Convert URLs to clickable links in comment.
            divComment.find('span.nickname').html('<i>(Anonymous)</i>');
            // Try to get optional nickname:
            try { divComment.find('span.nickname').text(zeroDecipher(key,comment.meta.nickname));} catch(err) { }  
            divComment.find('span.commentdate').text('  ('+(new Date(comment.meta.postdate*1000).toUTCString())+')').attr('title','CommentID: '+comment.meta.commentid);
            // If an avatar is available, display it.
            if (comment.meta.vizhash) 
                divComment.find('span.nickname').before('<img src="'+comment.meta.vizhash+'" class="vizhash" title="Anonymous avatar (Vizhash of the IP address)" />');
            place.append(divComment);
        }
        $('div#comments').append('<div class="comment"><button onclick="open_reply($(this),\''+pasteID()+'\');return false;">Add comment</button></div>');
        $('div#discussion').show();
    }
}

// Open the comment entry when clicking the "Reply" button of a comment.
// source = element which emitted the event.
// commentid = identifier of the comment we want to reply to.
function open_reply(source,commentid)
{
    $('div.reply').remove(); // Remove any other reply area.
    source.after('<div class="reply">'
                +'<input type="text" id="nickname" title="Optional nickname..." value="Optional nickname..." />'
                +'<textarea id="replymessage" class="replymessage" cols="80" rows="7"></textarea>'
                +'<br><button id="replybutton" onclick="send_comment(\''+commentid+'\');return false;">Post comment</button>'
                +'<div id="replystatus">&nbsp;</div>'
                +'</div>');
    $('input#nickname').focus(function() {
                             $(this).css('color', '#000');
                             if($(this).val()==$(this).attr('title')) $(this).val('');
                        });    
    $('textarea#replymessage').focus();
}

// Send a reply in a discussion.
// Input: parentid : the comment identifier we want to send a reply to.
function send_comment(parentid)
{
    if ($('textarea#replymessage').val().length==0) return; // Do not send if no data.
    showStatus('Sending comment...',spin=true); 
    var cipherdata=zeroCipher(pageKey(), $('textarea#replymessage').val());
    var ciphernickname='';
    var nick=$('input#nickname').val();
    if (nick!='' && nick!='Optional nickname...') ciphernickname=ciphernickname=zeroCipher(pageKey(),nick);
    var data_to_send =  { data:cipherdata,
                          parentid: parentid,
                          pasteid: pasteID(),
                          nickname: ciphernickname
                        }; 
    $.post(scriptLocation(), data_to_send ,'json' )
    .error( function() { showError('Comment could not be sent (serveur error or not responding).'); } )
    .success(function(data)
             {
                if (data.status==0) 
                {
                    showStatus('Comment posted.');
                    location.reload();
                }
                else if (data.status==1) 
                { 
                    showError('Could not post comment: '+data.message); 
                }
                else
                { 
                    showError('Could not post comment.'); 
                }
             }
    );
}

// Send a new paste to server
function send_data()
{
    if ($('textarea#message').val().length==0) return; // Do not send if no data.
    showStatus('Sending paste...',spin=true); 
    var randomkey = sjcl.codec.base64.fromBits(sjcl.random.randomWords(8,0),0);
    var cipherdata = zeroCipher(randomkey,$('textarea#message').val());
    var data_to_send =  { data:cipherdata,
                          expire:$('select#pasteExpiration').val(),
                          opendiscussion:$('input#opendiscussion').is(':checked')?1:0
                        }; 
    $.post(scriptLocation(), data_to_send ,'json' )
    .error( function() { showError('Data could not be sent (serveur error or not responding).'); } )
    .success(function(data)
             {
                if (data.status==0) 
                {
                    stateExistingPaste();
                    var url=scriptLocation()+"?"+data.id+'#'+randomkey; 
                    showStatus('');
                    $('div#pastelink').html('Your paste is <a href="'+url+'">'+url+'</a>');
                    $('div#pastelink').append('&nbsp;&nbsp;<button id="shortenbutton" onclick="document.location=\''+shortenUrl(url)+'\'"><img src="lib/icon_shorten.png" width="13" height="15" />Shorten URL</button>').show();
                    setElementText($('div#cleartext'),$('textarea#message').val());                    
                    urls2links($('div#cleartext')); 
                    showStatus('');
                }
                else if (data.status==1) 
                { 
                    showError('Could not create paste: '+data.message); 
                }
                else
                { 
                    showError('Could not create paste.'); 
                }
             }
    );
}

// Put the screen in "New paste" mode.
function stateNewPaste()
{
    $('button#sendbutton').show();
    $('button#clonebutton').hide();
    $('div#expiration').show();
    $('div#remainingtime').hide();
    $('div#language').hide(); // $('#language').show();
    $('input#password').hide(); //$('#password').show();
    $('div#opendisc').show();
    $('button#newbutton').show();
    $('div#pastelink').hide();
    $('textarea#message').text('');
    $('textarea#message').show();
    $('div#cleartext').hide();
    $('div#message').focus();
    $('div#discussion').hide();
}

// Put the screen in "Existing paste" mode.
function stateExistingPaste()
{
    $('button#sendbutton').hide();
    if (!$('div#oldienotice').is(":visible")) $('button#clonebutton').show(); // No "clone" for IE<10.
    $('button#clonebutton').show();// FIXME
    $('div#expiration').hide();
    $('div#language').hide();
    $('input#password').hide();
    $('div#opendisc').hide();
    $('button#newbutton').show();
    $('div#pastelink').hide();
    $('textarea#message').hide();
    $('div#cleartext').show();
}

// Clone the current paste.
function clonePaste()
{
    stateNewPaste();
    showStatus('');
    $('textarea#message').text($('div#cleartext').text());
}

// Create a new paste.
function newPaste()
{
    stateNewPaste();
    showStatus('');
    $('textarea#message').text('');
}

// Display an error message
// (We use the same function for paste and reply to comments)
function showError(message)
{
    $('div#status').addClass('errorMessage').text(message);
    $('div#replystatus').addClass('errorMessage').text(message);
}

// Display status
// (We use the same function for paste and reply to comments)
// message (string) = text to display
// spin (boolean, optional) = tell if the "spinning" animation should be displayed.
function showStatus(message,spin)
{
    $('div#replystatus').removeClass('errorMessage');
    $('div#replystatus').text(message);
    if (!message) { $('div#status').html('&nbsp'); return; }
    if (message=='') { $('div#status').html('&nbsp'); return; }
    $('div#status').removeClass('errorMessage');
    $('div#status').text(message);
    if (spin)
    {
        var img = '<img src="lib/busy.gif" style="width:16px;height:9px;margin:0px 4px 0px 0px;" />';
        $('div#status').prepend(img);
        $('div#replystatus').prepend(img);
    }
}

// Generate link to URL shortener.
function shortenUrl(url)
{
    return 'http://snipurl.com/site/snip?link='+encodeURIComponent(url);
}

// Convert URLs to clickable links.
// Input: element : a jQuery DOM element.
// Example URLs to handle:
//   magnet:?xt.1=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C&xt.2=urn:sha1:TXGCZQTH26NL6OUQAJJPFALHG2LTGBC7
//   http://localhost:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
//   http://user:password@localhost:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
// FIXME: add ppa & apt links.
function urls2links(element)
{
    var re = /((http|https|ftp):\/\/[\w?=&.\/-;#@~%+-]+(?![\w\s?&.\/;#~%"=-]*>))/ig;
    element.html(element.html().replace(re,'<a href="$1" rel="nofollow">$1</a>'));
    var re = /((magnet):[\w?=&.\/-;#@~%+-]+)/ig;
    element.html(element.html().replace(re,'<a href="$1">$1</a>'));
}

// Return the deciphering key stored in anchor part of the URL
function pageKey()
{
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

$(document).ready(function() {

    $('select#pasteExpiration').change(function() {
        if ($(this).val()=='burn') { $('div#opendisc').addClass('buttondisabled'); $('input#opendiscussion').attr('disabled',true); }
        else { $('div#opendisc').removeClass('buttondisabled'); $('input#opendiscussion').removeAttr('disabled'); }
    });


    if ($('div#cipherdata').text().length>1) // Display an existing paste
    {
        if (window.location.hash.length==0) // Missing decryption key in URL ?
        {
            showError('Cannot decrypt paste: Decryption key missing in URL (Did you use a redirector or an URL shortener which strips part of the URL ?)');
            return;
        }
        var messages = jQuery.parseJSON($('div#cipherdata').text()); // List of messages to display
        stateExistingPaste();  // Show proper elements on screen. 
        displayMessages(pageKey(),messages);
    }
    else if ($('div#errormessage').text().length>1) // Display error message from php code.
    {
        showError($('div#errormessage').text());
    }
    else // Create a new paste.
    {
        newPaste();
    }
});
