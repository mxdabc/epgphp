import requests
import logging
from datetime import datetime

# 配置日志
log_file = "/path/to/log/folder/update_epg.log"
logging.basicConfig(filename=log_file, level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
## e.g. 2025-06-23 13:51:09,822 - INFO - Request successful. Status code: 200

# 请求的 URL
url = "https://localhost:8000/update.php"

def send_request():
    try:
        # 设置超时时间为 120 秒
        response = requests.get(url, timeout=120)
        response.raise_for_status()  # 检查请求是否成功
        logging.info(f"Request successful. Status code: {response.status_code}")
    except requests.exceptions.RequestException as e:
        logging.error(f"Request failed: {e}")

if __name__ == "__main__":
    send_request()