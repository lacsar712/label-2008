<?php
// 数据库配置
define('DB_HOST', 'db');
define('DB_USER', 'notice_user');
define('DB_PASS', 'notice_pass');
define('DB_NAME', 'notice_db');

// 创建数据库连接
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// 关闭数据库连接
function closeConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// 安全处理输入
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// 格式化日期
function formatDate($date) {
    return date('Y-m-d H:i:s', strtotime($date));
}
?>
