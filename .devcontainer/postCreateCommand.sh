#!/bin/sh

export PATH="$PATH:$HOME/.composer/vendor/bin"
export PATH="$PATH:$PWD/vendor/bin"
echo 'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> ~/.bashrc
echo 'export PATH="$PATH:$PWD/vendor/bin"' >> ~/.bashrc
ln -s ./conf.sample.php cfg/conf.php
composer install --no-dev --optimize-autoloader

# for PHP unit testing
# composer require google/cloud-storage
# composer install --optimize-autoloader

sudo chmod a+x "$(pwd)" && sudo rm -rf /var/www/html && sudo ln -s "$(pwd)" /var/www/html

npm install --global nyc
