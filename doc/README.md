Generating documentation
========================

In order to generate the documentation, you will need to install the following
packages and its dependencies:
* phpdoc
* graphviz

Details about
[installing phpDocumentor](https://phpdoc.org/docs/latest/getting-started/installing.html)
can be found in that projects documentation.

Example for Debian and Ubuntu:
```console
$ sudo aptitude install php-pear graphviz
$ sudo pear channel-discover pear.phpdoc.org
$ sudo pear install phpdoc/phpDocumentor
```

To generate the documentation, change into the main directory and run phpdoc:
```console
$ cd PrivateBin
$ phpdoc -d lib/ -t doc/
```

**Note:** When used with PHP 7, the prerelease of phpDocumentator 2.9 needs to be
manually installed by downloading it from
[GitHub](https://github.com/phpDocumentor/phpDocumentor2/releases/download/v2.9.0/phpDocumentor.phar)
and then manually moving it to e.g. `/usr/local/bin` and making it executable.
