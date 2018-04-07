/**
 * static Helper methods
 *
 * @name Helper
 * @class
 */

/**
 * cache for script location
 *
 * @name Helper._baseUri
 * @private
 * @enum   {string|null}
 */
var _baseUri = null;

/**
 * converts a duration (in seconds) into human friendly approximation
 *
 * @name Helper.secondsToHuman
 * @function
 * @param  {number} seconds
 * @return {Array}
 */
export function secondsToHuman(seconds)
{
    var v;
    if (seconds < 60)
    {
        v = Math.floor(seconds);
        return [v, 'second'];
    }
    if (seconds < 60 * 60)
    {
        v = Math.floor(seconds / 60);
        return [v, 'minute'];
    }
    if (seconds < 60 * 60 * 24)
    {
        v = Math.floor(seconds / (60 * 60));
        return [v, 'hour'];
    }
    // If less than 2 months, display in days:
    if (seconds < 60 * 60 * 24 * 60)
    {
        v = Math.floor(seconds / (60 * 60 * 24));
        return [v, 'day'];
    }
    v = Math.floor(seconds / (60 * 60 * 24 * 30));
    return [v, 'month'];
}

/**
 * text range selection
 *
 * @see    {@link https://stackoverflow.com/questions/985272/jquery-selecting-text-in-an-element-akin-to-highlighting-with-your-mouse}
 * @name   Helper.selectText
 * @function
 * @param  {HTMLElement} element
 */
export function selectText(element)
{
    var range, selection;

    // MS
    if (document.body.createTextRange) {
        range = document.body.createTextRange();
        range.moveToElementText(element);
        range.select();
    } else if (window.getSelection) {
        selection = window.getSelection();
        range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }
}

/**
 * convert URLs to clickable links.
 * URLs to handle:
 * <pre>
 *     magnet:?xt.1=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C&xt.2=urn:sha1:TXGCZQTH26NL6OUQAJJPFALHG2LTGBC7
 *     http://example.com:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
 *     http://user:example.com@localhost:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
 * </pre>
 *
 * @name   Helper.urls2links
 * @function
 * @param  {string} html
 * @return {string}
 */
export function urls2links(html)
{
    return html.replace(
        /(((http|https|ftp):\/\/[\w?=&.\/-;#@~%+*-]+(?![\w\s?&.\/;#~%"=-]*>))|((magnet):[\w?=&.\/-;#@~%+*-]+))/ig,
        '<a href="$1" rel="nofollow">$1</a>'
    );
}

/**
 * minimal sprintf emulation for %s and %d formats
 *
 * Note that this function needs the parameters in the same order as the
 * format strings appear in the string, contrary to the original.
 *
 * @see    {@link https://stackoverflow.com/questions/610406/javascript-equivalent-to-printf-string-format#4795914}
 * @name   Helper.sprintf
 * @function
 * @param  {string} format
 * @param  {...*} args - one or multiple parameters injected into format string
 * @return {string}
 */
export function sprintf()
{
    var args = Array.prototype.slice.call(arguments);
    var format = args[0],
        i = 1;
    return format.replace(/%(s|d)/g, function (m) {
        // m is the matched format, e.g. %s, %d
        var val = args[i];
        // A switch statement so that the formatter can be extended.
        switch (m)
        {
            case '%d':
                val = parseFloat(val);
                if (isNaN(val)) {
                    val = 0;
                }
                break;
            default:
                // Default is %s
        }
        ++i;
        return val;
    });
}

/**
 * get value of cookie, if it was set, empty string otherwise
 *
 * @see    {@link http://www.w3schools.com/js/js_cookies.asp}
 * @name   Helper.getCookie
 * @function
 * @param  {string} cname - may not be empty
 * @return {string}
 */
export function getCookie(cname) {
    var name = cname + '=',
        ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; ++i) {
        var c = ca[i];
        while (c.charAt(0) === ' ')
        {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0)
        {
            return c.substring(name.length, c.length);
        }
    }
    return '';
}

/**
 * get the current location (without search or hash part of the URL),
 * eg. http://example.com/path/?aaaa#bbbb --> http://example.com/path/
 *
 * @name   Helper.baseUri
 * @function
 * @return {string}
 */
export function baseUri()
{
    // check for cached version
    if (_baseUri !== null) {
        return _baseUri;
    }

    _baseUri = window.location.origin + window.location.pathname;
    return _baseUri;
}

/**
 * resets state, used for unit testing
 *
 * @name   Helper.reset
 * @function
 */
export function reset()
{
    _baseUri = null;
}
