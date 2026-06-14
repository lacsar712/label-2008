<?php
require_once '../common.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

if (!is_logged_in()) {
    json_response(200, '已退出登录');
}

session_unset();
session_destroy();

json_response(200, '退出登录成功');
?>
