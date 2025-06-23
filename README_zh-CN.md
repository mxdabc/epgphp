> Language: [English](README.md)   [简体中文](README_zh-CN.md) 

> [!IMPORTANT]  
> 本项目没有任何文档参考，目前正在编写中。

[![GitHub License](https://img.shields.io/github/license/mxdabc/epgphp)
](https://www.gnu.org/licenses/gpl-2.0) ![Static Badge](https://img.shields.io/badge/redis-可选-red) ![GitHub repo size](https://img.shields.io/github/repo-size/mxdabc/epgphp) ![Static Badge](https://img.shields.io/badge/php-%3E%3D7.2-blue) ![GitHub Repo stars](https://img.shields.io/github/stars/mxdabc/epgphp) 

![Intro](https://socialify.git.ci/mxdabc/epgphp/image?description=1&descriptionEditable=PHP%E7%89%88%E7%9A%84EPG%E6%9C%8D%E5%8A%A1%EF%BC%8C%E6%9B%B4%E8%BD%BB%E9%87%8F%E3%80%82%20&font=Jost&forks=1&issues=1&language=1&name=1&owner=1&pulls=1&stargazers=1&theme=Auto)

# 轻量级 PHP 版 EPG 服务

欢迎使用 **轻量级 PHP 版 EPG 服务**！这是一个简单而高效的电子节目指南（EPG）服务，使用 PHP 构建。它设计得非常轻量级，易于使用，特别适合低配置服务器、没有Docker和需要高并发场景的 EPG 实现。

删除了原版本的文件链接，symlink函数。直接在根目录生成，完全没必要又多一层。适合没有开放`symlink()`函数的虚拟主机，有福了！

## 写在前面

遇到问题，理性反馈，不要做伸手党

## 功能特色

- **轻量级**：资源占用极少，性能优化良好。
- **简单安装**：几步即可开始使用。
- **灵活**：可以轻松自定义以满足您的需求。
- **带缓存队列支持**：无需一直请求MySQL等数据库，缓存高效。

## 使用说明

1. **添加您的 EPG 数据**：自定义 `manage.php` 文件，添加您的电视节目表数据。
2. **查询服务**：发送 HTTP GET 请求来获取 EPG 信息。
3. **自定义**：根据具体需求修改代码。

## 示例

以下是一个简单的查询示例：

```php
http://localhost:8000/index.php?channel=BBC&date=2024-08-14
http://localhost:8000/index.php?channel=BBC&date=20240814
```

## 贡献

欢迎贡献代码！您可以提交问题、功能请求或拉取请求。

## 许可证

Fork 自：https://github.com/TakcC/PHP-EPG-Docker-Server

本仓库为衍生/二次开发版，更适合面板、虚拟主机、服务器、稳定性优先的用户部署。

本项目采用 GPL-2.0 许可证。有关详细信息，请参阅 [LICENSE](LICENSE) 文件。

