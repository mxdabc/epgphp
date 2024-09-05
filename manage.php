<?php
/**
 * @file manage.php
 * @brief 管理页面部分
 * 
 * 作者: Tak
 * GitHub: https://github.com/TakcC/PHP-EPG-Docker-Server
 * 
 * 修改: mxdabc
 * Github: https://github.com/mxdabc/epgphp
 */

// 引入公共脚本
require_once 'public.php';

session_start();

// 设置会话变量，表明用户可以访问 phpliteadmin.php
$_SESSION['can_access_phpliteadmin'] = true;

// 读取 configUpdated 状态
$configUpdated = isset($_SESSION['configUpdated']) && $_SESSION['configUpdated'];
if ($configUpdated) {
    unset($_SESSION['configUpdated']);
}

// 处理密码更新请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];

    // 验证原密码是否正确
    if ($oldPassword === $Config['manage_password']) {
        // 原密码正确，更新配置中的密码
        $Config['manage_password'] = $newPassword;

        // 将新配置写回 config.json
        file_put_contents('config.json', json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 设置密码更改成功的标志变量
        $passwordChanged = true;
    } else {
        $passwordChangeError = "原密码错误";
    }
}

// 检查是否提交登录表单
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $password = $_POST['password'];

    // 验证密码
    if ($password === $Config['manage_password']) {
        // 密码正确，设置会话变量
        $_SESSION['loggedin'] = true;
    } else {
        $error = "密码错误";
    }
}

// 处理密码更改成功后的提示
$passwordChangedMessage = isset($passwordChanged) ? "<p style='color:green;'>密码已更改</p>" : '';
$passwordChangeErrorMessage = isset($passwordChangeError) ? "<p style='color:red;'>$passwordChangeError</p>" : '';

// 检查是否已登录
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // 显示登录表单
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>登录 - EPG 管理后台</title>
        <link rel="stylesheet" type="text/css" href="css/login.css">
    </head>
    <body>
        <div class="container">
            <div class="login-title">EPG 管理后台</div>
            <h2>请登录</h2>
            <form method="POST">
                <label for="password">管理密码:</label><br><br>
                <input type="password" id="password" name="password"><br><br>
                <input type="hidden" name="login" value="1">
                <input type="submit" value="登录">
            </form>
            <div class="button-container">
                <button type="button" onclick="showChangePasswordForm()">更改密码</button>
            </div>
            <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <?php echo $passwordChangedMessage; ?>
            <?php echo $passwordChangeErrorMessage; ?>
        </div>
        <!-- 底部显示 -->
        <div class="footer">
            <a href="https://github.com/mxdabc/epgphp" style="color: #888; text-decoration: none;">Crestekk EPG PHP Version. V1.0</a>
        </div>
    
    <!-- 修改密码模态框 -->
    <div id="changePasswordModal" class="modal">
        <div class="passwd-modal-content">
            <span class="close-password">&times;</span>
            <h2>更改登录密码</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="old_password">原密码</label>
                    <input type="password" id="old_password" name="old_password">
                </div>
                <div class="form-group">
                    <label for="new_password">新密码</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                <input type="hidden" name="change_password" value="1">
                <input type="submit" value="更改密码">
            </form>
        </div>
    </div>
    <script>
        function showChangePasswordForm() {
            var changePasswordModal = document.getElementById("changePasswordModal");
            var changePasswordSpan = document.getElementsByClassName("close-password")[0];

            changePasswordModal.style.display = "block";

            changePasswordSpan.onclick = function() {
                changePasswordModal.style.display = "none";
            }

            window.onclick = function(event) {
                if (event.target == changePasswordModal) {
                    changePasswordModal.style.display = "none";
                }
            }
        }
    </script>
    </body>
    </html>
    <?php
    exit;
}

