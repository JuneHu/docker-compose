# 从官方基础版本构建
# 镜像地址: https://hub.docker.com/_/nginx
ARG NGINX_VERSION=1.21.1
FROM nginx:${NGINX_VERSION}-alpine

# 更换国内的镜像
#
# 1. mirrors.aliyun.com (阿里云)
# 2. mirrors.tuna.tsinghua.edu.cn (清华)
# 3. mirrors.ustc.edu.cn (科大)
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories

# 设置时区
ARG TZ=Asia/Shanghai
RUN apk add --no-cache tzdata \
  && cp "/usr/share/zoneinfo/${TZ}" /etc/localtime \
  && echo "${TZ}" > /etc/timezone

WORKDIR /var/www/html
