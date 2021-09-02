# 从官方基础版本构建
ARG NGINX_VERSION=1.17.2
FROM nginx:${NGINX_VERSION}-alpine

# 更新为国内的镜像
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories

# 时区
ARG TZ=Asia/Shanghai
# 设置时区1
RUN apk add --no-cache tzdata \
    && cp "/usr/share/zoneinfo/${TZ}" /etc/localtime \
    && echo "${TZ}" > /etc/timezone

WORKDIR /var/www/html
