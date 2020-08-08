FROM ubuntu:18.04

ARG DEBIAN_FRONTEND=noninteractive

RUN apt update

RUN apt install -y software-properties-common \
gcc make autoconf libc-dev pkg-config libmcrypt-dev \
supervisor nginx php7.2-fpm php7.2-gd

COPY ./template/supervisor.tpl /etc/supervisor/conf.d/supervisord.conf

COPY ./template/nginx.tpl /etc/nginx/sites-available/privatebin

RUN ln -s /etc/nginx/sites-available/privatebin /etc/nginx/sites-enabled/privatebin

RUN rm /etc/nginx/sites-enabled/default

RUN mkdir -p /opt/privatebin

RUN mkdir -p /run/php/

RUN chown www-data:www-data /opt/privatebin

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
