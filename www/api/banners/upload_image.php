<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

require_login();
require_permission('banner:create');

if (!check_rate_limit('upload_banner', 10, 300)) {
    json_response(429, '请求过于频繁，请稍后再试');
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    json_response(400, '图片上传失败');
}

$file = $_FILES['image'];

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    json_response(400, '图片格式不支持，仅支持JPG、PNG、GIF、WebP格式');
}

$max_size = 5 * 1024 * 1024;
if ($file['size'] > $max_size) {
    json_response(400, '图片文件过大，最大支持5MB');
}

$upload_dir = dirname(__DIR__, 2) . '/uploads/banners/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'banner_' . $_SESSION['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$filepath = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    json_response(500, '图片保存失败');
}

$image_url = 'uploads/banners/' . $filename;

json_response(200, '图片上传成功', ['image_url' => $image_url]);
?>
