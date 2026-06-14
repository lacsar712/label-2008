<?php
require_once '../common.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

require_login();

if (!check_rate_limit('upload_avatar', 5, 300)) {
    json_response(429, get_error_message(429));
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    json_response(1015, get_error_message(1015));
}

$file = $_FILES['avatar'];

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    json_response(1016, get_error_message(1016));
}

$max_size = 2 * 1024 * 1024;
if ($file['size'] > $max_size) {
    json_response(1017, get_error_message(1017));
}

$upload_dir = dirname(__DIR__) . '/uploads/avatars/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    json_response(1015, get_error_message(1015));
}

$avatar_url = 'uploads/avatars/' . $filename;

$conn = getConnection();

$stmt = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user && !empty($user['avatar_url'])) {
    $old_file = dirname(__DIR__) . '/' . $user['avatar_url'];
    if (file_exists($old_file)) {
        unlink($old_file);
    }
}

$stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
$stmt->bind_param("si", $avatar_url, $_SESSION['user_id']);

if ($stmt->execute()) {
    $stmt->close();
    closeConnection($conn);
    json_response(200, '头像上传成功', ['avatar_url' => $avatar_url]);
} else {
    $stmt->close();
    closeConnection($conn);
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    json_response(500, get_error_message(500));
}
?>
