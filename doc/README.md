Generating documentation
========================

In order to generate the documentation, you will need to install the following
packages and its dependencies:
* phpdoc
* graphviz

Details about [installing phpDocumentor](https://phpdoc.org/docs/latest/getting-started/installing.html)
can be found in its own documentation.

Example for Debian and Ubuntu:
```console
$ sudo aptitude install php-pear graphviz
$ sudo pear channel-discover pear.phpdoc.org
$ sudo pear install phpdoc/phpDocumentor
```

To generate the documentation, change into the main directory and run phpdoc:
```console
$ cd ZeroBin
$ phpdoc -d lib/ -t doc/
```
