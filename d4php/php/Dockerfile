# 从官方基础版本构建
# 镜像地址: https://hub.docker.com/_/php
# 该镜像中php相关的配置文件位于 /usr/local/etc 目录中
ARG PHP_VERSION=7.4
FROM php:${PHP_VERSION}-fpm-alpine
# 官方版本默认安装扩展:
# 可以通过命令 docker run php:7.4-fpm-alpine php -m 查看
#
# Core ctype curl date dom fileinfo filter ftp hash iconv json libxml mbstring mysqlnd openssl pcre
# PDO pdo_sqlite Phar posix readline Reflection session SimpleXML sodium SPL sqlite3 standard
# tokenizer xml xmlreader xmlwriter zlib

# 更换国内的镜像
#
# 1. mirrors.aliyun.com (阿里云)
# 2. mirrors.tuna.tsinghua.edu.cn (清华)
# 3. mirrors.ustc.edu.cn (科大)
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories

COPY ./install.sh /tmp/
WORKDIR /tmp
RUN chmod +x install.sh \
  && sh install.sh \
  && rm -rf /tmp/install.sh

# 复制一款可以在容器内安装php扩展的命令行工具到容器中
# 地址: https://github.com/mlocati/docker-php-extension-installer
ADD ./install-php-extensions  /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions

# 设置时区
ARG TZ=Asia/Shanghai
RUN apk add --no-cache tzdata \
  && cp "/usr/share/zoneinfo/${TZ}" /etc/localtime \
  && echo "${TZ}" > /etc/timezone

COPY ./php.ini "$PHP_INI_DIR/php.ini"
COPY ./php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html
