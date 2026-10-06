'use strict';
const fs = require('node:fs');
const path = require('node:path');

function loadTemplateFragment(id) {
    const template = fs.readFileSync(path.resolve(__dirname, '../../tpl/bootstrap5.php'), 'utf8');
    const match = template.match(new RegExp(`<div id="${id}"[^>]*>[\\s\\S]*?<\\/div>`));

    if (!match) {
        throw new Error(`Template fragment #${id} not found`);
    }

    return match[0]
        .replace(/<\?php[\s\S]*?\?>/g, '')
        .replace(/<\?=[\s\S]*?\?>/g, '')
        .replace(/>\s+</g, '><')
        .trim();
}
exports.loadTemplateFragment = loadTemplateFragment;
