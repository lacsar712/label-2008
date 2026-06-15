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

$ids = isset($input['ids']) ? $input['ids'] : [];

if (!is_array($ids) || empty($ids)) {
    json_response(400, '参数错误：ids必须是非空数组');
}

$ids = array_unique(array_map('intval', $ids));
$ids = array_filter($ids, function($id) {
    return $id > 0;
});

if (empty($ids)) {
    json_response(400, '没有有效的标签ID');
}

$conn = getConnection();

$conn->begin_transaction();

try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $check_stmt = $conn->prepare("SELECT id, name FROM tags WHERE id IN ($placeholders)");
    $check_stmt->bind_param($types, ...$ids);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $existing_tags = [];
    while ($row = $check_result->fetch_assoc()) {
        $existing_tags[] = $row;
    }
    $check_stmt->close();

    if (empty($existing_tags)) {
        throw new Exception('没有找到有效的标签');
    }

    $existing_ids = array_column($existing_tags, 'id');

    $ref_count_stmt = $conn->prepare("SELECT tag_id, COUNT(*) as cnt FROM notice_tags WHERE tag_id IN ($placeholders) GROUP BY tag_id");
    $ref_count_stmt->bind_param($types, ...$existing_ids);
    $ref_count_stmt->execute();
    $ref_count_result = $ref_count_stmt->get_result();
    $ref_counts = [];
    while ($row = $ref_count_result->fetch_assoc()) {
        $ref_counts[$row['tag_id']] = $row['cnt'];
    }
    $ref_count_stmt->close();

    $delete_nt_stmt = $conn->prepare("DELETE FROM notice_tags WHERE tag_id IN ($placeholders)");
    $delete_nt_stmt->bind_param($types, ...$existing_ids);
    $delete_nt_stmt->execute();
    $deleted_nt_count = $conn->affected_rows;
    $delete_nt_stmt->close();

    $delete_stmt = $conn->prepare("DELETE FROM tags WHERE id IN ($placeholders)");
    $delete_stmt->bind_param($types, ...$existing_ids);
    $delete_stmt->execute();
    $deleted_count = $conn->affected_rows;
    $delete_stmt->close();

    $conn->commit();
    closeConnection($conn);

    write_operation_log('batch_delete', 'tag', $existing_ids, [
        'tags' => $existing_tags,
        'reference_counts' => $ref_counts
    ], [
        'deleted_count' => $deleted_count,
        'deleted_references' => $deleted_nt_count
    ]);

    json_response(200, '批量删除成功', [
        'deleted_count' => $deleted_count,
        'deleted_references' => $deleted_nt_count,
        'deleted_tags' => $existing_tags,
        'reference_counts' => $ref_counts
    ]);
} catch (Exception $e) {
    $conn->rollback();
    closeConnection($conn);
    json_response(500, '批量删除失败: ' . $e->getMessage());
}
?>
