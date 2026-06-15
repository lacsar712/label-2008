<?php
require_once '../common.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(400, '请求方法错误');
}

require_login();

$user = get_logged_in_user();

if ($user) {
    json_response(200, '获取成功', $user);
} else {
    json_response(404, get_error_message(404));
}
?>
