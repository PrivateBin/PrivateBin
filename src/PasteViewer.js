import $ from 'jquery'

/**
 * (view) Parse and show paste.
 *
 * @name   PasteViewer
 * @class
 */

var $placeholder,
    $prettyMessage,
    $prettyPrint,
    $plainText;

var text,
    format = 'plaintext',
    isDisplayed = false,
    isChanged = true; // by default true as nothing was parsed yet

/**
 * apply the set format on paste and displays it
 *
 * @name   PasteViewer.parsePaste
 * @private
 * @function
 */
function parsePaste()
{
    // skip parsing if no text is given
    if (text === '') {
        return;
    }

    import('dompurify').then(function (module) {
        var DOMPurify = module.default;

        // escape HTML entities, link URLs, sanitize
        var escapedLinkedText = Helper.urls2links(
                $('<div />').text(text).html()
            ),
            sanitizedLinkedText = DOMPurify.sanitize(escapedLinkedText);
        $plainText.html(sanitizedLinkedText);
        $prettyPrint.html(sanitizedLinkedText);

        switch (format) {
            case 'markdown':
                import('showdown').then(function (showdown) {
                    var converter = new showdown.Converter({
                        strikethrough: true,
                        tables: true,
                        tablesHeaderId: true
                    });
                    // let showdown convert the HTML and sanitize HTML *afterwards*!
                    $plainText.html(
                        DOMPurify.sanitize(converter.makeHtml(text))
                    );
                    // add table classes from bootstrap css
                    $plainText.find('table').addClass('table-condensed table-bordered');
                });
                break;
            case 'syntaxhighlighting':
                import('code-prettify').then(function (module) {
                    var prettyPrint = module.prettyPrint
                    var prettyPrintOne = module.prettyPrintOne

                    // yes, this is really needed to initialize the environment
                    if (typeof prettyPrint === 'function')
                    {
                        prettyPrint();
                    }

                    $prettyPrint.html(
                        DOMPurify.sanitize(
                            prettyPrintOne(escapedLinkedText, null, true)
                        )
                    );
                });
                // fall through, as the rest is the same
            default: // = 'plaintext'
                $prettyPrint.css('white-space', 'pre-wrap');
                $prettyPrint.css('word-break', 'normal');
                $prettyPrint.removeClass('prettyprint');
        }
    });
}

/**
 * displays the paste
 *
 * @name   PasteViewer.showPaste
 * @private
 * @function
 */
function showPaste()
{
    // instead of "nothing" better display a placeholder
    if (text === '') {
        $placeholder.removeClass('hidden');
        return;
    }
    // otherwise hide the placeholder
    $placeholder.addClass('hidden');

    switch (format) {
        case 'markdown':
            $plainText.removeClass('hidden');
            $prettyMessage.addClass('hidden');
            break;
        default:
            $plainText.addClass('hidden');
            $prettyMessage.removeClass('hidden');
            break;
    }
}

/**
 * sets the format in which the text is shown
 *
 * @name   PasteViewer.setFormat
 * @function
 * @param {string} newFormat the new format
 */
export function setFormat(newFormat)
{
    // skip if there is no update
    if (format === newFormat) {
        return;
    }

    // needs to update display too, if we switch from or to Markdown
    if (format === 'markdown' || newFormat === 'markdown') {
        isDisplayed = false;
    }

    format = newFormat;
    isChanged = true;
}

/**
 * returns the current format
 *
 * @name   PasteViewer.getFormat
 * @function
 * @return {string}
 */
export function getFormat()
{
    return format;
}

/**
 * returns whether the current view is pretty printed
 *
 * @name   PasteViewer.isPrettyPrinted
 * @function
 * @return {bool}
 */
export function isPrettyPrinted()
{
    return $prettyPrint.hasClass('prettyprinted');
}

/**
 * sets the text to show
 *
 * @name   PasteViewer.setText
 * @function
 * @param {string} newText the text to show
 */
export function setText(newText)
{
    if (text !== newText) {
        text = newText;
        isChanged = true;
    }
}

/**
 * gets the current cached text
 *
 * @name   PasteViewer.getText
 * @function
 * @return {string}
 */
export function getText()
{
    return text;
}

/**
 * show/update the parsed text (preview)
 *
 * @name   PasteViewer.run
 * @function
 */
export function run()
{
    if (isChanged) {
        parsePaste();
        isChanged = false;
    }

    if (!isDisplayed) {
        showPaste();
        isDisplayed = true;
    }
}

/**
 * hide parsed text (preview)
 *
 * @name   PasteViewer.hide
 * @function
 */
export function hide()
{
    if (!isDisplayed) {
        console.warn('PasteViewer was called to hide the parsed view, but it is already hidden.');
    }

    $plainText.addClass('hidden');
    $prettyMessage.addClass('hidden');
    $placeholder.addClass('hidden');

    isDisplayed = false;
}

/**
 * init status manager
 *
 * preloads jQuery elements
 *
 * @name   PasteViewer.init
 * @function
 */
export function init()
{
    $placeholder = $('#placeholder');
    $plainText = $('#plaintext');
    $prettyMessage = $('#prettymessage');
    $prettyPrint = $('#prettyprint');

    // get default option from template/HTML or fall back to set value
    format = Model.getFormatDefault() || format;
    text = '';
    isDisplayed = false;
    isChanged = true;
}
