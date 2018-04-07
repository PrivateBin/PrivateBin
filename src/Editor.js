import $ from 'jquery'

/**
 * Manage paste/message input, and preview tab
 *
 * Note that the actual preview is handled by PasteViewer.
 *
 * @name   Editor
 * @class
 */

var $editorTabs,
    $messageEdit,
    $messagePreview,
    $message;

var _isPreview = false;

/**
 * support input of tab character
 *
 * @name   Editor.supportTabs
 * @function
 * @param  {Event} event
 * @this $message (but not used, so it is jQuery-free, possibly faster)
 */
function supportTabs(event)
{
    var keyCode = event.keyCode || event.which;
    // tab was pressed
    if (keyCode === 9) {
        // get caret position & selection
        var val   = this.value,
            start = this.selectionStart,
            end   = this.selectionEnd;
        // set textarea value to: text before caret + tab + text after caret
        this.value = val.substring(0, start) + '\t' + val.substring(end);
        // put caret at right position again
        this.selectionStart = this.selectionEnd = start + 1;
        // prevent the textarea to lose focus
        event.preventDefault();
    }
}

/**
 * view the Editor tab
 *
 * @name   Editor.viewEditor
 * @function
 * @param  {Event} event - optional
 */
function viewEditor(event)
{
    // toggle buttons
    $messageEdit.addClass('active');
    $messagePreview.removeClass('active');

    PasteViewer.hide();

    // reshow input
    $message.removeClass('hidden');

    focusInput();

    // finish
    _isPreview = false;

    // prevent jumping of page to top
    if (typeof event !== 'undefined') {
        event.preventDefault();
    }
}

/**
 * view the preview tab
 *
 * @name   Editor.viewPreview
 * @function
 * @param  {Event} event
 */
function viewPreview(event)
{
    // toggle buttons
    $messageEdit.removeClass('active');
    $messagePreview.addClass('active');

    // hide input as now preview is shown
    $message.addClass('hidden');

    // show preview
    PasteViewer.setText($message.val());
    PasteViewer.run();

    // finish
    _isPreview = true;

    // prevent jumping of page to top
    if (typeof event !== 'undefined') {
        event.preventDefault();
    }
}

/**
 * get the state of the preview
 *
 * @name   Editor.isPreview
 * @function
 */
export function isPreview()
{
    return _isPreview;
}

/**
 * reset the Editor view
 *
 * @name   Editor.resetInput
 * @function
 */
export function resetInput()
{
    // go back to input
    if (_isPreview) {
        viewEditor();
    }

    // clear content
    $message.val('');
}

/**
 * shows the Editor
 *
 * @name   Editor.show
 * @function
 */
export function show()
{
    $message.removeClass('hidden');
    $editorTabs.removeClass('hidden');
}

/**
 * hides the Editor
 *
 * @name   Editor.reset
 * @function
 */
export function hide()
{
    $message.addClass('hidden');
    $editorTabs.addClass('hidden');
}

/**
 * focuses the message input
 *
 * @name   Editor.focusInput
 * @function
 */
export function focusInput()
{
    $message.focus();
}

/**
 * sets a new text
 *
 * @name   Editor.setText
 * @function
 * @param {string} newText
 */
export function setText(newText)
{
    $message.val(newText);
}

/**
 * returns the current text
 *
 * @name   Editor.getText
 * @function
 * @return {string}
 */
export function getText()
{
    return $message.val();
}

/**
 * init status manager
 *
 * preloads jQuery elements
 *
 * @name   Editor.init
 * @function
 */
export function init()
{
    $editorTabs = $('#editorTabs');
    $message = $('#message');

    // bind events
    $message.keydown(supportTabs);

    // bind click events to tab switchers (a), but save parent of them
    // (li)
    $messageEdit = $('#messageedit').click(viewEditor).parent();
    $messagePreview = $('#messagepreview').click(viewPreview).parent();
}
