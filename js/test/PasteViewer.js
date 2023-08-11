'use strict';
var common = require('../common');

describe('PasteViewer', function () {
    describe('run, hide, getText, setText, getFormat, setFormat & isPrettyPrinted', function () {
        this.timeout(30000);

        jsc.property(
            'displays text according to format',
            common.jscFormats(),
            'nestring',
            function (format, text) {
                var clean = jsdom(),
                    results = [];
                $('body').html(
                    '<div id="placeholder" class="hidden">+++ no paste text ' +
                    '+++</div><div id="prettymessage" class="hidden"><pre ' +
                    'id="prettyprint" class="prettyprint linenums:1"></pre>' +
                    '</div><div id="plaintext" class="hidden"></div>'
                );
                $.PrivateBin.PasteViewer.init();
                $.PrivateBin.PasteViewer.setFormat(format);
                $.PrivateBin.PasteViewer.setText('');
                results.push(
                    $('#placeholder').hasClass('hidden') &&
                    $('#prettymessage').hasClass('hidden') &&
                    $('#plaintext').hasClass('hidden') &&
                    $.PrivateBin.PasteViewer.getFormat() === format &&
                    $.PrivateBin.PasteViewer.getText() === ''
                );
                $.PrivateBin.PasteViewer.run();
                results.push(
                    !$('#placeholder').hasClass('hidden') &&
                    $('#prettymessage').hasClass('hidden') &&
                    $('#plaintext').hasClass('hidden')
                );
                $.PrivateBin.PasteViewer.hide();
                results.push(
                    $('#placeholder').hasClass('hidden') &&
                    $('#prettymessage').hasClass('hidden') &&
                    $('#plaintext').hasClass('hidden')
                );
                $.PrivateBin.PasteViewer.setText(text);
                $.PrivateBin.PasteViewer.run();
                results.push(
                    $('#placeholder').hasClass('hidden') &&
                    !$.PrivateBin.PasteViewer.isPrettyPrinted() &&
                    $.PrivateBin.PasteViewer.getText() === text
                );
                if (format === 'markdown') {
                    results.push(
                        $('#prettymessage').hasClass('hidden') &&
                        !$('#plaintext').hasClass('hidden')
                    );
                } else {
                    results.push(
                        !$('#prettymessage').hasClass('hidden') &&
                        $('#plaintext').hasClass('hidden')
                    );
                }
                clean();
                return results.every(element => element);
            }
        );

        jsc.property(
            'sanitizes XSS',
            common.jscFormats(),
            'string',
            // @see    {@link https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet}
            jsc.elements([
                '<PLAINTEXT>',
                '></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>',
                '\'\';!--"<XSS>=&{()}',
                '<SCRIPT SRC=http://example.com/xss.js></SCRIPT>',
                '\'">><marquee><img src=x onerror=confirm(1)></marquee>">' +
                '</plaintext\\></|\\><plaintext/onmouseover=prompt(1)>' +
                '<script>prompt(1)</script>@gmail.com<isindex formaction=' +
                'javascript:alert(/XSS/) type=submit>\'-->"></script>' +
                '<script>alert(document.cookie)</script>"><img/id="confirm' +
                '&lpar;1)"/alt="/"src="/"onerror=eval(id)>\'">',
                '<IMG SRC="javascript:alert(\'XSS\');">',
                '<IMG SRC=javascript:alert(\'XSS\')>',
                '<IMG SRC=JaVaScRiPt:alert(\'XSS\')>',
                '<IMG SRC=javascript:alert(&quot;XSS&quot;)>',
                '<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>',
                '<a onmouseover="alert(document.cookie)">xxs link</a>',
                '<a onmouseover=alert(document.cookie)>xxs link</a>',
                '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">',
                '<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>',
                '<IMG STYLE="xss:expr/*XSS*/ession(alert(\'XSS\'))">',
                '<FRAMESET><FRAME SRC="javascript:alert(\'XSS\');"></FRAMESET>',
                '<TABLE BACKGROUND="javascript:alert(\'XSS\')">',
                '<TABLE><TD BACKGROUND="javascript:alert(\'XSS\')">',
                '<SCRIPT>document.write("<SCRI");</SCRIPT>PT SRC="httx://xss.rocks/xss.js"></SCRIPT>'
            ]),
            'string',
            function (format, prefix, xss, suffix) {
                var clean = jsdom(),
                    text = prefix + xss + suffix;
                $('body').html(
                    '<div id="placeholder" class="hidden">+++ no paste text ' +
                    '+++</div><div id="prettymessage" class="hidden"><pre ' +
                    'id="prettyprint" class="prettyprint linenums:1"></pre>' +
                    '</div><div id="plaintext" class="hidden"></div>'
                );
                $.PrivateBin.PasteViewer.init();
                $.PrivateBin.PasteViewer.setFormat(format);
                $.PrivateBin.PasteViewer.setText(text);
                $.PrivateBin.PasteViewer.run();
                var result = $('body').html().indexOf(xss) === -1;
                clean();
                return result;
            }
        );
    });
});

