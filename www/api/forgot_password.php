<?php
require_once '../common.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

if (!check_rate_limit('forgot_password', 3, 300)) {
    json_response(429, get_error_message(429));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_response(400, get_error_message(400));
}

$email = trim($input['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(1005, get_error_message(1005));
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    closeConnection($conn);
    json_response(200, '如果该邮箱已注册，我们已发送重置链接到您的邮箱');
}

$stmt->close();

$token = generate_reset_token();
$expire = date('Y-m-d H:i:s', time() + 3600);

$stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expire = ? WHERE email = ?");
$stmt->bind_param("sss", $token, $expire, $email);
$stmt->execute();
$stmt->close();

closeConnection($conn);

send_reset_email($email, $token);

json_response(200, '如果该邮箱已注册，我们已发送重置链接到您的邮箱');
?>
