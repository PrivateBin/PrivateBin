'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('PasswordPeek', function () {
    afterEach(() => {
        globalThis.cleanup();
    });

    const markup = (
        '<div class="input-group">' +
        '<input type="password" id="passwordinput" class="form-control input-password" />' +
        '<button type="button" class="toggle-password" title="Show password">' +
        '<svg><use href="img/bootstrap-icons.svg#eye" /></svg></button>' +
        '<button type="button" class="generate-password">' +
        '<svg><use href="img/bootstrap-icons.svg#repeat" /></svg></button>' +
        '</div>'
    );

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
            if (seen.size !== 100) {
                throw new Error('duplicate passwords generated');
            }
        });
    });

    describe('buttons', function () {
        it('the eye shows and hides the password', function () {
            document.body.innerHTML = markup;
            PrivateBin.PasswordPeek.init();
            const input = document.getElementById('passwordinput');
            const eye = document.querySelector('.toggle-password');
            eye.click();
            if (input.type !== 'text' || !eye.querySelector('use').getAttribute('href').endsWith('#eye-slash')) {
                throw new Error('password not shown');
            }
            eye.click();
            if (input.type !== 'password' || !eye.querySelector('use').getAttribute('href').endsWith('#eye')) {
                throw new Error('password not hidden');
            }
        });
        it('every eye on the page works, not only the first', function () {
            document.body.innerHTML = markup + markup.replace('id="passwordinput"', 'id="second"');
            PrivateBin.PasswordPeek.init();
            document.querySelectorAll('.toggle-password')[1].click();
            if (document.getElementById('second').type !== 'text') {
                throw new Error('second eye did not work');
            }
        });
        it('the generate button fills in and shows a password', function () {
            document.body.innerHTML = markup;
            PrivateBin.PasswordPeek.init();
            const input = document.getElementById('passwordinput');
            let inputEvents = 0;
            input.addEventListener('input', () => ++inputEvents);
            document.querySelector('.generate-password').click();
            if (input.value.length !== 20 || input.type !== 'text' || inputEvents !== 1) {
                throw new Error('generate failed: ' + input.value + ' ' + input.type + ' ' + inputEvents);
            }
        });
    });
});
