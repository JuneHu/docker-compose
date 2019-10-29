# easyswoole development environment powered by docker

环境

- swoole: 4.4.8
- easyswoole: 3.3.2
- php: 7.3.10

使用

1. 进入 `www` 目录使用 `composer` 安装easyswoole

```bash
composer require easyswoole/easyswoole=3.x
php vendor/bin/easyswoole install
```

2. `docker` 启动服务

```bash
docker-compose up -d
```

3. 进入 `easyswoole` 容器

```bash
docker exec -it easyswoole /bin/sh
```
