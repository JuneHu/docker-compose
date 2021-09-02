# 从官方基础版本构建
ARG PHP_VERSION=7.3.10
FROM php:${PHP_VERSION}-alpine
# 官方版本默认安装扩展:
# Core ctype curl
# date dom
# fileinfo filter ftp
# hash iconv
# json
# libxml
# mbstring
# mysqlnd
# openssl
# pcre
# PDO pdo_sqlite
# Phar posix
# readline
# Reflection
# session
# SimpleXML
# sodium
# SPL sqlite3 standard tokenizer xml xmlreader xmlwriter
# zlib

# 更新为国内的镜像
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories

# 时区
ARG TZ=Asia/Shanghai
# 设置时区
RUN apk add --no-cache tzdata \
    && cp "/usr/share/zoneinfo/${TZ}" /etc/localtime \
    && echo "${TZ}" > /etc/timezone

# 安装PHP扩展
# Possible values for ext-name:
# bcmath bz2 calendar ctype curl dba dom enchant exif fileinfo filter ftp gd gettext
# gmp hash iconv imap interbase intl json ldap mbstring mysqli oci8 odbc opcache pcntl pdo
# pdo_dblib pdo_firebird pdo_mysql pdo_oci pdo_odbc pdo_pgsql pdo_sqlite pgsql phar posix pspell readline
# recode reflection session shmop simplexml snmp soap sockets sodium spl standard sysvmsg sysvsem
# sysvshm tidy tokenizer wddx xml xmlreader xmlrpc xmlwriter xsl zend_test zip

# 先安装依赖
RUN apk add --no-cache $PHPIZE_DEPS libpng libpng-dev gettext gettext-dev openssl-dev libzip libzip-dev libmcrypt libmcrypt-dev

# bcmath, calendar, gettext, sockets, opcache, gd, zip
# mysqli, pcntl, pdo_mysql, shmop, sysvmsg, sysvsem, sysvshm 扩展
RUN docker-php-ext-install -j$(nproc) mysqli pdo_mysql sockets gd gettext pcntl opcache shmop bcmath zip sysvmsg sysvsem sysvshm calendar

# mcrypt扩展
RUN pecl install mcrypt-1.0.2 && docker-php-ext-enable mcrypt

# redis扩展
RUN pecl install redis-5.0.0 && docker-php-ext-enable redis

# swoole扩展
RUN pecl install swoole-4.4.5 && docker-php-ext-enable swoole

WORKDIR /var/www/html
