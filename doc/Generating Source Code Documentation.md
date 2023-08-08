# Generating Source Code Documentation

## Generating PHP documentation

In order to generate the documentation, you will need to install the following
packages and its dependencies:
* phpdoc
* graphviz

Details about
[installing phpDocumentor](https://phpdoc.org/docs/latest/getting-started/installing.html)
can be found in that projects documentation.

Example for Debian and Ubuntu:
```console
$ sudo apt install php-pear graphviz
$ sudo pear channel-discover pear.phpdoc.org
$ sudo pear install phpdoc/phpDocumentor
```

To generate the documentation, change into the main directory and run phpdoc:
```console
$ cd PrivateBin
$ phpdoc --visibility public,protected,private -t doc/phpdoc -d lib/
```

**Note:** When used with PHP 7, the prerelease of phpDocumentator 2.9 needs to be
manually installed by downloading it from
[GitHub](https://github.com/phpDocumentor/phpDocumentor2/releases/download/v2.9.0/phpDocumentor.phar)
and then manually moving it to e.g. `/usr/local/bin` and making it executable.

## Generating JS documentation

In order to generate the documentation, you will need to install the following
packages and its dependencies:
* npm

Then you can use the node package manager to install the latest stable release
of jsdoc globally:

```console
$ npm install -g jsdoc
```

Example for Debian and Ubuntu, including steps to allow current user to install
node modules globally:
```console
$ sudo apt install npm
$ sudo mkdir /usr/local/lib/node_modules
$ sudo chown -R $(whoami) $(npm config get prefix)/{lib/node_modules,bin,share}
$ npm install -g jsdoc
$ ln -s /usr/bin/nodejs /usr/local/bin/node
```

To generate the documentation, change into the main directory and run phpdoc:
```console
$ cd PrivateBin
$ jsdoc -p -d doc/jsdoc js/privatebin.js js/legacy.js
```
