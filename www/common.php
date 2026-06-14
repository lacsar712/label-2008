<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function check_rate_limit($action, $max_attempts = 5, $window_seconds = 60) {
    $conn = getConnection();
    $ip = get_client_ip();
    
    $stmt = $conn->prepare("SELECT count, window_start FROM rate_limits WHERE ip = ? AND action = ?");
    $stmt->bind_param("ss", $ip, $action);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $now = new DateTime();
    
    if ($row) {
        $window_start = new DateTime($row['window_start']);
        $interval = $now->getTimestamp() - $window_start->getTimestamp();
        
        if ($interval > $window_seconds) {
            $stmt = $conn->prepare("UPDATE rate_limits SET count = 1, window_start = ? WHERE ip = ? AND action = ?");
            $window_start_str = $now->format('Y-m-d H:i:s');
            $stmt->bind_param("sss", $window_start_str, $ip, $action);
            $stmt->execute();
            $stmt->close();
            closeConnection($conn);
            return true;
        } elseif ($row['count'] >= $max_attempts) {
            closeConnection($conn);
            return false;
        } else {
            $stmt = $conn->prepare("UPDATE rate_limits SET count = count + 1 WHERE ip = ? AND action = ?");
            $stmt->bind_param("ss", $ip, $action);
            $stmt->execute();
            $stmt->close();
            closeConnection($conn);
            return true;
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO rate_limits (ip, action, count, window_start) VALUES (?, ?, 1, ?)");
        $window_start_str = $now->format('Y-m-d H:i:s');
        $stmt->bind_param("sss", $ip, $action, $window_start_str);
        $stmt->execute();
        $stmt->close();
        closeConnection($conn);
        return true;
    }
}

function json_response($code, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            json_response(401, '请先登录');
        } else {
            header('Location: login.php');
            exit();
        }
    }
}

function get_current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, username, email, avatar_url, nickname, bio, register_time, last_login_time, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    closeConnection($conn);
    
    return $user;
}

function get_error_message($code) {
    $messages = [
        200 => '操作成功',
        400 => '参数错误',
        401 => '未授权，请先登录',
        403 => '权限不足',
        404 => '资源不存在',
        429 => '请求过于频繁，请稍后再试',
        500 => '服务器内部错误',
        1001 => '用户名不能为空',
        1002 => '用户名长度应在3-50个字符之间',
        1003 => '用户名已存在',
        1004 => '邮箱不能为空',
        1005 => '邮箱格式不正确',
        1006 => '邮箱已被注册',
        1007 => '密码不能为空',
        1008 => '密码长度至少6位',
        1009 => '两次输入的密码不一致',
        1010 => '用户名或密码错误',
        1011 => '账户已被禁用',
        1012 => '原密码错误',
        1013 => '新密码不能与原密码相同',
        1014 => '重置令牌无效或已过期',
        1015 => '头像上传失败',
        1016 => '头像格式不支持，仅支持JPG、PNG、GIF格式',
        1017 => '头像文件过大，最大支持2MB',
    ];
    return isset($messages[$code]) ? $messages[$code] : '未知错误';
}

function generate_reset_token() {
    return bin2hex(random_bytes(32));
}

function send_reset_email($email, $token) {
    $reset_url = 'http://' . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . $token;
    $subject = '密码重置请求';
    $message = "您好，\n\n您收到此邮件是因为我们收到了您的密码重置请求。\n\n请点击以下链接重置密码：\n$reset_url\n\n如果您没有请求重置密码，请忽略此邮件。\n\n此链接将在1小时后过期。";
    $headers = 'From: no-reply@example.com' . "\r\n" .
               'Reply-To: no-reply@example.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    return mail($email, $subject, $message, $headers);
}
?>