// 检查是否提交配置表单
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {

    // 获取表单数据并去除每个 URL 末尾的换行符
    $xml_urls = array_map('trim', explode("\n", trim($_POST['xml_urls'])));
    $days_to_keep = intval($_POST['days_to_keep']);
    $gen_xml = isset($_POST['gen_xml']) ? intval($_POST['gen_xml']) : $Config['gen_xml'];
    $include_future_only = isset($_POST['include_future_only']) ? intval($_POST['include_future_only']) : $Config['include_future_only'];
    $proc_chname = isset($_POST['proc_chname']) ? intval($_POST['proc_chname']) : $Config['proc_chname'];
    $ret_default = isset($_POST['ret_default']) ? intval($_POST['ret_default']) : $Config['ret_default'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // 处理间隔时间
    $interval_hour = intval($_POST['interval_hour']);
    $interval_minute = intval($_POST['interval_minute']);
    $interval_time = $interval_hour * 3600 + $interval_minute * 60;

    // 处理频道替换
    $channel_replacements = array_map('trim', explode("\n", trim($_POST['channel_replacements'])));

    // 处理频道映射
    $channel_mappings = [];
    $current_search = '';
    foreach ($_POST['channel_mappings'] as $mapping) {
        // 如果当前项是 search，则存储为当前搜索模式
        if (isset($mapping['search'])) {
            $current_search = trim(str_replace("，", ",", $mapping['search']));
        }
        // 如果当前项是 replace，则将其与当前 search 组合，并存入频道映射数组
        elseif (isset($mapping['replace'])) {
            $replace = trim($mapping['replace']);
            if ($current_search !== '' && $replace !== '') {
                $channel_mappings[$current_search] = $replace;
            }
            // 重置 current_search 以准备处理下一对 search-replace
            $current_search = '';
        }
    }

    // 获取旧的配置
    $oldConfig = $Config;

    // 更新配置
    $newConfig = [
        'xml_urls' => $xml_urls,
        'days_to_keep' => $days_to_keep,
        'gen_xml' => $gen_xml,
        'include_future_only' => $include_future_only,
        'proc_chname' => $proc_chname,
        'ret_default' => $ret_default,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'interval_time' => $interval_time,
        'manage_password' => $Config['manage_password'], // 保留密码
        'channel_replacements' => $channel_replacements,
        'channel_mappings' => $channel_mappings
    ];

    // 将新配置写回 config.json
    file_put_contents('config.json', json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // 设置标志变量以显示弹窗
    $_SESSION['configUpdated'] = true;

    // 重新加载配置以确保页面显示更新的数据
    $Config = json_decode(file_get_contents('config.json'), true);

    // 重新启动 cron.php ，设置新的定时任务
    if ($oldConfig['start_time'] !== $start_time || $oldConfig['end_time'] !== $end_time || $oldConfig['interval_time'] !== $interval_time) {
        exec('php cron.php > /dev/null 2>/dev/null &');
    }
    header('Location: manage.php');
    exit;
} else {
    // 首次进入界面，检查 cron.php 是否运行正常
    if($Config['interval_time']!=0) {
        $output = [];
        exec("ps aux | grep '[c]ron.php'", $output);
        if(!$output) {
            exec('php cron.php > /dev/null 2>/dev/null &');
        }
    }
}

// 连接数据库并获取日志表中的数据
$logData = [];
$cronLogData = [];
$channels = [];

try {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $dbResponse = null;

    if ($requestMethod == 'GET') {
        // 返回更新日志数据
        if (isset($_GET['get_update_logs'])) {
            $dbResponse = $db->query("SELECT * FROM update_log")->fetchAll(PDO::FETCH_ASSOC);
        }

        // 返回定时任务日志数据
        elseif (isset($_GET['get_cron_logs'])) {
            $dbResponse = $db->query("SELECT * FROM cron_log")->fetchAll(PDO::FETCH_ASSOC);
        }

        // 返回所有频道数据和频道数量
        elseif (isset($_GET['get_channel'])) {
            $channels = $db->query("SELECT DISTINCT channel FROM epg_data ORDER BY channel ASC")->fetchAll(PDO::FETCH_COLUMN);
            $dbResponse = [
                'channels' => $channels,
                'count' => count($channels)
            ];
        }

        // 返回待保存频道列表数据
        elseif (isset($_GET['get_gen_list'])) {
            $dbResponse = $db->query("SELECT channel FROM gen_list")->fetchAll(PDO::FETCH_COLUMN);
        }

        if ($dbResponse !== null) {
            header('Content-Type: application/json');
            echo json_encode($dbResponse);
            exit;
        }
    }

    // 将频道数据写入数据库
    elseif ($requestMethod === 'POST' && isset($_GET['set_gen_list'])) {
        $data = json_decode(file_get_contents("php://input"), true)['data'] ?? '';
        try {
            // 启动事务
            $db->beginTransaction();
            // 清空表中的数据
            $db->exec("DELETE FROM gen_list");
            // 插入新数据
            $lines = array_filter(array_map('trim', explode("\n", $data)));
            $stmt = $db->prepare("INSERT INTO gen_list (channel) VALUES (:channel)");
            foreach ($lines as $line) {
                $stmt->bindValue(':channel', $line, PDO::PARAM_STR);
                $stmt->execute(); // 执行插入操作
            }
            // 提交事务
            $db->commit();
            echo 'success';
        } catch (PDOException $e) {
            // 回滚事务
            $db->rollBack();
            echo "数据库操作失败，原因如下: " . $e->getMessage();
        }
        exit;
    }
} catch (Exception $e) {
    // 处理数据库连接错误
    $logData = [];
    $cronLogData = [];
    $channels = [];
}

// 生成配置管理表单
?>
<!DOCTYPE html>
<html>
<head>
    <title>管理配置</title>
    <link rel="stylesheet" type="text/css" href="css/manage.css">
</head>
<body>
<div class="container">
    <h2>管理配置</h2>
    <form method="POST" id="settingsForm">

        <label for="xml_urls">EPG源地址（支持 xml 跟 .xml.gz 格式， # 为注释）</label><br><br>
        <textarea class="code-font" placeholder="一行一个，地址前面加 # 可以临时停用，后面加 # 可以备注。快捷键： Ctrl+/  。" id="xml_urls" name="xml_urls" rows="5"><?php echo implode("\n", array_map('trim', $Config['xml_urls'])); ?></textarea><br><br>

        <!--
        这一块没处理好，我要疯了，要不然就是提交有问题，要不然就是读取不了
        见底部代码
        <label for="disable_elements">
            <input type="checkbox" id="disable_elements" onclick="toggleDisable()" style="margin-right: 10px;">
            我使用宝塔面板定时任务<br><br>
        </label>
        -->
        <!-- ('.input-days-to-keep, .input-time, .input-time2, .input-time3'); -->
        <label for="days_to_keep" class="label-days-to-keep">数据保存天数</label>
        <label for="start_time" class="label-time custom-margin1">【定时任务】： 开始时间</label>
        <label for="end_time" class="label-time2 custom-margin2">结束时间</label>
        <label for="interval_time" class="label-time3 custom-margin3">间隔周期 (选0小时0分钟取消)</label>

        <div class="form-row">
            <select id="days_to_keep" name="days_to_keep" class="input-days-to-keep" required>
                <?php for ($i = 1; $i <= 30; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $Config['days_to_keep'] == $i ? 'selected' : ''; ?>>
                        <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
            <input type="time" id="start_time" name="start_time" class="input-time" value="<?php echo $Config['start_time']; ?>" required>
            <input type="time" id="end_time" name="end_time" class="input-time2" value="<?php echo $Config['end_time']; ?>" required>
            
            <!-- Interval Time Controls -->
            <select id="interval_hour" name="interval_hour" class="input-time3" required>
                <?php for ($h = 0; $h < 24; $h++): ?>
                    <option value="<?php echo $h; ?>" <?php echo intval($Config['interval_time']) / 3600 == $h ? 'selected' : ''; ?>>
                        <?php echo $h; ?>
                    </option>
                <?php endfor; ?>
            </select> 小时
            <select id="interval_minute" name="interval_minute" class="input-time3" required>
                <?php for ($m = 0; $m < 60; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo (intval($Config['interval_time']) % 3600) / 60 == $m ? 'selected' : ''; ?>>
                        <?php echo $m; ?>
                    </option>
                <?php endfor; ?>
            </select> 分钟
        </div><br>
        
        <div class="flex-container">
            <div class="flex-item" style="width: 40%;">
                <label for="channel_replacements">频道忽略字符串（按顺序， \s 空格）</label><br><br>
                <textarea class="code-font" id="channel_replacements" name="channel_replacements" style="height: 138px;"><?php echo implode("\n", array_map('trim', $Config['channel_replacements'])); ?></textarea><br><br>
            </div>
            <div class="flex-item" style="width: 60%;">
                <label for="channel_mappings">频道映射（正则表达式 regex: ）</label><br><br>
                <div class="table-wrapper">
                    <table id="channelMappingsTable">
                        <thead style="position: sticky; top: 0; background-color: #ffffff;" class="code-font"><tr>                                
                            <th>自定义频道名（可用 , 分隔）</th>
                            <th><span onclick="showModal('channel')" style="color: blue; cursor: pointer;">数据库频道名</span></th>
                        </tr></thead>
                        <tbody class="code-font">
                            <?php foreach ($Config['channel_mappings'] as $search => $replace): ?>
                            <tr>
                                <td contenteditable="true" oninput="updateHiddenInput(this); addRowIfNeeded(this)">
                                    <?php echo htmlspecialchars(trim($search, '[]'), ENT_QUOTES); ?>
                                    <input type="hidden" name="channel_mappings[][search]" value="<?php echo htmlspecialchars(trim($search, '[]'), ENT_QUOTES); ?>">
                                </td>
                                <td contenteditable="true" oninput="updateHiddenInput(this); addRowIfNeeded(this)">
                                    <?php echo htmlspecialchars($replace, ENT_QUOTES); ?>
                                    <input type="hidden" name="channel_mappings[][replace]" value="<?php echo htmlspecialchars($replace, ENT_QUOTES); ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- 初始空行 -->
                            <tr>
                                <td contenteditable="true" oninput="updateHiddenInput(this); addRowIfNeeded(this)">
                                    <input type="hidden" name="channel_mappings[][search]" value="">
                                </td>
                                <td contenteditable="true" oninput="updateHiddenInput(this); addRowIfNeeded(this)">
                                    <input type="hidden" name="channel_mappings[][replace]" value="">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div><br>
            </div>
        </div>

        <div class="tooltip">
            <input id="updateConfig" type="submit" name="update" value="更新配置">
            <span class="tooltiptext">快捷键：Ctrl+S</span>
        </div><br><br>
        <div class="button-container">
            <a href="update.php" target="_blank">更新数据库</a>
            <!--<a href="phpliteadmin.php" target="_blank">管理数据库</a>-->
            <button type="button" onclick="showModal('cron')">定时任务日志</button>
            <button type="button" onclick="showModal('update')">更新日志</button>
            <button type="button" onclick="showModal('moresetting')">更多设置</button>
            <button type="button" name="logoutbtn" onclick="logout()">退出</button>
        </div>
    </form>
</div>

<!-- 底部显示 -->
<div class="footer">
    <a href="https://github.com/mxdabc/epgphp" style="color: #888; text-decoration: none;">Crestekk EPG PHP Version. V1.0</a>
</div>

<!-- 配置更新模态框 -->
<div id="myModal" class="modal">
    <div class="modal-content config-modal-content">
        <span class="close">&times;</span>
        <p id="modalMessage"></p>
    </div>
</div>

<!-- 更新日志模态框 -->
<div id="updatelogModal" class="modal">
    <div class="modal-content update-log-modal-content">
        <span class="close">&times;</span>
        <h2>数据库更新日志</h2>
        <div class="table-container" id="log-table-container">
            <table id="logTable">
                <thead style="position: sticky; top: 0; background-color: white;">
                    <tr>
                        <th>时间</th>
                        <th>描述</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- 数据由 JavaScript 动态生成 -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 定时任务日志模态框 -->
<div id="cronlogModal" class="modal">
    <div class="modal-content cron-log-modal-content">
        <span class="close">&times;</span>
        <h2>定时任务日志</h2>
        <textarea id="cronLogContent" readonly style="width: 100%; height: 440px;"></textarea>
    </div>
</div>

<!-- 频道列表模态框 -->
<div id="channelModal" class="modal">
    <div class="modal-content channel-modal-content">
        <span class="close">&times;</span>
        <h2 id="channelModalTitle">频道列表</h2>
        <input type="text" id="searchInput" placeholder="搜索频道名..." onkeyup="filterChannels()">
        <textarea id="channelList" readonly style="width: 100%; height: 390px;"></textarea>
    </div>
</div>

<!-- 更多设置模态框 -->
<div id="moreSettingModal" class="modal">
    <div class="modal-content more-setting-modal-content">
        <span class="close">&times;</span>
        <h2>更多设置</h2>
        <label for="gen_xml">生成 xmltv 文件：</label>
        <select id="gen_xml" name="gen_xml" required>
            <option value="1" <?php if ($Config['gen_xml'] == 1) echo 'selected'; ?>>t.xml.gz</option>
            <option value="2" <?php if ($Config['gen_xml'] == 2) echo 'selected'; ?>>t.xml</option>
            <option value="3" <?php if ($Config['gen_xml'] == 3) echo 'selected'; ?>>同时生成</option>
            <option value="0" <?php if ($Config['gen_xml'] == 0) echo 'selected'; ?>>不生成</option>
        </select>
        <label for="include_future_only">生成方式：</label>
        <select id="include_future_only" name="include_future_only" required>
            <option value="1" <?php if ($Config['include_future_only'] == 1) echo 'selected'; ?>>仅预告数据</option>
            <option value="0" <?php if ($Config['include_future_only'] == 0) echo 'selected'; ?>>所有数据</option>
        </select>
        <br><br>
        <label for="proc_chname">入库前处理频道名：</label>
        <select id="proc_chname" name="proc_chname" required>
            <option value="1" <?php if (!isset($Config['proc_chname']) || $Config['proc_chname'] == 1) echo 'selected'; ?>>是</option>
            <option value="0" <?php if (isset($Config['proc_chname']) && $Config['proc_chname'] == 0) echo 'selected'; ?>>否</option>
        </select>
        <label for="ret_default">默认返回“精彩节目”：</label>
        <select id="ret_default" name="ret_default" required>
            <option value="1" <?php if (!isset($Config['ret_default']) || $Config['ret_default'] == 1) echo 'selected'; ?>>是</option>
            <option value="0" <?php if (isset($Config['ret_default']) && $Config['ret_default'] == 0) echo 'selected'; ?>>否</option>
        </select>
        <br><br>
        <label for="gen_list_text">仅生成以下频道的节目单：</label>
        <span onclick="parseSource()">
            （可粘贴 txt、m3u 直播源并<span style="color: blue; cursor: pointer;">点击解析</span>）
        </span><br><br>
        <textarea id="gen_list_text" style="width: 100%; height: 260px;"></textarea><br><br>
        <button id="saveConfig" type="button" onclick="saveAndUpdateConfig();">保存配置</button>
    </div>
</div>

<script>
    //document.addEventListener('DOMContentLoaded', function() {
    //    // 获取复选框元素
    //    const checkbox = document.getElementById('disable_elements');
    //
    //    // 从 status.php 获取状态
    //    fetch('status.php')
    //        .then(response => response.text())
    //        .then(status => {
    //            checkbox.checked = status == '1'; // 更新复选框的状态
    //            toggleDisable(); // 根据复选框状态启用或禁用输入框
    //        })
    //        .catch(error => console.error('Error:', error));
    //});

    //function toggleDisable() {
        //var checkbox = document.getElementById('disable_elements');
        //var elements = document.querySelectorAll('.input-days-to-keep, .input-time, .input-time2, .input-time3');
        
        //const status = checkbox.checked ? 1 : 0;
        // const url = `status.php?status=${status}`;

        // 创建一个新的 XMLHttpRequest 对象
        // const xhr = new XMLHttpRequest();
        // xhr.open('POST', url, true);
        // xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // 发送请求
        // xhr.send();

        // 可选：处理响应
        // xhr.onload = function () {
        //    if (xhr.status >= 200 && xhr.status < 300) {
        //        console.log('Request successful:', xhr.responseText);
        //    } else {
        //        console.error('Request failed:', xhr.statusText);
        //    }
        //};

        //if (checkbox.checked) {
        //    console.log("宝塔面板定时任务已选中");
        //} else {
        //    console.log("宝塔面板定时任务未选中");
        //}

        //elements.forEach(function(element) {
        //    if (checkbox.checked) {
        //        element.disabled = true; // 禁用输入框
        //    } else {
        //        element.disabled = false; // 启用输入框
        //    }
        //});

        // 发送状态到服务器
        //fetch(`status.php?status=${checkbox.checked ? 1 : 0}`)
        //    .then(response => response.text())
        //    .then(data => console.log(data))
        //    .catch(error => console.error('Error:', error));
    //}

    // 退出登录
    function logout() {
        // 清除所有cookies
        document.cookie.split(";").forEach(function(cookie) {
            var name = cookie.split("=")[0].trim();
            document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/';
        });        
        // 清除本地存储
        sessionStorage.clear();
        // 重定向到登录页面
        window.location.href = 'manage.php';
    }
    // 频道映射自动添加空行
    function addRowIfNeeded(cell) {
        var row = cell.parentElement;
        var table = row.parentElement;
        var isLastRow = row === table.lastElementChild;
        if (isLastRow && cell.innerText.trim() !== "") {
            var newRow = table.insertRow();
            newRow.innerHTML = `
                <td contenteditable="true" oninput="updateHiddenInput(this); addRowIfNeeded(this)">
                    <input type="hidden" name="channel_mappings[][search]" value="">
                </td>
                <td contenteditable="true" oninput="updateHiddenInput(this); addRowIfNeeded(this)">
                    <input type="hidden" name="channel_mappings[][replace]" value="">
                </td>`;
            newRow.scrollIntoView({ behavior: 'smooth', block: 'end' });// 自动滚动到新添加的行
        }
    }
    function updateHiddenInput(cell) {
        var hiddenInput = cell.querySelector('input[type="hidden"]');
        hiddenInput.value = cell.textContent.trim();
    }

    let genListLoaded = false; // 用于跟踪数据是否已加载

    // Ctrl+S 保存设置
    document.addEventListener("keydown", function(event) {
        if (event.ctrlKey && event.key === "s") {
            event.preventDefault(); // 阻止默认行为，如保存页面
            saveAndUpdateConfig();
        }
    });

    // Ctrl+/ 设置（取消）注释
    document.getElementById('xml_urls').addEventListener('keydown', function(event) {
    if (event.ctrlKey && event.key === '/') {
            event.preventDefault();
            const textarea = this;
            const { selectionStart, selectionEnd, value } = textarea;
            const lines = value.split('\n');
            // 计算当前选中的行
            const startLine = value.slice(0, selectionStart).split('\n').length - 1;
            const endLine = value.slice(0, selectionEnd).split('\n').length - 1;
            // 判断选中的行是否都已注释
            const allCommented = lines.slice(startLine, endLine + 1).every(line => line.trim().startsWith('#'));
            const newLines = lines.map((line, index) => {
                if (index >= startLine && index <= endLine) {
                    return allCommented ? line.replace(/^#\s*/, '') : '# ' + line;
                }
                return line;
            });
            // 更新 textarea 的内容
            textarea.value = newLines.join('\n');
            // 检查光标开始位置是否在行首
            const startLineStartIndex = value.lastIndexOf('\n', selectionStart - 1) + 1;
            const isStartInLineStart = (selectionStart - startLineStartIndex < 2);
            // 检查光标结束位置是否在行首
            const endLineStartIndex = value.lastIndexOf('\n', selectionEnd - 1) + 1;
            const isEndInLineStart = (selectionEnd - endLineStartIndex < 2);
            // 计算光标新的开始位置
            const newSelectionStart = isStartInLineStart 
                ? startLineStartIndex
                : selectionStart + newLines[startLine].length - lines[startLine].length;
            // 计算光标新的结束位置
            const lengthDiff = newLines.join('').length - lines.join('').length;
            const endLineDiff = newLines[endLine].length - lines[endLine].length;
            const newSelectionEnd = isEndInLineStart
                ? (endLineDiff > 0 ? endLineStartIndex + lengthDiff : endLineStartIndex + lengthDiff - endLineDiff)
                : selectionEnd + lengthDiff;
            // 恢复光标位置
            textarea.setSelectionRange(newSelectionStart, newSelectionEnd);
        }
    });

    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        return `${hours}小时${minutes}分钟`;
    }

    var configUpdated = <?php echo json_encode($configUpdated); ?>;
    var intervalTime = "<?php echo $Config['interval_time']; ?>";
    var startTime = "<?php echo $Config['start_time']; ?>";
    var endTime = "<?php echo $Config['end_time']; ?>";

    if (configUpdated) {
        var modal = document.getElementById("myModal");
        var span = document.getElementsByClassName("close")[0];
        var modalMessage = document.getElementById("modalMessage");
        //var disableCheckbox = document.getElementById('disable_elements');
        
        //if (disableCheckbox.checked) {
        //    console.log("宝塔面板定时任务已选中 并保存成功");
        //}
        //else {
        //    console.log("宝塔面板定时任务未选中 并保存成功");
        //}

        //if (disableCheckbox.checked) {
        //    modalMessage.innerHTML = "配置已更新<br><br>您已选择宝塔面板<br>已取消自带的定时任务";
        //} else if (intervalTime === "0") {

        if (intervalTime === "0") {
            modalMessage.innerHTML = "配置已更新<br><br>已取消定时任务";
        } else {
            modalMessage.innerHTML = `配置已更新<br><br>已设置定时任务<br>开始时间：${startTime}<br>结束时间：${endTime}<br>间隔周期：${formatTime(intervalTime)}`;
        }

        modal.style.display = "block";
        span.onclick = function() {
            modal.style.display = "none";
        }
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        $configUpdated = false;
    }


    function showModal(type) {
        var modal, logSpan, logContent;
        switch (type) {
        case 'update':
            modal = document.getElementById("updatelogModal");
            logSpan = document.getElementsByClassName("close")[1];
            fetchLogs('<?php echo $_SERVER['PHP_SELF']; ?>?get_update_logs=true', updateLogTable);
            break;
        case 'cron':
            modal = document.getElementById("cronlogModal");
            logSpan = document.getElementsByClassName("close")[2];
            fetchLogs('<?php echo $_SERVER['PHP_SELF']; ?>?get_cron_logs=true', updateCronLogContent);
            break;
        case 'channel':
            modal = document.getElementById("channelModal");
            logSpan = document.getElementsByClassName("close")[3];
            fetchLogs('<?php echo $_SERVER['PHP_SELF']; ?>?get_channel=true', updateChannelList);
            document.getElementById('searchInput').value = ""; // 清空搜索框
            break;
        case 'moresetting':
            modal = document.getElementById("moreSettingModal");
            logSpan = document.getElementsByClassName("close")[4];            
            fetchLogs('<?php echo $_SERVER['PHP_SELF']; ?>?get_gen_list=true', updateGenList);
            genListLoaded = true; // 数据已加载
            break;
        default:
            console.error('Unknown type:', type);
            break;
        }
        modal.style.display = "block";
        logSpan.onclick = function() {
            modal.style.display = "none";
        }
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    }

    function fetchLogs(endpoint, callback) {
        fetch(endpoint)
            .then(response => response.json())
            .then(data => callback(data))
            .catch(error => {
                console.error('Error fetching log:', error);
                callback([]);
            });
    }

    function updateLogTable(logData) {
        var logTableBody = document.querySelector("#logTable tbody");
        logTableBody.innerHTML = '';

        logData.forEach(log => {
            var row = document.createElement("tr");
            row.innerHTML = `
                <td>${new Date(log.timestamp).toLocaleString()}</td>
                <td>${log.log_message}</td>
            `;
            logTableBody.appendChild(row);
        });
        var logTableContainer = document.getElementById("log-table-container");
        logTableContainer.scrollTop = logTableContainer.scrollHeight;
    }

    function updateCronLogContent(logData) {
        var logContent = document.getElementById("cronLogContent");
        logContent.value = logData.map(log => `[${new Date(log.timestamp).toLocaleDateString('zh-CN', { month: '2-digit', day: '2-digit' })} ${new Date(log.timestamp).toLocaleTimeString()}] ${log.log_message}`).join('\n');
        logContent.scrollTop = logContent.scrollHeight;
    }

    function updateChannelList(data) {
        const channelList = document.getElementById('channelList');
        const channelTitle = document.getElementById('channelModalTitle');
        channelList.dataset.allChannels = data.channels.join('\n'); // 保存所有频道数据
        channelList.value = channelList.dataset.allChannels;
        channelTitle.innerHTML = `频道列表<span style="font-size: 18px;">（总数：${data.count}）</span>`; // 更新频道总数
    }

    function updateGenList(genData) {
        const gen_list_text = document.getElementById('gen_list_text');
        gen_list_text.value = genData.join('\n');
    }

    function filterChannels() {
        var input = document.getElementById('searchInput').value.toLowerCase();
        var channelList = document.getElementById('channelList');
        var allChannels = channelList.dataset.allChannels.split('\n');
        var filteredChannels = allChannels.filter(channel => channel.toLowerCase().includes(input));
        channelList.value = filteredChannels.join('\n');
    }

    // 解析 txt、m3u 直播源，并生成频道列表
    function parseSource() {
        const textarea = document.getElementById('gen_list_text');
        const text = textarea.value;
        const channels = new Set();
        if (text.includes('#EXTM3U')) {
            if (text.includes('tvg-name')) {
                text.replace(/tvg-name="([^"]+)"/g, (_, name) => channels.add(name.trim()));
            } else {
                text.replace(/#EXTINF:[^,]*,([^,\n]+)/g, (_, name) => channels.add(name.trim()));
            }
        } else {
            text.split('\n').forEach(line => {
                if (line && !line.includes('#genre#')) {
                    channels.add(line.split(',')[0].trim());
                }
            });
        }
        textarea.value = Array.from(channels).join('\n');
    }

    // 保存数据并更新配置
    function saveAndUpdateConfig() {
        if (!genListLoaded) {
            document.getElementById('updateConfig').click();
            return;
        }
        const textAreaContent = document.getElementById('gen_list_text').value;
        fetch('<?php echo $_SERVER['PHP_SELF']; ?>?set_gen_list=true', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ data: textAreaContent })
        })
        .then(response => response.text())
        .then(responseText => {
            if (responseText.trim() === 'success') {
                document.getElementById('updateConfig').click();
            } else {
                console.error('服务器响应错误:', responseText);
            }
        })
        .catch(error => {
            console.error('请求失败:', error);
        });
    }

    // 在提交表单时，将更多设置中的数据包括在表单数据中
    document.getElementById('settingsForm').addEventListener('submit', function() {
        const fields = ['gen_xml', 'include_future_only', 'proc_chname', 'ret_default'];
        fields.forEach(function(field) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = field;
            hiddenInput.value = document.getElementById(field).value;
            this.appendChild(hiddenInput);
        }, this);
    });
    
