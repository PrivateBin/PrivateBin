#!/bin/sh

#COMPOSER_BIN="$HOME/.composer/vendor/bin" # should be equivalent
COMPOSER_BIN="$(composer global config bin-dir --absolute --quiet)"
# LOCAL_VENDOR_BIN="$PWD/vendor/bin" # should be equivalent
LOCAL_VENDOR_BIN="$(composer config vendor-dir --absolute --quiet)"
export PATH="$PATH:$COMPOSER_BIN"
export PATH="$PATH:$LOCAL_VENDOR_BIN"
echo 'export PATH="$PATH:$(composer global config bin-dir --absolute --quiet)"' >> ~/.bashrc
echo 'export PATH="$PATH:$(composer config vendor-dir --absolute --quiet)"' >> ~/.bashrc
ln -s ./conf.sample.php cfg/conf.php
composer install --no-dev --optimize-autoloader

# for PHP unit testing
composer require --global google/cloud-storage

sudo chmod a+x "$(pwd)" && sudo rm -rf /var/www/html && sudo ln -s "$(pwd)" /var/www/html

npm install --global nyc
