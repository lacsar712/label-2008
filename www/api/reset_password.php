<?php
require_once '../common.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

if (!check_rate_limit('reset_password', 3, 300)) {
    json_response(429, get_error_message(429));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_response(400, get_error_message(400));
}

$token = trim($input['token'] ?? '');
$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

if (empty($token) || empty($new_password) || empty($confirm_password)) {
    json_response(400, get_error_message(400));
}

if (strlen($new_password) < 6) {
    json_response(1008, get_error_message(1008));
}

if ($new_password !== $confirm_password) {
    json_response(1009, get_error_message(1009));
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT id, reset_token_expire FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    closeConnection($conn);
    json_response(1014, get_error_message(1014));
}

$now = new DateTime();
$expire = new DateTime($user['reset_token_expire']);

if ($now > $expire) {
    closeConnection($conn);
    json_response(1014, get_error_message(1014));
}

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE id = ?");
$stmt->bind_param("si", $hashed_password, $user['id']);

if ($stmt->execute()) {
    $stmt->close();
    
    $del_stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $del_stmt->bind_param("i", $user['id']);
    $del_stmt->execute();
    $del_stmt->close();
    
    closeConnection($conn);
    json_response(200, '密码重置成功');
} else {
    $stmt->close();
    closeConnection($conn);
    json_response(500, get_error_message(500));
}
?>
