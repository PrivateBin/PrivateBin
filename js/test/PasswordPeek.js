'use strict';
require('../common');
const fc = require('fast-check');

describe('PasswordPeek', function () {
    const iconHref = 'img/bootstrap-icons.svg#eye';

    /**
     * input group with a password field and its reveal button, as in the
     * bootstrap5 template
     *
     * @param {string} id
     * @returns {string}
     */
    function passwordGroup(id) {
        return (
            '<div class="input-group">' +
            '<input id="' + id + '" type="password" class="form-control input-password" />' +
            '<button class="btn btn-outline-secondary toggle-password" type="button" ' +
            'title="Show password" aria-label="Show password">' +
            '<svg width="16" height="16" fill="currentColor" aria-hidden="true">' +
            '<use href="' + iconHref + '" /></svg>' +
            '</button></div>'
        );
    }

    /**
     * asserts that the input and its reveal button are in the expected state
     *
     * @param {string} id
     * @param {boolean} isRevealed
     */
    function assertState(id, isRevealed) {
        const input = document.getElementById(id);
        const button = input.closest('.input-group').querySelector('.toggle-password');
        const label = isRevealed ? 'Hide password' : 'Show password';
        assert.strictEqual(input.getAttribute('type'), isRevealed ? 'text' : 'password', id + ' type');
        assert.strictEqual(button.getAttribute('title'), label, id + ' title');
        assert.strictEqual(button.getAttribute('aria-label'), label, id + ' aria-label');
        assert.strictEqual(
            button.querySelector('use').getAttribute('href'),
            isRevealed ? iconHref + '-slash' : iconHref,
            id + ' icon'
        );
    }

    /**
     * clicks the reveal button belonging to the given input
     *
     * @param {string} id
     */
    function clickButton(id) {
        document.getElementById(id).closest('.input-group')
            .querySelector('.toggle-password').click();
    }

    beforeEach(function () {
        globalThis.cleanup();
        // same order as in the template: the decryption prompt comes before
        // the password input of the create form
        document.body.innerHTML = (
            '<div id="passwordmodal"><form id="passwordform">' +
            passwordGroup('passworddecrypt') +
            '</form></div>' +
            '<nav><div id="password">' +
            passwordGroup('passwordinput') +
            '</div></nav>'
        );
        PrivateBin.PasswordPeek.init();
    });

    after(function () {
        globalThis.cleanup();
    });

    it('toggles the password prompt when opening a document', function () {
        clickButton('passworddecrypt');
        assertState('passworddecrypt', true);
        assertState('passwordinput', false);
        clickButton('passworddecrypt');
        assertState('passworddecrypt', false);
        assertState('passwordinput', false);
    });

    it('toggles the password input when creating a document', function () {
        clickButton('passwordinput');
        assertState('passwordinput', true);
        assertState('passworddecrypt', false);
        clickButton('passwordinput');
        assertState('passwordinput', false);
        assertState('passworddecrypt', false);
    });

    it('toggles each input independently, in any click order', function () {
        fc.assert(fc.property(
            fc.integer({min: 1, max: 5}).chain(function (count) {
                return fc.tuple(
                    fc.constant(count),
                    fc.array(fc.nat({max: count - 1}), {maxLength: 20})
                );
            }),
            function ([count, clicks]) {
                // replacing the markup is enough, init binds to the new elements
                const ids = [];
                for (let i = 0; i < count; ++i) {
                    ids.push('password' + i);
                }
                document.body.innerHTML = ids.map(passwordGroup).join('');
                PrivateBin.PasswordPeek.init();

                const revealed = ids.map(() => false);
                for (const index of clicks) {
                    clickButton(ids[index]);
                    revealed[index] = !revealed[index];
                }
                ids.forEach(function (id, index) {
                    assertState(id, revealed[index]);
                });
            }
        ), {numRuns: 50});
    });

    it('ignores a reveal button without a password input', function () {
        globalThis.cleanup();
        document.body.innerHTML = (
            '<div class="input-group">' +
            '<button class="toggle-password" type="button" title="Show password">' +
            '<svg><use href="' + iconHref + '" /></svg></button></div>' +
            '<button class="toggle-password" id="orphan" type="button" title="Show password"></button>'
        );
        PrivateBin.PasswordPeek.init();
        document.querySelectorAll('.toggle-password').forEach(button => button.click());
        document.querySelectorAll('.toggle-password').forEach(function (button) {
            assert.strictEqual(button.getAttribute('title'), 'Show password');
        });
        assert.strictEqual(document.querySelector('use').getAttribute('href'), iconHref);
    });
});
