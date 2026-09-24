'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('PasteViewer', function () {
    describe('run, hide, getText, setText, getFormat, setFormat & isPrettyPrinted', function () {
        this.timeout(30000);

        beforeEach(() => {
            document.body.innerHTML = (
                '<div id="placeholder" class="hidden">+++ no document text +++</div>' +
                '<div id="prettymessage" class="hidden"><pre id="prettyprint" class="prettyprint linenums:1"></pre></div>' +
                '<div id="plaintext" class="hidden"></div>'
            );
        });

        afterEach(() => {
            globalThis.cleanup();
        });

        it('basic plaintext display works', function () {
            PrivateBin.PasteViewer.init();
            PrivateBin.PasteViewer.setFormat('plaintext');
            PrivateBin.PasteViewer.setText('hello');
            PrivateBin.PasteViewer.run();
            assert.strictEqual(PrivateBin.PasteViewer.getText(), 'hello');
            assert.ok(!document.getElementById('prettymessage').classList.contains('hidden'));
        });

        it('basic markdown display works', function () {
            PrivateBin.PasteViewer.init();
            PrivateBin.PasteViewer.setFormat('markdown');
            PrivateBin.PasteViewer.setText('hello **bold**');
            PrivateBin.PasteViewer.run();
            assert.strictEqual(PrivateBin.PasteViewer.getText(), 'hello **bold**');
            assert.ok(!document.getElementById('plaintext').classList.contains('hidden'));
        });

        it('initializes with empty text and shows nothing', () => {
            fc.assert(fc.property(
                common.fcFormats(),
                function (format) {
                    var results = [];
                    PrivateBin.PasteViewer.init();
                    PrivateBin.PasteViewer.setFormat(format);
                    PrivateBin.PasteViewer.setText('');
                    results.push(
                        document.getElementById('placeholder').classList.contains('hidden') &&
                        document.getElementById('prettymessage').classList.contains('hidden') &&
                        document.getElementById('plaintext').classList.contains('hidden') &&
                        PrivateBin.PasteViewer.getFormat() === format &&
                        PrivateBin.PasteViewer.getText() === ''
                    );
                    return results.every(element => element);
                }
            ));
        });

        it('when no text is given and view is rendered, it shows placeholder', () => {
            fc.assert(fc.property(
                common.fcFormats(),
                function (format) {
                    var results = [];
                    PrivateBin.PasteViewer.init();
                    PrivateBin.PasteViewer.setFormat(format);
                    PrivateBin.PasteViewer.setText('');
                    PrivateBin.PasteViewer.run();
                    results.push(
                        !document.getElementById('placeholder').classList.contains('hidden') &&
                        document.getElementById('prettymessage').classList.contains('hidden') &&
                        document.getElementById('plaintext').classList.contains('hidden')
                    );
                    return results.every(element => element);
                }
            ));
        });

        it('displays text according to format', () => {
            fc.assert(fc.property(
                common.fcFormats(),
                fc.string({minLength: 1}),
                function (format, text) {
                    var results = [];
                    PrivateBin.PasteViewer.init();
                    PrivateBin.PasteViewer.setFormat(format);
                    PrivateBin.PasteViewer.setText(text);
                    PrivateBin.PasteViewer.run();
                    results.push(
                        document.getElementById('placeholder').classList.contains('hidden') &&
                        !PrivateBin.PasteViewer.isPrettyPrinted() &&
                        PrivateBin.PasteViewer.getText() === text
                    );
                    if (format === 'markdown') {
                        results.push(
                            document.getElementById('prettymessage').classList.contains('hidden') &&
                            !document.getElementById('plaintext').classList.contains('hidden')
                        );
                    } else {
                        results.push(
                            !document.getElementById('prettymessage').classList.contains('hidden') &&
                            document.getElementById('plaintext').classList.contains('hidden')
                        );
                    }

                    return results.every(element => element);
                }
            ));
        });

        it('sanitizes XSS', () => {
            fc.assert(fc.property(
                common.fcFormats(),
                fc.string(),
                fc.constantFrom(
                    '<PLAINTEXT>',
                    '></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>',
                    '\'\';!--"<XSS>=&{()}',
                    '<SCRIPT SRC=http://example.com/xss.js></SCRIPT>',
                    '\'">><marquee><img src=x onerror=confirm(1)></marquee>">' +
                    '</plaintext\\></|\\><plaintext/onmouseover=prompt(1)>' +
                    '<script>prompt(1)</script>@gmail.com<isindex formaction=java' +
                    // obfuscate script URL from eslint rule: no-script-url
                    'script:alert(/XSS/) type=submit>\'-->"></script>' +
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
                ),
                fc.string(),
                function (format, prefix, xss, suffix) {
                    var text = prefix + xss + suffix;
                    document.body.innerHTML = (
                        '<div id="placeholder" class="hidden">+++ no document text ' +
                        '+++</div><div id="prettymessage" class="hidden"><pre ' +
                        'id="prettyprint" class="prettyprint linenums:1"></pre>' +
                        '</div><div id="plaintext" class="hidden"></div>'
                    );
                    PrivateBin.PasteViewer.init();
                    PrivateBin.PasteViewer.setFormat(format);
                    PrivateBin.PasteViewer.setText(text);
                    PrivateBin.PasteViewer.run();
                    var result = document.body.innerHTML.indexOf(xss) === -1;
                    globalThis.cleanup();
                    return result;
                }
            ));
        });
    });
});
