<?php
/**
 * @file cron.php
 * @brief 定时任务脚本
 * 
 * 该脚本用于在特定时间间隔内执行 update.php，以实现定时任务功能。
 * 添加对Arch Linux的优化
 * 
 * 作者: Tak
 * GitHub: https://github.com/TakcC/PHP-EPG-Docker-Server
 * 修改: mxdabc
 * Github: https://github.com/mxdabc/epgphp
 */

// 引入公共脚本
require_once 'public.php';

// 取消时间限制
set_time_limit(0);

// 设置脚本只能通过CLI运行
if (php_sapi_name() !== 'cli') {
    die("此脚本只能通过 CLI 运行");
}

// 日志记录函数
function logMessage($message) {
    global $db;
    try {
        $timestamp = date('Y-m-d H:i:s'); // 使用设定的时区时间
        $stmt = $db->prepare("INSERT INTO cron_log (timestamp, log_message) VALUES (:timestamp, :message)");
        $stmt->bindValue(':timestamp', $timestamp, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    } catch (PDOException $e) {
        die("日志记录失败: " . $e->getMessage());
    }
}

// 获取当前进程的PID
$currentPid = getmypid();

// 检查是否已有实例在运行
$output = [];
exec("ps aux | grep '[c]ron.php'", $output);
foreach ($output as $line) {
    // 提取进程信息
    $parts = preg_split('/\s+/', $line);
    $pid = $parts[1];
    // 排除当前进程的PID
    if (isset($pid) && $pid != $currentPid && posix_kill($pid, 0)) {
        if (posix_kill($pid, 9)) {
            logMessage("【终止旧进程】 {$pid}");
        } else {
            logMessage("无法终止旧的进程 {$pid}");
        }
    }
}

// 检查配置中是否存在 interval_time
if (!isset($Config['interval_time'])) {
    logMessage("不存在间隔时间。退出...");
    exit;
}

// 从配置中获取间隔时间
$interval_time = $Config['interval_time'];

// 如果间隔时间为0，则不执行
if ($interval_time == 0) {
    logMessage("间隔时间设置为0。退出...");
    exit;
}

// 检查配置中是否存在首次执行时间和结束时间
if (!isset($Config['start_time'])) {
    logMessage("不存在start_time。退出...");
    exit;
}

if (!isset($Config['end_time'])) {
    logMessage("不存在end_time。退出...");
    exit;
}

// 启动事务
$db->beginTransaction();

// 从配置中获取首次执行时间和结束时间
$start_time = $Config['start_time'];
$end_time = $Config['end_time'];

list($first_run_hour, $first_run_minute) = explode(':', $start_time);
list($end_hour, $end_minute) = explode(':', $end_time);

// 获取当前时间
$current_time = time();

// 计算今天的首次执行时间
$first_run_today = strtotime(date('Y-m-d') . " $first_run_hour:$first_run_minute:00");

// 计算明天的结束时间或今天的结束时间
if ($end_hour > $first_run_hour || ($end_hour == $first_run_hour && $end_minute > $first_run_minute)) {
    // 结束时间在今天
    $end_time_today = strtotime(date('Y-m-d') . " $end_hour:$end_minute:00");
} else {
    // 结束时间在明天
    $end_time_today = strtotime(date('Y-m-d', strtotime('+1 day')) . " $end_hour:$end_minute:00");
}

// 如果当前时间已经超过结束时间，则下次执行时间为明天的首次执行时间
if ($current_time >= $end_time_today) {
    $next_execution_time = $first_run_today + 24 * 3600; // 加一天
} else {
    // 计算从今天首次执行时间开始的下一个执行时间
    $next_execution_time = $first_run_today + ceil(($current_time - $first_run_today) / $interval_time) * $interval_time;
}

// 如果跨越了结束时间，则设置为明天的首次执行时间
if ($next_execution_time >= $end_time_today) {
    $first_run_today += 24 * 3600; // 更新明天的开始时间
    $end_time_today += 24 * 3600; // 更新明天的结束时间
    $next_execution_time = $first_run_today; // 重置为明天首次执行时间
}

// 计算距离下一个执行时间的秒数
$initial_sleep = $next_execution_time - $current_time;

// 汇总所有日志信息
logMessage("【开始时间】 " . date('H:i', $first_run_today));
logMessage("【结束时间】 " . date('H:i', $end_time_today));
$logContent = "【间隔时间】 " . gmdate('H小时i分钟', $interval_time) . "\n";
$logContent .= "\t\t  -------运行时间表-------\n";

// 循环输出每次执行的时间
$current_execution_time = $first_run_today;
while ($current_execution_time < $end_time_today) {
    $logContent .= "\t\t\t   " . date('H:i', $current_execution_time) . "\n";
    $current_execution_time += $interval_time;
}
$logContent .= "\t\t  ------------------------";
logMessage($logContent);

logMessage("【下次执行】 " . date('m/d H:i', $next_execution_time));
logMessage("【等待时间】 " . gmdate('H小时i分钟', $initial_sleep));

// 提交事务
$db->commit();

// 首先等待到下一个执行时间
sleep($initial_sleep);

// 无限循环，可以使用实际需求中的退出条件
while (true) {
    // 执行update.php
    exec('php update.php');
    logMessage("【成功执行】 update.php");

    // 计算下一个执行时间
    $current_time = time();
    $next_execution_time += $interval_time;

    // 如果跨越了结束时间，则设置为明天的首次执行时间
    if ($next_execution_time >= $end_time_today) {
        $first_run_today += 24 * 3600; // 更新明天的开始时间
        $end_time_today += 24 * 3600; // 更新明天的结束时间
        $next_execution_time = $first_run_today; // 重置为明天首次执行时间
    }

    // 计算等待时间
    $sleep_time = $next_execution_time - $current_time;

    // 记录下次执行时间
    logMessage("【下次执行】 " . date('m/d H:i', $next_execution_time));

    // 等待到下一个执行时间
    sleep($sleep_time);
}
?>
