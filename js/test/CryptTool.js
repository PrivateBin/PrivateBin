'use strict';
require('../common');

describe('CryptTool', function () {
    describe('cipher & decipher', function () {
        this.timeout(30000);
        it('can en- and decrypt any message', function () {
            jsc.check(jsc.forall(
                'string',
                'string',
                'string',
                function (key, password, message) {
                    return message === $.PrivateBin.CryptTool.decipher(
                        key,
                        password,
                        $.PrivateBin.CryptTool.cipher(key, password, message)
                    );
                }
            ),
            // reducing amount of checks as running 100 takes about 5 minutes
            {tests: 5, quiet: true});
        });

        // The below static unit tests are included to ensure deciphering of "classic"
        // SJCL based pastes still works
        it(
            'supports PrivateBin v1 ciphertext (SJCL & Base64)',
            function () {
                // Of course you can easily decipher the following texts, if you like.
                // Bonus points for finding their sources and hidden meanings.
                var paste1 = $.PrivateBin.CryptTool.decipher(
                    '6t2qsmLyfXIokNCL+3/yl15rfTUBQvm5SOnFPvNE7Q8=',
                    // -- "That's amazing. I've got the same combination on my luggage."
                    Array.apply(0, Array(6)).map(function(_,b) { return b + 1; }).join(''),
                    '{"iv":"4HNFIl7eYbCh6HuShctTIA==","v":1,"iter":10000,"ks"' +
                    ':256,"ts":128,"mode":"gcm","adata":"","cipher":"aes","sa' +
                    'lt":"u0lQvePq6L0=","ct":"fGPUVrDyaVr1ZDGb+kqQ3CPEW8x4YKG' +
                    'fzHDmA0Vjkh250aWNe7Cnigkps9aaFVMX9AaerrTp3yZbojJtNqVGMfL' +
                    'dUTu+53xmZHqRKxCCqSfDNSNoW4Oxk5OVgAtRyuG4bXHDsWTXDNz2xce' +
                    'qzVFqhkwTwlUchrV7uuFK/XUKTNjPFM744moivIcBbfM2FOeKlIFs8RY' +
                    'PYuvqQhp2rMLlNGwwKh//4kykQsHMQDeSDuJl8stMQzgWR/btUBZuwNZ' +
                    'EydkMH6IPpTdf5WTSrZ+wC2OK0GutCm4UaEe6txzaTMfu+WRVu4PN6q+' +
                    'N+2zljWJ1XdpVcN/i0Sv4QVMym0Xa6y0eccEhj/69o47PmExmMMeEwEx' +
                    'ImPalMNT9JUSiZdOZJ/GdzwrwoIuq1mdQR6vSH+XJ/8jXJQ7bjjJVJYX' +
                    'TcT0Di5jixArI2Kpp1GGlGVFbLgPugwU1wczg+byqeDOAECXRRnQcoge' +
                    'aJtVcRwXwfy4j3ORFcblYMilxyHqKBewcYPRVBGtBs50cVjSIkAfR84r' +
                    'nc1nfvnxK/Gmm+4VBNHI6ODWNpRolVMCzXjbKYnV3Are5AgSpsTqaGl4' +
                    '1VJGpcco6cAwi4K0Bys1seKR+bLSdUgqRrkEqSRSdu3/VTu9HhEk8an0' +
                    'rjTE4CBB5/LMn16p0TGLoOb32odKFIEtpanVvLjeyiVMvSxcgYLNnTi/' +
                    '5FiaAC4pJxRD+AZHedU1FICUeEXxIcac/4E5qjkHjX9SpQtLl80QLIVn' +
                    'jNliZm7QLB/nKu7W8Jb0+/CiTdV3Q9LhxlH4ciprnX+W0B00BKYFHnL9' +
                    'jRVzKdXhf1EHydbXMAfpCjHAXIVCkFakJinQBDIIw/SC6Yig0u0ddEID' +
                    '2B7LYAP1iE4RZwzTrxCB+ke2jQr8c20Jj6u6ShFOPC9DCw9XupZ4HAal' +
                    'VG00kSgjus+b8zrVji3/LKEhb4EBzp1ctBJCFTeXwej8ZETLoXTylev5' +
                    'dlwZSYAbuBPPcbFR/xAIPx3uDabd1E1gTqUc68ICIGhd197Mb2eRWiSv' +
                    'Hr5SPsASerMxId6XA6+iQlRiI+NDR+TGVNmCnfxSlyPFMOHGTmslXOGI' +
                    'qGfBR8l4ft8YVZ70lCwmwTuViGc75ULSf9mM57/LmRzQFMYQtvI8IFK9' +
                    'JaQEMY5xz0HLtR4iyQUUdwR9e0ytBNdWF2a2WPDEnJuY/QJo4GzTlgv4' +
                    'QUxMXI5htsn2rf0HxCFu7Po8DNYLxTS+67hYjDIYWYaEIc8LXWMLyDm9' +
                    'C5fARPJ4F2BIWgzgzkNj+dVjusft2XnziamWdbS5u3kuRlVuz5LQj+R5' +
                    'imnqQAincdZTkTT1nYx+DatlOLllCYIHffpI="}'
                ),
                paste2 = $.PrivateBin.CryptTool.decipher(
                    's9pmKZKOBN7EVvHpTA8jjLFH3Xlz/0l8lB4+ONPACrM=',
                    '', // no password
                    '{"iv":"WA42mdxIVXUwBqZu7JYNiw==","v":1,"iter":10000,"ks"' +
                    ':256,"ts":128,"mode":"gcm","adata":"","cipher":"aes","sa' +
                    'lt":"jN6CjbQMJCM=","ct":"kYYMo5DFG1+w0UHiYXT5pdV0IUuXxzO' +
                    'lslkW/c3DRCbGFROCVkAskHce7HoRczee1N9c5MhHjVMJUIZE02qIS8U' +
                    'yHdJ/GqcPVidTUcj9rnDNWsTXkjVv8jCwHS/cwmAjDTWpwp5ThECN+ov' +
                    '/wNp/NdtTj8Qj7f/T3rfZIOCWfwLH9s4Des35UNcUidfPTNQ1l0Gm0X+' +
                    'r98CCUSYZjQxkZc6hRZBLPQ8EaNVooUwd5eP4GiYlmSDNA0wOSA+5isP' +
                    'YxomVCt+kFf58VBlNhpfNi7BLYAUTPpXT4SfH5drR9+C7NTeZ+tTCYjb' +
                    'U94PzYItOpu8vgnB1/a6BAM5h3m9w+giUb0df4hgTWeZnZxLjo5BN8WV' +
                    '+kdTXMj3/Vv0gw0DQrDcCuX/cBAjpy3lQGwlAN1vXoOIyZJUjMpQRrOL' +
                    'dKvLB+zcmVNtGDbgnfP2IYBzk9NtodpUa27ne0T0ZpwOPlVwevsIVZO2' +
                    '24WLa+iQmmHOWDFFpVDlS0t0fLfOk7Hcb2xFsTxiCIiyKMho/IME1Du3' +
                    'X4e6BVa3hobSSZv0rRtNgY1KcyYPrUPW2fxZ+oik3y9SgGvb7XpjVIta' +
                    '8DWlDWRfZ9kzoweWEYqz9IA8Xd373RefpyuWI25zlHoX3nwljzsZU6dC' +
                    '//h/Dt2DNr+IAvKO3+u23cWoB9kgcZJ2FJuqjLvVfCF+OWcig7zs2pTY' +
                    'JW6Rg6lqbBCxiUUlae6xJrjfv0pzD2VYCLY7v1bVTagppwKzNI3WaluC' +
                    'OrdDYUCxUSe56yd1oAoLPRVbYvomRboUO6cjQhEknERyvt45og2kORJO' +
                    'EJayHW+jZgR0Y0jM3Nk17ubpij2gHxNx9kiLDOiCGSV5mn9mV7qd3HHc' +
                    'OMSykiBgbyzjobi96LT2dIGLeDXTIdPOog8wyobO4jWq0GGs0vBB8oSY' +
                    'XhHvixZLcSjX2KQuHmEoWzmJcr3DavdoXZmAurGWLKjzEdJc5dSD/eNr' +
                    '99gjHX7wphJ6umKMM+fn6PcbYJkhDh2GlJL5COXjXfm/5aj/vuyaRRWZ' +
                    'MZtmnYpGAtAPg7AUG"}'
                );

                assert.ok(
                    paste1.includes('securely packed in iron') &&
                    paste2.includes('Sol is right')
                );
            }
        );

        it(
            'supports ZeroBin ciphertext (SJCL & Base64 1.7)',
            function () {
                var newBase64 = global.Base64;
                global.Base64 = require('../base64-1.7').Base64;
                jsdom();
                delete require.cache[require.resolve('../privatebin')];
                require('../privatebin');

                // Of course you can easily decipher the following texts, if you like.
                // Bonus points for finding their sources and hidden meanings.
                var paste1 = $.PrivateBin.CryptTool.decipher(
                    '6t2qsmLyfXIokNCL+3/yl15rfTUBQvm5SOnFPvNE7Q8=',
                    // -- "That's amazing. I've got the same combination on my luggage."
                    Array.apply(0, Array(6)).map(function(_,b) { return b + 1; }).join(''),
                    '{"iv":"aTnR2qBL1CAmLX8FdWe3VA==","v":1,"iter":10000,"ks"' +
                    ':256,"ts":128,"mode":"gcm","adata":"","cipher":"aes","sa' +
                    'lt":"u0lQvePq6L0=","ct":"A3nBTvICZtYy6xqbIJE0c8Veored5lM' +
                    'JUGgGUm4581wjrPFlU0Q0tUZSf+RUUoZj2jqDa4kiyyZ5YNMe30hNMV0' +
                    'oVSalNhRgD9svVMnPuF162IbyhVCwr7ULjT981CHxVlGNqGqmIU6L/Xi' +
                    'xgdArxAA8x1GCrfAkBWWGeq8Qw5vJPG/RCHpwR4Wy3azrluqeyERBzma' +
                    'OQjO/kM35TiI6IrLYFyYyL7upYlxAaxS0XBMZvN8QU8Lnerwvh5JVC6O' +
                    'kkKrhogajTJIKozCF79yI78c50LUh7tTuI3Yoh7+fXxhoODvQdYFmoiU' +
                    'lrutN7Y5ZMRdITvVu8fTYtX9c7Fiufmcq5icEimiHp2g1bvfpOaGOsFT' +
                    '+XNFgC9215jcp5mpBdN852xs7bUtw+nDrf+LsDEX6iRpRZ+PYgLDN5xQ' +
                    'T1ByEtYbeP+tO38pnx72oZdIB3cj8UkOxnxdNiZM5YB5egn4jUj1fHot' +
                    '1I69WoTiUJipZ5PIATv7ScymRB+AYzjxjurQ9lVfX9QtAbEH2dhdmoUo' +
                    '3IDRSXpWNCe9RC1aUIyWfZO7oI7FEohNscHNTLEcT+wFnFUPByLlXmjN' +
                    'Z7FKeNpvUm3jTY4t4sbZH8o2dUl624PAw1INcJ6FKqWGWwoFT2j1MYC+' +
                    'YV/LkLTdjuWfayvwLMh27G/FfKCRbW36vqinegqpPDylsx9+3oFkEw3y' +
                    '5Z8+44oN91rE/4Md7JhPJeRVlFC9TNCj4dA+EVhbbQqscvSnIH2uHkMw' +
                    '7mNNo7xba/YT9KoPDaniqnYqb+q2pX1WNWE7dLS2wfroMAS3kh8P22DA' +
                    'V37AeiNoD2PcI6ZcHbRdPa+XRrRcJhSPPW7UQ0z4OvBfjdu/w390QxAx' +
                    'SxvZewoh49fKKB6hTsRnZb4tpHkjlww=="}'
                ),
                paste2 = $.PrivateBin.CryptTool.decipher(
                    's9pmKZKOBN7EVvHpTA8jjLFH3Xlz/0l8lB4+ONPACrM=',
                    '', // no password
                    '{"iv":"Z7lAZQbkrqGMvruxoSm6Pw==","v":1,"iter":10000,"ks"' +
                    ':256,"ts":128,"mode":"gcm","adata":"","cipher":"aes","sa' +
                    'lt":"jN6CjbQMJCM=","ct":"PuOPWB3i2FPcreSrLYeQf84LdE8RHjs' +
                    'c+MGtiOr4b7doNyWKYtkNorbRadxaPnEee2/Utrp1MIIfY5juJSy8RGw' +
                    'EPX5ciWcYe6EzsXWznsnvhmpKNj9B7eIIrfSbxfy8E2e/g7xav1nive+' +
                    'ljToka3WT1DZ8ILQd/NbnJeHWaoSEOfvz8+d8QJPb1tNZvs7zEY95Dum' +
                    'QwbyOsIMKAvcZHJ9OJNpujXzdMyt6DpcFcqlldWBZ/8q5rAUTw0HNx/r' +
                    'CgbhAxRYfNoTLIcMM4L0cXbPSgCjwf5FuO3EdE13mgEDhcClW79m0Qvc' +
                    'nIh8xgzYoxLbp0+AwvC/MbZM8savN/0ieWr2EKkZ04ggiOIEyvfCUuNp' +
                    'rQBYO+y8kKduNEN6by0Yf4LRCPfmwN+GezDLuzTnZIMhPbGqUAdgV6Ex' +
                    'qK2ULEEIrQEMoOuQIxfoMhqLlzG79vXGt2O+BY+4IiYfvmuRLks4UXfy' +
                    'HqxPXTJg48IYbGs0j4TtJPUgp3523EyYLwEGyVTAuWhYAmVIwd/hoV7d' +
                    '7tmfcF73w9dufDFI3LNca2KxzBnWNPYvIZKBwWbq8ncxkb191dP6mjEi' +
                    '7NnhqVk5A6vIBbu4AC5PZf76l6yep4xsoy/QtdDxCMocCXeAML9MQ9uP' +
                    'QbuspOKrBvMfN5igA1kBqasnxI472KBNXsdZnaDddSVUuvhTcETM="}'
                );

                global.Base64 = newBase64;
                jsdom();
                delete require.cache[require.resolve('../privatebin')];
                require('../privatebin');
                assert.ok(
                    paste1.includes('securely packed in iron') &&
                    paste2.includes('Sol is right')
                );
            }
        );
    });

    describe('getSymmetricKey', function () {
        var keys = [];

        // the parameter is used to ensure the test is run more then one time
        jsc.property(
            'returns random, non-empty keys',
            'integer',
            function(counter) {
                var key = $.PrivateBin.CryptTool.getSymmetricKey(),
                    result = (key !== '' && keys.indexOf(key) === -1);
                keys.push(key);
                return result;
            }
        );
    });

    describe('Base64.js vs SJCL.js vs abab.js', function () {
        jsc.property(
            'these all return the same base64 string',
            'string',
            function(string) {
                var base64 = Base64.toBase64(string),
                    sjcl = global.sjcl.codec.base64.fromBits(global.sjcl.codec.utf8String.toBits(string)),
                    abab = window.btoa(Base64.utob(string)),
                    esab46 = Base64.fromBase64(sjcl),
                    lcjs = global.sjcl.codec.utf8String.fromBits(global.sjcl.codec.base64.toBits(abab)),
                    baba = Base64.btou(window.atob(base64));
                return base64 === sjcl && sjcl === abab && string === esab46 && esab46 === lcjs && lcjs === baba;
            }
        );
    });
});

