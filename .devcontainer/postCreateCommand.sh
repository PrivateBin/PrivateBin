#!/bin/sh

export PATH="$PATH:$HOME/.composer/vendor/bin"
ln -s ./conf.sample.php cfg/conf.php
composer install --no-dev --optimize-autoloader

sudo chmod a+x "$(pwd)" && sudo rm -rf /var/www/html && sudo ln -s "$(pwd)" /var/www/html

npm install --global nyc
