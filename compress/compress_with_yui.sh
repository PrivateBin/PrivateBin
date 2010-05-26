#!/bin/bash

# Compress $1 with YUI Compressor 2.4.2, returning the compressed script on stdout

DIR=`dirname $0`

$DIR/remove_constants.pl $1 > ._tmpRC.js

java -jar $DIR/yuicompressor-2.4.2.jar ._tmpRC.js \
  | $DIR/digitize.pl

rm -f ._tmpRC.js

