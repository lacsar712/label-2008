<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('category:delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    json_response(400, '参数错误');
}

$conn = getConnection();

$before_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$before_stmt->bind_param("i", $id);
$before_stmt->execute();
$before_result = $before_stmt->get_result();
$before_data = $before_result->fetch_assoc();
$before_stmt->close();

if (!$before_data) {
    closeConnection($conn);
    json_response(404, '分类不存在');
}

$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    closeConnection($conn);
    write_operation_log('delete', 'category', $id, $before_data, null);
    json_response(200, '删除成功');
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '删除失败: ' . $error);
}
?>