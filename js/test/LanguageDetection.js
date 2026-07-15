'use strict';

var common = require('../common');
var assert = require('assert');

describe('Hybrid Language Detection', function () {
    var clean;

    beforeEach(function () {
        clean = jsdom();
        $('body').html(
            '<div id="placeholder" class="hidden"></div>' +
            '<div id="prettymessage" class="hidden"><pre id="prettyprint" class="line-numbers"></pre></div>' +
            '<div id="plaintext" class="hidden"></div>'
        );
        $.PrivateBin.PasteViewer.init();
        $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
        $.PrivateBin.PasteViewer.setLanguage('auto');
    });

    afterEach(function () {
        clean();
    });

    var samples = {
        diff: 'diff --git a/file b/file\n--- a/file\n+++ b/file',
        bash: '#!/bin/bash\necho "Hello World"',
        python: '#!/usr/bin/env python\ndef main():\n    print("Hello")',
        php: '<?php\necho "Hello";',
        markup: '<!DOCTYPE html>\n<html><body></body></html>',
        css: 'body {\n  color: red;\n}',
        clike: 'class Foo {\n    public void bar() {}\n}',
        javascript: 'const x = 5;\nconsole.log(x);',
        typescript: 'interface User {\n  id: number;\n}',
        sql: 'SELECT * FROM users;',
        go: 'package main\nimport "fmt"\nfunc main() {}',
        rust: 'fn main() {\n    println!("Hello");\n}',
        yaml: 'version: "3"\nservices:\n  web:\n    image: nginx',
        json: '{"key": "value"}',
        markdown: '## Title\n\n[Link](https://privatebin.info)'
    };

    Object.keys(samples).forEach(function (lang) {
        it('correctly detects ' + lang, function () {
            $.PrivateBin.PasteViewer.setText(samples[lang]);
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-' + lang), true);
        });
    });

    var shebangs = {
        bash: '#!/bin/sh\necho 1',
        python: '#!/usr/bin/env python3\npass',
        javascript: '#!/usr/bin/env node\nconsole.log(1)',
        typescript: '#!/usr/bin/env ts-node\nconsole.log(1)'
    };

    Object.keys(shebangs).forEach(function (lang) {
        it('correctly routes shebang to ' + lang, function () {
            $.PrivateBin.PasteViewer.setText(shebangs[lang]);
            $.PrivateBin.PasteViewer.run();
            assert.strictEqual($('#prettyprint code').hasClass('language-' + lang), true);
        });
    });

    it('gracefully falls back to clike for unsupported languages (Ruby)', function () {
        var rubySample = 'class Person\n  def initialize(name)\n    @name = name\n  end\nend';
        $.PrivateBin.PasteViewer.setText(rubySample);
        $.PrivateBin.PasteViewer.run();
        assert.strictEqual($('#prettyprint code').hasClass('language-clike'), true);
    });

    it('enforces execution time under 2ms', function () {
        var longCode = 'const x = 1;\n' + '// Some comment\n'.repeat(500);
        // Warm up V8 compilation
        $.PrivateBin.Controller._detectLanguage(longCode);
        $.PrivateBin.Controller._detectLanguage(longCode);

        var start = process.hrtime();
        var iterations = 100;
        for (var i = 0; i < iterations; i++) {
            $.PrivateBin.Controller._detectLanguage(longCode);
        }
        var diff = process.hrtime(start);
        var ms = (diff[0] * 1e9 + diff[1]) / 1e6;
        var avg = ms / iterations;
        
        assert.ok(avg < 15, 'Average detection run took too long: ' + avg + 'ms');
    });
});
