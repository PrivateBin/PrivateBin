require('bootstrap')

import { random } from 'sjcl'
import jQuery from 'jquery'

/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @see       {@link https://github.com/PrivateBin/PrivateBin}
 * @copyright 2012 Sébastien SAUVAGE ({@link http://sebsauvage.net})
 * @license   {@link https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License}
 * @version   1.1.1
 * @name      PrivateBin
 * @namespace
 */

// Immediately start random number generator collector.
random.startCollectors();

// main application start, called when DOM is fully loaded
jQuery(document).ready(function() {
    'use strict';
    // run main controller
    jQuery.PrivateBin.Controller.init();
});

jQuery.PrivateBin = {
    Helper: Helper,
    I18n: I18n,
    CryptTool: CryptTool,
    Model: Model,
    UiHelper: UiHelper,
    Alert: Alert,
    PasteStatus: PasteStatus,
    Prompt: Prompt,
    Editor: Editor,
    PasteViewer: PasteViewer,
    AttachmentViewer: AttachmentViewer,
    DiscussionViewer: DiscussionViewer,
    TopNav: TopNav,
    Uploader: Uploader,
    PasteEncrypter: PasteEncrypter,
    PasteDecrypter: PasteDecrypter,
    Controller: Controller
};
