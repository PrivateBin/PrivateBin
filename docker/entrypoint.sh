#! /bin/sh

chown -R www-data /var/www/html/data
apache2-foreground
