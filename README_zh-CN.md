> [!IMPORTANT]  
> 本项目文档正在编写中，还不够完善。

![Intro](https://socialify.git.ci/mxdabc/epgphp/image?description=1&descriptionEditable=PHP%E7%89%88%E7%9A%84EPG%E6%9C%8D%E5%8A%A1%EF%BC%8C%E6%9B%B4%E8%BD%BB%E9%87%8F%E3%80%82%20&font=Jost&forks=1&issues=1&language=1&name=1&owner=1&pulls=1&stargazers=1&theme=Auto)

# 📺 轻量级 PHP 版 EPG 服务

> Language: [English](README.md)   [简体中文](README_zh-CN.md) 

欢迎使用 **轻量级 PHP 版 EPG 服务**！🎉 这是一个简单而高效的电子节目指南（EPG）服务，使用 PHP 构建。它设计得非常轻量级，易于使用，特别适合低配置服务器、没有Docker和需要高并发场景的 EPG 实现。

## 🚀 功能特色

- **轻量级**：资源占用极少，性能优化良好。
- **简单安装**：几步即可开始使用。
- **灵活**：可以轻松自定义以满足您的需求。
- **无依赖**：纯 PHP 实现，无需外部依赖。

## 🛠️ 安装步骤

1. **克隆仓库**：
   ```bash
   git clone https://github.com/mxdabc/epgphp.git
   ```
2. **进入项目目录**：
   ```bash
   cd epgphp
   ```
3. **运行服务(临时)**：
   ```bash
   php -S localhost:8000
   ```
4. **访问服务**：
   打开浏览器并访问 `http://localhost:8000/manage.php`。

## 📚 使用说明

1. **添加您的 EPG 数据**：自定义 `manage.php` 文件，添加您的电视节目表数据。
2. **查询服务**：发送 HTTP GET 请求来获取 EPG 信息。
3. **自定义**：根据具体需求修改代码。

## 📦 示例

以下是一个简单的查询示例：

```php
http://localhost:8000/index.php?channel=BBC&date=2024-08-14
```

## 👥 贡献

欢迎贡献代码！您可以提交问题、功能请求或拉取请求。

## 📝 许可证

原创作品：https://github.com/TakcC/PHP-EPG-Docker-Server

本仓库为我自己的修改版，更适合在没有Docker和需要高并发场景下使用。

本项目采用 GPL-2.0 许可证。有关详细信息，请参阅 [LICENSE](LICENSE) 文件。

