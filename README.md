> [!IMPORTANT]  
> The documentation for this project is still under preparation and is not yet complete. 

# 📺 Lightweight PHP EPG Service

> Language: [English](README.md)   [简体中文](README_zh-CN.md) 

Welcome to the **Lightweight PHP EPG Service**! 🎉 This project is a simple yet efficient Electronic Program Guide (EPG) service built with PHP. It is particularly suitable for EPG implementation in low-configuration servers, without Docker, and in scenarios requiring high concurrency.

## 🚀 Features

- **Lightweight**: Minimal resource usage, optimized for performance.
- **Easy Setup**: Just a few steps to get started.
- **Flexible**: Easily customizable to suit your needs.
- **No Dependencies**: Pure PHP with no external dependencies.

## 🛠️ Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/mxdabc/epgphp.git
   ```
2. **Navigate to the project directory**:
   ```bash
   cd epgphp
   ```
3. **Run the service(Temporarily)**:
   ```bash
   php -S localhost:8000
   ```
4. **Access the service**:
   Open your browser and go to `http://localhost:8000/manage.php`.

## 📚 Usage

1. **Add your EPG data**: Customize the `manage.php` file with your TV schedule data.
2. **Query the service**: Send HTTP GET requests to fetch EPG information.
3. **Customize**: Modify the code as needed to fit your specific requirements.

## 📦 Example

Here’s a simple example of how to query the service:

```php
http://localhost:8000/index.php?channel=BBC&date=2024-08-14
```

## 👥 Contributing

Contributions are welcome! Feel free to submit issues, feature requests, or pull requests.

## 📝 License

Original work: https://github.com/TakcC/PHP-EPG-Docker-Server

This repository is my own modified version, which is more suitable for use in scenarios without Docker and requiring high concurrency.

This project is licensed under the BSD-3-Clause License. See the [LICENSE](LICENSE) file for more details.

