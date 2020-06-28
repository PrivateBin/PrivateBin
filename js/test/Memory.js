'use strict';
const common = require('../common');

describe('Memory', function () {
    describe('add', function () {
        this.timeout(30000);

        jsc.property(
            'allows adding valid paste URLs',
            common.jscSchemas(),
            jsc.nearray(common.jscA2zString()),
            jsc.array(common.jscQueryString()),
            'string',
            function (schema, address, query, fragment) {
                const expected = schema + '://' + address.join('') + '/?' +
                      encodeURI(
                          query.join('').replace(/^&+|&+$/gm,'') + '#' + fragment
                      ),
                      clean = jsdom();
                $('body').html(
                    '<main><div id="sidebar-wrapper"><table><tbody>' +
                    '</tbody></table></div></main>'
                );
                // clear cache, then the first cell will match what we add
                $.PrivateBin.Memory.init();
                $.PrivateBin.Memory.add(expected);
                clean();
                return true;
            }
        );
    });
    
    describe('init', function () {
        it(
            'enables toggling the memory sidebar',
            function() {
                $('body').html(
                    '<main><div id="sidebar-wrapper"><table><tbody></tbody>' +
                    '</table></div><button id="menu-toggle"></button></main>'
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
