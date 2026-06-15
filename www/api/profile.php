<?php
require_once '../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = get_logged_in_user();
    if ($user) {
        json_response(200, '获取成功', $user);
    } else {
        json_response(404, get_error_message(404));
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit('update_profile', 10, 60)) {
        json_response(429, get_error_message(429));
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json_response(400, get_error_message(400));
    }
    
    $nickname = trim($input['nickname'] ?? '');
    $bio = trim($input['bio'] ?? '');
    
    if (strlen($nickname) > 50) {
        json_response(400, '昵称长度不能超过50个字符');
    }
    
    if (strlen($bio) > 255) {
        json_response(400, '个人简介长度不能超过255个字符');
    }
    
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE users SET nickname = ?, bio = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nickname, $bio, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeConnection($conn);
        
        $user = get_logged_in_user();
        json_response(200, '更新成功', $user);
    } else {
        $stmt->close();
        closeConnection($conn);
        json_response(500, get_error_message(500));
    }
} else {
    json_response(400, '请求方法错误');
}
?>
