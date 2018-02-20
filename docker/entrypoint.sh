#! /bin/sh

chown -R www-data /var/www/html/data
rm -r /var/www/html/docker
apache2-foreground
