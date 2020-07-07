'use strict';
const common = require('../common');

describe('Memory', function () {
    describe('add & refreshList', function () {
        this.timeout(30000);

        jsc.property(
            'allows adding valid paste URLs',
            common.jscSchemas(),
            jsc.nearray(common.jscA2zString()),
            jsc.array(common.jscQueryString()),
            'string',
            function (schema, address, query, fragment) {
                const expectedQuery = encodeURI(
                          query.join('').replace(/^&+|&+$/gm,'')
                      ),
                      expected = schema + '://' + address.join('') + '/?' +
                      expectedQuery + '#' + fragment,
                      clean = jsdom();
                $('body').html(
                    '<main><div id="sidebar-wrapper"><table><tbody>' +
                    '</tbody></table></div></main>'
                );
                // clear cache, then the first cell will match what we add
                $.PrivateBin.Memory.init();
                $.PrivateBin.Memory.add(expected);
                $.PrivateBin.Memory.refreshList();
                const result = $('#sidebar-wrapper table tbody tr td')[3].textContent;
                clean();
                return result === expectedQuery;
            }
        );
    });
    
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
