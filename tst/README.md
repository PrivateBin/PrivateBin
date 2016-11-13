Running unit tests
==================

In order to run these tests, you will need to install the following packages
and its dependencies:
* phpunit
* php-gd
* php-sqlite
* php-xdebug

Example for Debian and Ubuntu:
```sh
$ sudo aptitude install phpunit php-gd php-sqlite php-xdebug
```

To run the tests, just change into this directory and run phpunit:
```sh
$ cd PrivateBin/tst
$ phpunit
```
