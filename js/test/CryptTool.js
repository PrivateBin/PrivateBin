'use strict';
var common = require('../common');

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
            'supports PrivateBin v1 ciphertext (SJCL & Base64 2.1.9)',
            function () {
                // Of course you can easily decipher the following texts, if you like.
                // Bonus points for finding their sources and hidden meanings.
                var paste1 = $.PrivateBin.CryptTool.decipher(
                    '6t2qsmLyfXIokNCL+3/yl15rfTUBQvm5SOnFPvNE7Q8=',
                    // -- "That's amazing. I've got the same combination on my luggage."
                    Array.apply(0, Array(6)).map(function(_,b) { return b + 1; }).join(''),
                    '{"iv":"4HNFIl7eYbCh6HuShctTIA==","v":1,"iter":10000,"ks":256,"ts":128,"mode":"gcm","adata":"","cipher":"aes","salt":"u0lQvePq6L0=","ct":"fGPUVrDyaVr1ZDGb+kqQ3CPEW8x4YKGfzHDmA0Vjkh250aWNe7Cnigkps9aaFVMX9AaerrTp3yZbojJtNqVGMfLdUTu+53xmZHqRKxCCqSfDNSNoW4Oxk5OVgAtRyuG4bXHDsWTXDNz2xceqzVFqhkwTwlUchrV7uuFK/XUKTNjPFM744moivIcBbfM2FOeKlIFs8RYPYuvqQhp2rMLlNGwwKh//4kykQsHMQDeSDuJl8stMQzgWR/btUBZuwNZEydkMH6IPpTdf5WTSrZ+wC2OK0GutCm4UaEe6txzaTMfu+WRVu4PN6q+N+2zljWJ1XdpVcN/i0Sv4QVMym0Xa6y0eccEhj/69o47PmExmMMeEwExImPalMNT9JUSiZdOZJ/GdzwrwoIuq1mdQR6vSH+XJ/8jXJQ7bjjJVJYXTcT0Di5jixArI2Kpp1GGlGVFbLgPugwU1wczg+byqeDOAECXRRnQcogeaJtVcRwXwfy4j3ORFcblYMilxyHqKBewcYPRVBGtBs50cVjSIkAfR84rnc1nfvnxK/Gmm+4VBNHI6ODWNpRolVMCzXjbKYnV3Are5AgSpsTqaGl41VJGpcco6cAwi4K0Bys1seKR+bLSdUgqRrkEqSRSdu3/VTu9HhEk8an0rjTE4CBB5/LMn16p0TGLoOb32odKFIEtpanVvLjeyiVMvSxcgYLNnTi/5FiaAC4pJxRD+AZHedU1FICUeEXxIcac/4E5qjkHjX9SpQtLl80QLIVnjNliZm7QLB/nKu7W8Jb0+/CiTdV3Q9LhxlH4ciprnX+W0B00BKYFHnL9jRVzKdXhf1EHydbXMAfpCjHAXIVCkFakJinQBDIIw/SC6Yig0u0ddEID2B7LYAP1iE4RZwzTrxCB+ke2jQr8c20Jj6u6ShFOPC9DCw9XupZ4HAalVG00kSgjus+b8zrVji3/LKEhb4EBzp1ctBJCFTeXwej8ZETLoXTylev5dlwZSYAbuBPPcbFR/xAIPx3uDabd1E1gTqUc68ICIGhd197Mb2eRWiSvHr5SPsASerMxId6XA6+iQlRiI+NDR+TGVNmCnfxSlyPFMOHGTmslXOGIqGfBR8l4ft8YVZ70lCwmwTuViGc75ULSf9mM57/LmRzQFMYQtvI8IFK9JaQEMY5xz0HLtR4iyQUUdwR9e0ytBNdWF2a2WPDEnJuY/QJo4GzTlgv4QUxMXI5htsn2rf0HxCFu7Po8DNYLxTS+67hYjDIYWYaEIc8LXWMLyDm9C5fARPJ4F2BIWgzgzkNj+dVjusft2XnziamWdbS5u3kuRlVuz5LQj+R5imnqQAincdZTkTT1nYx+DatlOLllCYIHffpI="}'
                ),
                paste2 = $.PrivateBin.CryptTool.decipher(
                    's9pmKZKOBN7EVvHpTA8jjLFH3Xlz/0l8lB4+ONPACrM=',
                    '', // no password
                    '{"iv":"WA42mdxIVXUwBqZu7JYNiw==","v":1,"iter":10000,"ks":256,"ts":128,"mode":"gcm","adata":"","cipher":"aes","salt":"jN6CjbQMJCM=","ct":"kYYMo5DFG1+w0UHiYXT5pdV0IUuXxzOlslkW/c3DRCbGFROCVkAskHce7HoRczee1N9c5MhHjVMJUIZE02qIS8UyHdJ/GqcPVidTUcj9rnDNWsTXkjVv8jCwHS/cwmAjDTWpwp5ThECN+ov/wNp/NdtTj8Qj7f/T3rfZIOCWfwLH9s4Des35UNcUidfPTNQ1l0Gm0X+r98CCUSYZjQxkZc6hRZBLPQ8EaNVooUwd5eP4GiYlmSDNA0wOSA+5isPYxomVCt+kFf58VBlNhpfNi7BLYAUTPpXT4SfH5drR9+C7NTeZ+tTCYjbU94PzYItOpu8vgnB1/a6BAM5h3m9w+giUb0df4hgTWeZnZxLjo5BN8WV+kdTXMj3/Vv0gw0DQrDcCuX/cBAjpy3lQGwlAN1vXoOIyZJUjMpQRrOLdKvLB+zcmVNtGDbgnfP2IYBzk9NtodpUa27ne0T0ZpwOPlVwevsIVZO224WLa+iQmmHOWDFFpVDlS0t0fLfOk7Hcb2xFsTxiCIiyKMho/IME1Du3X4e6BVa3hobSSZv0rRtNgY1KcyYPrUPW2fxZ+oik3y9SgGvb7XpjVIta8DWlDWRfZ9kzoweWEYqz9IA8Xd373RefpyuWI25zlHoX3nwljzsZU6dC//h/Dt2DNr+IAvKO3+u23cWoB9kgcZJ2FJuqjLvVfCF+OWcig7zs2pTYJW6Rg6lqbBCxiUUlae6xJrjfv0pzD2VYCLY7v1bVTagppwKzNI3WaluCOrdDYUCxUSe56yd1oAoLPRVbYvomRboUO6cjQhEknERyvt45og2kORJOEJayHW+jZgR0Y0jM3Nk17ubpij2gHxNx9kiLDOiCGSV5mn9mV7qd3HHcOMSykiBgbyzjobi96LT2dIGLeDXTIdPOog8wyobO4jWq0GGs0vBB8oSYXhHvixZLcSjX2KQuHmEoWzmJcr3DavdoXZmAurGWLKjzEdJc5dSD/eNr99gjHX7wphJ6umKMM+fn6PcbYJkhDh2GlJL5COXjXfm/5aj/vuyaRRWZMZtmnYpGAtAPg7AUG"}'
                );

                if (!paste1.includes('securely packed in iron') || !paste2.includes('Sol is right')) {
                    throw Error('v1 (SJCL based) pastes could not be deciphered');
                }
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
                    '{"iv":"aTnR2qBL1CAmLX8FdWe3VA==","v":1,"iter":10000,"ks":256,"ts":128,"mode":"gcm","adata":"","cipher":"aes","salt":"u0lQvePq6L0=","ct":"A3nBTvICZtYy6xqbIJE0c8Veored5lMJUGgGUm4581wjrPFlU0Q0tUZSf+RUUoZj2jqDa4kiyyZ5YNMe30hNMV0oVSalNhRgD9svVMnPuF162IbyhVCwr7ULjT981CHxVlGNqGqmIU6L/XixgdArxAA8x1GCrfAkBWWGeq8Qw5vJPG/RCHpwR4Wy3azrluqeyERBzmaOQjO/kM35TiI6IrLYFyYyL7upYlxAaxS0XBMZvN8QU8Lnerwvh5JVC6OkkKrhogajTJIKozCF79yI78c50LUh7tTuI3Yoh7+fXxhoODvQdYFmoiUlrutN7Y5ZMRdITvVu8fTYtX9c7Fiufmcq5icEimiHp2g1bvfpOaGOsFT+XNFgC9215jcp5mpBdN852xs7bUtw+nDrf+LsDEX6iRpRZ+PYgLDN5xQT1ByEtYbeP+tO38pnx72oZdIB3cj8UkOxnxdNiZM5YB5egn4jUj1fHot1I69WoTiUJipZ5PIATv7ScymRB+AYzjxjurQ9lVfX9QtAbEH2dhdmoUo3IDRSXpWNCe9RC1aUIyWfZO7oI7FEohNscHNTLEcT+wFnFUPByLlXmjNZ7FKeNpvUm3jTY4t4sbZH8o2dUl624PAw1INcJ6FKqWGWwoFT2j1MYC+YV/LkLTdjuWfayvwLMh27G/FfKCRbW36vqinegqpPDylsx9+3oFkEw3y5Z8+44oN91rE/4Md7JhPJeRVlFC9TNCj4dA+EVhbbQqscvSnIH2uHkMw7mNNo7xba/YT9KoPDaniqnYqb+q2pX1WNWE7dLS2wfroMAS3kh8P22DAV37AeiNoD2PcI6ZcHbRdPa+XRrRcJhSPPW7UQ0z4OvBfjdu/w390QxAxSxvZewoh49fKKB6hTsRnZb4tpHkjlww=="}'
                ),
                paste2 = $.PrivateBin.CryptTool.decipher(
                    's9pmKZKOBN7EVvHpTA8jjLFH3Xlz/0l8lB4+ONPACrM=',
                    '', // no password
                    '{"iv":"Z7lAZQbkrqGMvruxoSm6Pw==","v":1,"iter":10000,"ks":256,"ts":128,"mode":"gcm","adata":"","cipher":"aes","salt":"jN6CjbQMJCM=","ct":"PuOPWB3i2FPcreSrLYeQf84LdE8RHjsc+MGtiOr4b7doNyWKYtkNorbRadxaPnEee2/Utrp1MIIfY5juJSy8RGwEPX5ciWcYe6EzsXWznsnvhmpKNj9B7eIIrfSbxfy8E2e/g7xav1nive+ljToka3WT1DZ8ILQd/NbnJeHWaoSEOfvz8+d8QJPb1tNZvs7zEY95DumQwbyOsIMKAvcZHJ9OJNpujXzdMyt6DpcFcqlldWBZ/8q5rAUTw0HNx/rCgbhAxRYfNoTLIcMM4L0cXbPSgCjwf5FuO3EdE13mgEDhcClW79m0QvcnIh8xgzYoxLbp0+AwvC/MbZM8savN/0ieWr2EKkZ04ggiOIEyvfCUuNprQBYO+y8kKduNEN6by0Yf4LRCPfmwN+GezDLuzTnZIMhPbGqUAdgV6ExqK2ULEEIrQEMoOuQIxfoMhqLlzG79vXGt2O+BY+4IiYfvmuRLks4UXfyHqxPXTJg48IYbGs0j4TtJPUgp3523EyYLwEGyVTAuWhYAmVIwd/hoV7d7tmfcF73w9dufDFI3LNca2KxzBnWNPYvIZKBwWbq8ncxkb191dP6mjEi7NnhqVk5A6vIBbu4AC5PZf76l6yep4xsoy/QtdDxCMocCXeAML9MQ9uPQbuspOKrBvMfN5igA1kBqasnxI472KBNXsdZnaDddSVUuvhTcETM="}'
                );

                global.Base64 = newBase64;
                jsdom();
                delete require.cache[require.resolve('../privatebin')];
                require('../privatebin');
                if (!paste1.includes('securely packed in iron') || !paste2.includes('Sol is right')) {
                    throw Error('v1 (SJCL based) pastes could not be deciphered');
                }
            }
        );
    });

    describe('isEntropyReady & addEntropySeedListener', function () {
        it(
            'lets us know that enough entropy is collected or make us wait for it',
            function(done) {
                if ($.PrivateBin.CryptTool.isEntropyReady()) {
                    done();
                } else {
                    $.PrivateBin.CryptTool.addEntropySeedListener(function() {
                        done();
                    });
                }
            }
        );
    });

    describe('getSymmetricKey', function () {
        var keys = [];

        // the parameter is used to ensure the test is run more then one time
        jsc.property(
            'returns random, non-empty keys',
            'nat',
            function(n) {
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
                    abab = window.btoa(Base64.utob(string));
                return base64 === sjcl && sjcl === abab;
            }
        );
    });
});

