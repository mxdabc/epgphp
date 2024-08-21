# 本地测试中，暂未上线，请稍等

# 📺 Lightweight PHP EPG Service

Welcome to the **Lightweight PHP EPG Service**! 🎉 This project is a simple yet efficient Electronic Program Guide (EPG) service built with PHP. It’s designed to be lightweight, easy to use, and perfect for small-scale EPG implementations.

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
3. **Run the service**:
   ```bash
   php -S localhost:8000
   ```
4. **Access the service**:
   Open your browser and go to `http://localhost:8000`.

## 📚 Usage

1. **Add your EPG data**: Customize the `epg-data.php` file with your TV schedule data.
2. **Query the service**: Send HTTP GET requests to fetch EPG information.
3. **Customize**: Modify the code as needed to fit your specific requirements.

## 📦 Example

Here’s a simple example of how to query the service:

```php
http://localhost:8000?channel=BBC&date=2024-08-14
```

## 👥 Contributing

Contributions are welcome! Feel free to submit issues, feature requests, or pull requests.

## 📝 License

This project is licensed under the BSD-3-Clause License. See the [LICENSE](LICENSE) file for more details.

---

Check out the repository on GitHub: [mxdabc/epgphp](https://github.com/mxdabc/epgphp) 📂

---

Enjoy your lightweight PHP EPG service! 😎

---
