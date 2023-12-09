#!/bin/sh

composer install --no-dev --optimize-autoloader
sudo chmod a+x "$(pwd)" && sudo rm -rf /var/www/html && sudo ln -s "$(pwd)" /var/www/html
npm install --global nyc
