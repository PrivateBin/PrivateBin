'use strict';
require('../common');

describe('PasswordPeek', function () {
    afterEach(() => {
        globalThis.cleanup();
    });

    it('binds every password reveal button', function () {
        document.body.innerHTML = `
            <div class="input-group">
                <input id="decrypt-password" class="input-password" type="password">
                <button class="toggle-password" type="button" title="Show password">
                    <svg><use href="#eye"></use></svg>
                </button>
            </div>
            <div class="input-group">
                <input id="create-password" class="input-password" type="password">
                <button class="toggle-password" type="button" title="Show password">
                    <svg><use href="#eye"></use></svg>
                </button>
            </div>
        `;

        PrivateBin.PasswordPeek.init();

        const revealButtons = document.querySelectorAll('.toggle-password');
        revealButtons[0].click();
        revealButtons[1].click();

        assert.strictEqual(document.getElementById('decrypt-password').type, 'text');
        assert.strictEqual(document.getElementById('create-password').type, 'text');
    });
});
