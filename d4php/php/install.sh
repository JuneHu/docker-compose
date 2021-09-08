#!/bin/sh

export MC="-j$(nproc)"
# 定义需要安装的扩展
PHP_EXTENSIONS=',bcmath,pdo_mysql,mysqli,gd,redis,mcrypt,'

echo
echo "============================================"
echo "Install extensions with   : install.sh"
echo "PHP version               : ${PHP_VERSION}"
echo "Extra Extensions          : ${PHP_EXTENSIONS}"
echo "Work directory            : ${PWD}"
echo "============================================"
echo

#
# Check if current php version is greater than or equal to
# specific version.
#
# For example, to check if current php is greater than or
# equal to PHP 7.0:
#
# isPhpVersionGreaterOrEqual 7 0
#
# Param 1: Specific PHP Major version
# Param 2: Specific PHP Minor version
# Return : 1 if greater than or equal to, 0 if less than
#
isPhpVersionGreaterOrEqual()
{
    local PHP_MAJOR_VERSION=$(php -r "echo PHP_MAJOR_VERSION;")
    local PHP_MINOR_VERSION=$(php -r "echo PHP_MINOR_VERSION;")

    if [[ "$PHP_MAJOR_VERSION" -gt "$1" || "$PHP_MAJOR_VERSION" -eq "$1" && "$PHP_MINOR_VERSION" -ge "$2" ]]; then
        return 1;
    else
        return 0;
    fi
}

# docker-php-ext-install可以安装PHP的扩展
#
# bcmath bz2 calendar ctype curl dba dom enchant exif fileinfo filter ftp gd gettext
# gmp hash iconv imap interbase intl json ldap mbstring mysqli oci8 odbc opcache pcntl pdo
# pdo_dblib pdo_firebird pdo_mysql pdo_oci pdo_odbc pdo_pgsql pdo_sqlite pgsql phar posix pspell readline
# recode reflection session shmop simplexml snmp soap sockets sodium spl standard sysvmsg sysvsem
# sysvshm tidy tokenizer wddx xml xmlreader xmlrpc xmlwriter xsl zend_test zip

# 安装依赖库
apk add --no-cache $PHPIZE_DEPS

if [[ -z "${PHP_EXTENSIONS##*,bcmath,*}" ]]; then
    echo "---------- Install bcmath ----------"
	docker-php-ext-install ${MC} bcmath
fi

if [[ -z "${EXTENSIONS##*,gd,*}" ]]; then
    echo "---------- Install gd ----------"
    isPhpVersionGreaterOrEqual 7 4

    if [[ "$?" = "1" ]]; then
        # "--with-xxx-dir" was removed from php 7.4 see https://github.com/docker-library/php/issues/912
        options="--with-freetype --with-jpeg --with-webp"
    else
        options="--with-gd --with-freetype-dir=/usr/include/ --with-png-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-webp-dir=/usr/include/"
    fi

    apk add --no-cache freetype freetype-dev libpng libpng-dev libjpeg-turbo libjpeg-turbo-dev libwebp-dev
    docker-php-ext-configure gd ${options}
    docker-php-ext-install ${MC} gd
    apk del freetype-dev libpng-dev libjpeg-turbo-dev
fi

if [[ -z "${PHP_EXTENSIONS##*,pdo_mysql,*}" ]]; then
  echo "---------- Install pdo_mysql ----------"
  docker-php-ext-install ${MC} pdo_mysql
fi

if [[ -z "${PHP_EXTENSIONS##*,mysqli,*}" ]]; then
  echo "---------- Install mysqli ----------"
  docker-php-ext-install ${MC} mysqli
fi

if [[ -z "${PHP_EXTENSIONS##*,pcntl,*}" ]]; then
  echo "---------- Install pcntl ----------"
  docker-php-ext-install ${MC} pcntl
fi

if [[ -z "${PHP_EXTENSIONS##*,opcache,*}" ]]; then
    echo "---------- Install opcache ----------"
    docker-php-ext-install ${MC} opcache
fi

# swoole扩展 https://pecl.php.net/package/swoole
# 这里需要注意swoole版本与PHP的兼容性，此处安装的是4.7.1，需要php7.2或者更高版本
# 官方文档 https://wiki.swoole.com/#/environment
if [[ -z "${PHP_EXTENSIONS##*,swoole,*}" ]]; then
  echo "---------- Install swoole ----------"
  docker-php-ext-install sockets
  apk add --no-cache libstdc++
  apk add --no-cache --virtual .build-deps curl-dev openssl-dev pcre-dev pcre2-dev zlib-dev
  pecl install --configureoptions 'enable-sockets="no" enable-openssl="yes" enable-http2="yes" enable-mysqlnd="yes" enable-swoole-json="yes" enable-swoole-curl="yes" ' swoole-4.7.1
  docker-php-ext-enable swoole
  apk del .build-deps
fi

# redis扩展 https://pecl.php.net/package/redis
# 这里需要注意redis版本与PHP的兼容性，此处安装的是5.3.4，需要php7.0或者更高版本
if [[ -z "${PHP_EXTENSIONS##*,redis,*}" ]]; then
  echo "---------- Install redis ----------"
  pecl install redis-5.3.4
  docker-php-ext-enable redis
fi

# mcrypt扩展 https://pecl.php.net/package/mcrypt
# 这里需要注意mcrypt版本与PHP的兼容性，此处安装的是1.0.4，需要php7.2或者更高版本
if [[ -z "${PHP_EXTENSIONS##*,mcrypt,*}" ]]; then
  echo "---------- Install mcrypt ----------"
  apk add --no-cache libmcrypt-dev libmcrypt re2c
  pecl install mcrypt-1.0.4
  docker-php-ext-enable mcrypt
fi
