'use strict';
require('../common');

describe('Memory', function () {
    describe('init', function () {
        it(
            'enables toggling the memory sidebar',
            function() {
                $('body').html(
                    '<main><div id="sidebar-wrapper"></div>' +
                    '<button id="menu-toggle"></button></main>'
                );
                assert.ok(!$('main').hasClass('toggled'));

                $('#menu-toggle').click();
                assert.ok(!$('main').hasClass('toggled'));

                $.PrivateBin.Memory.init();
                assert.ok(!$('main').hasClass('toggled'));

                $('#menu-toggle').click();
                assert.ok($('main').hasClass('toggled'));

                $('#menu-toggle').click();
                assert.ok(!$('main').hasClass('toggled'));
            }
        );
    });
});
