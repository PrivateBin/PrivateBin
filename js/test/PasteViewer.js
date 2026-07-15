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
                    '<div id="placeholder" class="hidden">+++ no document text ' +
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
            ]),
            'string',
            function (format, prefix, xss, suffix) {
                var clean = jsdom(),
                    text = prefix + xss + suffix;
                $('body').html(
                    '<div id="placeholder" class="hidden">+++ no document text ' +
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

    describe('Prism.js syntax highlighting & auto-detection', function () {
        it('correctly auto-detects diff format', function () {
            var clean = jsdom();
            $('body').html(
                '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden"><pre ' +
                'id="prettyprint" class="line-numbers"></pre></div><div id="plaintext" class="hidden"></div>'
            );
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
            $.PrivateBin.PasteViewer.setLanguage('auto');
            $.PrivateBin.PasteViewer.setText('--- a/file.txt\n+++ b/file.txt\n@@ -1,1 +1,1 @@\n-old\n+new');
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-diff'), true);
            assert.strictEqual($('#prettyprint').hasClass('diff-highlight'), true);
            clean();
        });

        it('correctly auto-detects PHP format', function () {
            var clean = jsdom();
            $('body').html(
                '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden"><pre ' +
                'id="prettyprint" class="line-numbers"></pre></div><div id="plaintext" class="hidden"></div>'
            );
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
            $.PrivateBin.PasteViewer.setLanguage('auto');
            $.PrivateBin.PasteViewer.setText('<?php echo "Hello World"; ?>');
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-php'), true);
            clean();
        });

        it('respects manual language override', function () {
            var clean = jsdom();
            $('body').html(
                '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden"><pre ' +
                'id="prettyprint" class="line-numbers"></pre></div><div id="plaintext" class="hidden"></div>'
            );
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
            $.PrivateBin.PasteViewer.setLanguage('javascript');
            $.PrivateBin.PasteViewer.setText('const foo = "bar";');
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-javascript'), true);
            clean();
        });

        it('correctly auto-detects JSON format', function () {
            var clean = jsdom();
            $('body').html(
                '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden"><pre ' +
                'id="prettyprint" class="line-numbers"></pre></div><div id="plaintext" class="hidden"></div>'
            );
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
            $.PrivateBin.PasteViewer.setLanguage('auto');
            $.PrivateBin.PasteViewer.setText('{"name": "PrivateBin", "version": 2}');
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-json'), true);
            clean();
        });

        it('correctly auto-detects SQL format', function () {
            var clean = jsdom();
            $('body').html(
                '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden"><pre ' +
                'id="prettyprint" class="line-numbers"></pre></div><div id="plaintext" class="hidden"></div>'
            );
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
            $.PrivateBin.PasteViewer.setLanguage('auto');
            $.PrivateBin.PasteViewer.setText('SELECT * FROM pastes WHERE id = 1;');
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-sql'), true);
            clean();
        });

        it('correctly auto-detects Markdown format', function () {
            var clean = jsdom();
            $('body').html(
                '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden"><pre ' +
                'id="prettyprint" class="line-numbers"></pre></div><div id="plaintext" class="hidden"></div>'
            );
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
            $.PrivateBin.PasteViewer.setLanguage('auto');
            $.PrivateBin.PasteViewer.setText('## Header\n\n[Link](https://privatebin.info)\n\n- item 1\n- item 2');
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-markdown'), true);
            clean();
        });

        it('correctly auto-detects TypeScript format', function () {
            var clean = jsdom();
            $('body').html(
                '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden"><pre ' +
                'id="prettyprint" class="line-numbers"></pre></div><div id="plaintext" class="hidden"></div>'
            );
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
            $.PrivateBin.PasteViewer.setLanguage('auto');
            $.PrivateBin.PasteViewer.setText('interface User {\n  id: number;\n  name: string;\n}\nconst u: User = { id: 1, name: "Alice" };');
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-typescript'), true);
            clean();
        });

        it('correctly auto-detects YAML format', function () {
            var clean = jsdom();
            $('body').html(
                '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden"><pre ' +
                'id="prettyprint" class="line-numbers"></pre></div><div id="plaintext" class="hidden"></div>'
            );
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
            $.PrivateBin.PasteViewer.setLanguage('auto');
            $.PrivateBin.PasteViewer.setText('version: "3"\nservices:\n  web:\n    image: nginx:latest\n    ports:\n      - "80:80"');
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-yaml'), true);
            clean();
        });

        it('falls back to clike for unsupported languages like Ruby', function () {
            var clean = jsdom();
            $('body').html(
                '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden"><pre ' +
                'id="prettyprint" class="line-numbers"></pre></div><div id="plaintext" class="hidden"></div>'
            );
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
            $.PrivateBin.PasteViewer.setLanguage('auto');
            $.PrivateBin.PasteViewer.setText('class Person\n  attr_accessor :name\n  def initialize(name)\n    @name = name\n  end\nend');
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-clike'), true);
            clean();
        });
    });
});
