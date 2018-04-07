import $ from 'jquery'

/**
 * (Model) Data source (aka MVC)
 *
 * @name   Model
 * @class
 */

var $cipherData,
    $templates;

var id = null, symmetricKey = null;

/**
 * returns the expiration set in the HTML
 *
 * @name   Model.getExpirationDefault
 * @function
 * @return string
 */
export function getExpirationDefault()
{
    return $('#pasteExpiration').val();
}

/**
 * returns the format set in the HTML
 *
 * @name   Model.getFormatDefault
 * @function
 * @return string
 */
export function getFormatDefault()
{
    return $('#pasteFormatter').val();
}

/**
 * check if cipher data was supplied
 *
 * @name   Model.getCipherData
 * @function
 * @return boolean
 */
export function hasCipherData()
{
    return getCipherData().length > 0;
}

/**
 * returns the cipher data
 *
 * @name   Model.getCipherData
 * @function
 * @return string
 */
export function getCipherData()
{
    return $cipherData.text();
}

/**
 * get the pastes unique identifier from the URL,
 * eg. http://example.com/path/?c05354954c49a487#dfdsdgdgdfgdf returns c05354954c49a487
 *
 * @name   Model.getPasteId
 * @function
 * @return {string} unique identifier
 * @throws {string}
 */
export function getPasteId()
{
    if (id === null) {
        id = window.location.search.substring(1);

        if (id === '') {
            throw 'no paste id given';
        }
    }

    return id;
}

/**
 * return the deciphering key stored in anchor part of the URL
 *
 * @name   Model.getPasteKey
 * @function
 * @return {string|null} key
 * @throws {string}
 */
export function getPasteKey()
{
    if (symmetricKey === null) {
        symmetricKey = window.location.hash.substring(1);

        if (symmetricKey === '') {
            throw 'no encryption key given';
        }

        // Some web 2.0 services and redirectors add data AFTER the anchor
        // (such as &utm_source=...). We will strip any additional data.
        var ampersandPos = symmetricKey.indexOf('&');
        if (ampersandPos > -1)
        {
            symmetricKey = symmetricKey.substring(0, ampersandPos);
        }
    }

    return symmetricKey;
}

/**
 * returns a jQuery copy of the HTML template
 *
 * @name Model.getTemplate
 * @function
 * @param  {string} name - the name of the template
 * @return {jQuery}
 */
export function getTemplate(name)
{
    // find template
    var $element = $templates.find('#' + name + 'template').clone(true);
    // change ID to avoid collisions (one ID should really be unique)
    return $element.prop('id', name);
}

/**
 * resets state, used for unit testing
 *
 * @name   Model.reset
 * @function
 */
export function reset()
{
    $cipherData = $templates = id = symmetricKey = null;
}

/**
 * init navigation manager
 *
 * preloads jQuery elements
 *
 * @name   Model.init
 * @function
 */
export function init()
{
    $cipherData = $('#cipherdata');
    $templates = $('#templates');
}
