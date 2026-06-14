<?php
require_once '../common.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

if (!check_rate_limit('register', 3, 300)) {
    json_response(429, get_error_message(429));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_response(400, get_error_message(400));
}

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

if (empty($username)) {
    json_response(1001, get_error_message(1001));
}

if (strlen($username) < 3 || strlen($username) > 50) {
    json_response(1002, get_error_message(1002));
}

if (empty($email)) {
    json_response(1004, get_error_message(1004));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(1005, get_error_message(1005));
}

if (empty($password)) {
    json_response(1007, get_error_message(1007));
}

if (strlen($password) < 6) {
    json_response(1008, get_error_message(1008));
}

if ($password !== $confirm_password) {
    json_response(1009, get_error_message(1009));
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    closeConnection($conn);
    json_response(1003, get_error_message(1003));
}
$stmt->close();

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    closeConnection($conn);
    json_response(1006, get_error_message(1006));
}
$stmt->close();

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$ip = get_client_ip();
$now = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO users (username, email, password, last_login_time, last_login_ip) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $username, $email, $hashed_password, $now, $ip);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;
    $_SESSION['user_id'] = $user_id;
    
    $stmt->close();
    closeConnection($conn);
    
    json_response(200, '注册成功', [
        'user_id' => $user_id,
        'username' => $username
    ]);
} else {
    $stmt->close();
    closeConnection($conn);
    json_response(500, get_error_message(500));
}
?>
