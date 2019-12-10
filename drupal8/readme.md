# drupal8 compose config file

修改 `.env` 文件对应配置，执行 `docker-compose up -d` 即可

容器启动完毕之后打开浏览器访问 `http://localhost:1080` 完成 Drupal8 的安装

> 安装时的数据库地址 `localhost` 修改为 `mysql`(服务名) 或者 `mysql5`(容器名称)

[点击这里](https://hub.docker.com/_/drupal)访问 Drupal 的官方 docker 镜像
