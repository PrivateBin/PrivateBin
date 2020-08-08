[program:nginx]
command=/usr/sbin/nginx -g "daemon off;"
priority=900
stdout_logfile= /dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
username=www-data
autorestart=true

[program:php-fpm]
command=/usr/sbin/php-fpm7.2 -F
autostart=true
autorestart=unexpected
exitcodes=0