</script>
</body>
</html>

<!--
config.json 尾部加入 
,"BTPanel_scheduled_tasks": 0
status.php 内容
    $configFile = 'config.json';

    // 处理 POST 请求以更新配置
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 获取 POST 数据
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;

        // 读取配置文件
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            // 更新 BTPanel_scheduled_tasks 的值
            $config['BTPanel_scheduled_tasks'] = $status;

            // 写回配置文件
            file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            echo 'Configuration updated successfully.';
        } else {
            echo 'Configuration file not found.';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 处理 GET 请求中包含 status 参数以更新配置
        if (isset($_GET['status'])) {
            $status = isset($_GET['status']) ? intval($_GET['status']) : 0;

            // 读取配置文件
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                // 更新 BTPanel_scheduled_tasks 的值
                $config['BTPanel_scheduled_tasks'] = $status;

                // 写回配置文件
                file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                echo 'Configuration and flag updated successfully.';
            } else {
                echo 'Configuration file not found.';
            }
        } else {
            // 处理 GET 请求以获取 BTPanel_scheduled_tasks 的值
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $BTPanel_scheduled_tasks = isset($config['BTPanel_scheduled_tasks']) ? (int)$config['BTPanel_scheduled_tasks'] : 0;
                echo $BTPanel_scheduled_tasks;
            } else {
                echo '0'; // 配置文件不存在时返回 0
            }
        }
    }
-->
