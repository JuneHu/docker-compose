# PHP开发环境之docker compose一把梭

已安装扩展

- mcrypt
- pdo
- pdo_mysql
- redis
- swoole

版本

- php: 7.4
- redis: 5.0.13
- swoole: 4.7.0

默认监听`1080`端口，可修改`.env`文件中的`NGINX_HTTP_HOST_PORT`参数调整

站点根目录: 宿主机的`./www`目录映射容器的`/var/www/html`目录，可以修改`.env`文件的`SOURCE_DIR`参数调整

数据库的用户名、密码详见`.env`文件

修改完参数之后运行`docker-compose up -d`即可
