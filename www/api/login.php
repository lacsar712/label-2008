<?php
require_once '../common.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

if (!check_rate_limit('login', 5, 60)) {
    json_response(429, get_error_message(429));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_response(400, get_error_message(400));
}

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    json_response(1010, get_error_message(1010));
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT id, password, status FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    closeConnection($conn);
    json_response(1010, get_error_message(1010));
}

if ($user['status'] !== 'active') {
    closeConnection($conn);
    json_response(1011, get_error_message(1011));
}

$ip = get_client_ip();
$now = date('Y-m-d H:i:s');

$stmt = $conn->prepare("UPDATE users SET last_login_time = ?, last_login_ip = ? WHERE id = ?");
$stmt->bind_param("ssi", $now, $ip, $user['id']);
$stmt->execute();
$stmt->close();

$_SESSION['user_id'] = $user['id'];
session_regenerate_id(true);

closeConnection($conn);

json_response(200, '登录成功', [
    'user_id' => $user['id']
]);
?>
