## V4.0

1. 舍弃`symlink()`函数，直接在根目录生成`XMLTV`文件。

2. 添加Python脚本，使其自动Cron，适合没有权限执行cron.php的人。

3. 修复`manage.html`字符溢出。

4. 更新语法。

Summary: 总体来说，更适合虚拟主机、面板用户，可以托管至仅能使用PHP的网站。

## V3.1

1. 加入Nginx、Caddy服务器的配置文件示例，更新了一些地方。

## V3.0

1. 引入Font Awesome.

2. 还是加上了phpliteadmin. 正在优化

## V2.0

1. 修改UI，删去phpliteadmin、tinyfilemanager高危目录.

## V1.0

1. Original work: https://github.com/TakcC/PHP-EPG-Docker-Server

This repository is my own modified version, which is more suitable for use in scenarios without Docker and requiring high concurrency.

This project is licensed under the GPL-2.0 License. See the LICENSE file for more details.