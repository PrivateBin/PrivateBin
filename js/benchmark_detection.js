'use strict';

// Load the exact same JSDOM and mock environment used by unit tests
const common = require('./common');

const samples = {
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

console.log('\n=========================================');
console.log('BENCHMARK: Language Detection (1,000 runs)');
console.log('=========================================\n');

Object.keys(samples).forEach((lang) => {
    const code = samples[lang];
    
    // Cold run (first call)
    const coldStart = process.hrtime();
    const coldResult = $.PrivateBin.Controller._detectLanguage(code);
    const coldDiff = process.hrtime(coldStart);
    const coldMs = (coldDiff[0] * 1e9 + coldDiff[1]) / 1e6;

    // Warmed runs (1000 iterations)
    const start = process.hrtime();
    for (let i = 0; i < 1000; i++) {
        $.PrivateBin.Controller._detectLanguage(code);
    }
    const diff = process.hrtime(start);
    const ms = (diff[0] * 1e9 + diff[1]) / 1e6;
    const avg = (ms / 1000) * 1000; // in microseconds

    console.log(`${lang.padEnd(12)}: Correct? ${coldResult === lang ? 'Yes' : 'No'} | Cold: ${coldMs.toFixed(3)}ms | Warmed Avg: ${avg.toFixed(2)} µs`);
});

console.log('\n=========================================');
process.exit(0);
