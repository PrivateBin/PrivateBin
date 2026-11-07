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
                globalThis.cleanup();
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

    describe('generatePassword', function () {
        it('creates 20 characters by default', function () {
            fc.assert(fc.property(fc.integer(), function () {
                return PrivateBin.PasswordPeek.generatePassword().length === 20;
            }), {numRuns: 200});
        });

        it('creates the requested number of characters', function () {
            fc.assert(fc.property(fc.integer({min: 4, max: 128}), function (length) {
                return PrivateBin.PasswordPeek.generatePassword(length).length === length;
            }));
        });

        it('never uses look-alike characters', function () {
            fc.assert(fc.property(fc.integer(), function () {
                return !/[0OoIl1]/.test(PrivateBin.PasswordPeek.generatePassword());
            }), {numRuns: 500});
        });

        it('uses upper and lower case letters, digits and punctuation', function () {
            fc.assert(fc.property(fc.integer(), function () {
                const password = PrivateBin.PasswordPeek.generatePassword();
                return /[A-Z]/.test(password) && /[a-z]/.test(password) &&
                    /[2-9]/.test(password) && /[!@#$%&*+\-=?^_~]/.test(password) &&
                    /^[A-Za-z2-9!@#$%&*+\-=?^_~]+$/.test(password);
            }), {numRuns: 500});
        });

        it('creates a different password every time', function () {
            const seen = new Set();
            for (let i = 0; i < 100; ++i) {
                seen.add(PrivateBin.PasswordPeek.generatePassword());
            }
            assert.strictEqual(seen.size, 100);
        });
    });

    describe('generate button', function () {
        beforeEach(function () {
            globalThis.cleanup();
            document.body.innerHTML = (
                passwordGroup('passwordinput').replace(
                    '</button></div>',
                    '</button><button class="generate-password" type="button">' +
                    '<svg><use href="img/bootstrap-icons.svg#repeat" /></svg></button></div>'
                )
            );
            PrivateBin.PasswordPeek.init();
        });

        it('fills in a password and shows it', function () {
            const input = document.getElementById('passwordinput');
            let inputEvents = 0;
            input.addEventListener('input', () => ++inputEvents);
            document.querySelector('.generate-password').click();
            assert.strictEqual(input.value.length, 20);
            assert.strictEqual(inputEvents, 1);
            assertState('passwordinput', true);
        });

        it('keeps a shown password shown when generating again', function () {
            document.querySelector('.generate-password').click();
            const first = document.getElementById('passwordinput').value;
            document.querySelector('.generate-password').click();
            assert.notStrictEqual(document.getElementById('passwordinput').value, first);
            assertState('passwordinput', true);
        });
    });
});
