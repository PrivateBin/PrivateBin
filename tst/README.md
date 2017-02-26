Running PHP unit tests
======================

In order to run these tests, you will need to install the following packages
and its dependencies:
* phpunit
* php-gd
* php-sqlite3
* php-curl (optional, for codeclimate test reporter)
* php-xdebug (optional, for code coverage reports)
* composer (to install eris property based unit tests)

Example for Debian and Ubuntu:
```console
$ sudo apt install phpunit php-gd php-sqlite php-curl php-xdebug composer
$ cd PrivateBin
$ composer update
```

To run the tests, just change into this directory and run phpunit:
```console
$ cd PrivateBin/tst
$ phpunit
```

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
$ npm install jsverify jsdom jsdom-global
```

Example for Debian and Ubuntu, including steps to allow current user to install
node modules globally:
```console
$ sudo apt install npm
$ sudo mkdir /usr/local/lib/node_modules
$ sudo chown -R $(whoami) $(npm config get prefix)/{lib/node_modules,bin,share}
$ ln -s /usr/bin/nodejs /usr/local/bin/node
$ npm install -g mocha istanbul
$ cd PrivateBin/js
$ npm install jsverify jsdom jsdom-global
```

To run the tests, just change into the `js` directory and run istanbul:
```console
$ cd PrivateBin/js
$ istanbul cover _mocha
```

