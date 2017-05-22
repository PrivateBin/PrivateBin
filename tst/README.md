Running PHP unit tests
======================

In order to run these tests, you will need to install the following packages
and their dependencies:
* phpunit
* php-gd
* php-sqlite3
* php-xdebug (for code coverage reports)

Example for Debian and Ubuntu:
```console
$ sudo apt install phpunit php-gd php-sqlite3 php-xdebug
```

To run the tests, change into the `tst` directory and run phpunit:
```console
$ cd PrivateBin/tst
$ phpunit
```

Additionally there is the `ConfigurationTestGenerator`. Based on the
configurations defined in its constructor, it generates the unit test file
`tst/ConfigurationCombinationsTest.php`, containing all possible combinations
of these configurations and tests for (most of the) valid combinations. Some of
combinations can't be tested with this method, i.e. a valid option combined with
an invalid one. Other very specific test cases (i.e. to trigger multiple errors)
are covered in `tst/PrivateBinTest.php`. Here is how to generate the
configuration test and run it:

```console
$ cd PrivateBin/tst
$ php ConfigurationTestGenerator.php
$ phpunit ConfigurationCombinationsTest.php
```

Note that it can take an hour or longer to run the several thousand tests.


Running JavaScript unit tests
=============================

In order to run these tests, you will need to install the following packages
and its dependencies:
* npm

Then you can use the node package manager to install the latest stable release
of mocha and istanbul (for code coverage reports) globally and jsVerify, jsdom
and jsdom-global locally:

```console
$ npm install -g mocha istanbul
$ cd PrivateBin/js
$ npm install jsverify jsdom@9 jsdom-global@2
```

Example for Debian and Ubuntu, including steps to allow the current user to
install node modules globally:
```console
$ sudo apt install npm
$ sudo mkdir /usr/local/lib/node_modules
$ sudo chown -R $(whoami) $(npm config get prefix)/{lib/node_modules,bin,share}
$ ln -s /usr/bin/nodejs /usr/local/bin/node
$ npm install -g mocha istanbul
$ cd PrivateBin/js
$ npm install jsverify jsdom@9 jsdom-global@2
```

Note: If you use a distribution that provides nodeJS >= 6, then you can install
the latest jsdom and jsdom-global packages and don't need to use @9 and @2.

To run the tests, just change into the `js` directory and run istanbul:
```console
$ cd PrivateBin/js
$ istanbul cover _mocha
```

Property based unit testing
---------------------------

In the JavaScript unit tests we use the JSVerify library to leverage property
based unit testing. Instead of artificially creating specific test cases to
cover all relevant paths of the tested code (with the generated coverage reports
providing means to check the tested paths), property based testing allows us to
describe the patterns of data that are valid input.

With each run of the tests, for each `jsc.property` 100 random inputs are
generated and tested. For example we tell the test to generate random strings,
which will include empty strings, numeric strings, long strings, unicode
sequences, etc. This is great for finding corner cases that one might not think
of when explicitly writing one test case at a time.

There is another benefit, too: When an error is found, JSVerify will try to find
the smallest, still failing test case for you and print this out including the
associated random number generator (RNG) state, so you can reproduce it easily:

```console
[...]

  30 passing (3s)
  1 failing

  1) Helper getCookie returns the requested cookie:
     Error: Failed after 30 tests and 11 shrinks. rngState: 88caf85079d32e416b; Counterexample: ["{", "9", "9", "YD8%fT"]; [" ", "_|K:"]; 

[...]
```

Of course it may just be that you need to adjust a test case if the random
pattern generated is ambiguous. In the above example the cookie string would
contain two identical keys "9", something that may not be valid, but that our
code could encounter and needs to be able to handle.

After you adjusted the code of the library or the test you can rerun the test
with the same RNG state as follows:

```console
$ istanbul cover _mocha -- test.js --jsverifyRngState 88caf85079d32e416b
```

