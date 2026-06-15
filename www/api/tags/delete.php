<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('tag:delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    json_response(400, '参数错误');
}

$conn = getConnection();

$before_stmt = $conn->prepare("SELECT * FROM tags WHERE id = ?");
$before_stmt->bind_param("i", $id);
$before_stmt->execute();
$before_result = $before_stmt->get_result();
$before_data = $before_result->fetch_assoc();
$before_stmt->close();

if (!$before_data) {
    closeConnection($conn);
    json_response(404, '标签不存在');
}

$conn->begin_transaction();

try {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notice_tags WHERE tag_id = ?");
    $count_stmt->bind_param("i", $id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $reference_count = $count_row['cnt'];
    $count_stmt->close();

    $delete_nt_stmt = $conn->prepare("DELETE FROM notice_tags WHERE tag_id = ?");
    $delete_nt_stmt->bind_param("i", $id);
    $delete_nt_stmt->execute();
    $delete_nt_stmt->close();

    $delete_stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
    $delete_stmt->bind_param("i", $id);
    $delete_stmt->execute();
    $delete_stmt->close();

    $conn->commit();
    closeConnection($conn);
    write_operation_log('delete', 'tag', $id, $before_data, null);
    json_response(200, '删除成功', ['deleted_references' => $reference_count]);
} catch (Exception $e) {
    $conn->rollback();
    closeConnection($conn);
    json_response(500, '删除失败: ' . $e->getMessage());
}
?>
