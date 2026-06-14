<?php
require_once '../common.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

require_login();

if (!check_rate_limit('change_password', 3, 300)) {
    json_response(429, get_error_message(429));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_response(400, get_error_message(400));
}

$old_password = $input['old_password'] ?? '';
$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
    json_response(400, get_error_message(400));
}

if (strlen($new_password) < 6) {
    json_response(1008, get_error_message(1008));
}

if ($new_password !== $confirm_password) {
    json_response(1009, get_error_message(1009));
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($old_password, $user['password'])) {
    closeConnection($conn);
    json_response(1012, get_error_message(1012));
}

if (password_verify($new_password, $user['password'])) {
    closeConnection($conn);
    json_response(1013, get_error_message(1013));
}

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);

if ($stmt->execute()) {
    $stmt->close();
    closeConnection($conn);
    
    session_unset();
    session_destroy();
    
    json_response(200, '密码修改成功，请重新登录');
} else {
    $stmt->close();
    closeConnection($conn);
    json_response(500, get_error_message(500));
}
?>
