<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('tag:merge');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$source_id = isset($input['source_id']) ? intval($input['source_id']) : 0;
$target_id = isset($input['target_id']) ? intval($input['target_id']) : 0;

if ($source_id <= 0 || $target_id <= 0) {
    json_response(400, '参数错误');
}

if ($source_id === $target_id) {
    json_response(400, '源标签和目标标签不能相同');
}

$conn = getConnection();

$conn->begin_transaction();

try {
    $source_stmt = $conn->prepare("SELECT * FROM tags WHERE id = ?");
    $source_stmt->bind_param("i", $source_id);
    $source_stmt->execute();
    $source_result = $source_stmt->get_result();
    $source_tag = $source_result->fetch_assoc();
    $source_stmt->close();

    if (!$source_tag) {
        throw new Exception('源标签不存在');
    }

    $target_stmt = $conn->prepare("SELECT * FROM tags WHERE id = ?");
    $target_stmt->bind_param("i", $target_id);
    $target_stmt->execute();
    $target_result = $target_stmt->get_result();
    $target_tag = $target_result->fetch_assoc();
    $target_stmt->close();

    if (!$target_tag) {
        throw new Exception('目标标签不存在');
    }

    $move_stmt = $conn->prepare("
        INSERT IGNORE INTO notice_tags (notice_id, tag_id)
        SELECT notice_id, ? FROM notice_tags WHERE tag_id = ?
    ");
    $move_stmt->bind_param("ii", $target_id, $source_id);
    $move_stmt->execute();
    $merged_count = $conn->affected_rows;
    $move_stmt->close();

    $delete_nt_stmt = $conn->prepare("DELETE FROM notice_tags WHERE tag_id = ?");
    $delete_nt_stmt->bind_param("i", $source_id);
    $delete_nt_stmt->execute();
    $delete_nt_stmt->close();

    $update_target_count_stmt = $conn->prepare("
        UPDATE tags SET reference_count = (
            SELECT COUNT(*) FROM notice_tags WHERE tag_id = ?
        ) WHERE id = ?
    ");
    $update_target_count_stmt->bind_param("ii", $target_id, $target_id);
    $update_target_count_stmt->execute();
    $update_target_count_stmt->close();

    $delete_source_stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
    $delete_source_stmt->bind_param("i", $source_id);
    $delete_source_stmt->execute();
    $delete_source_stmt->close();

    $conn->commit();

    $get_target_stmt = $conn->prepare("SELECT * FROM tags WHERE id = ?");
    $get_target_stmt->bind_param("i", $target_id);
    $get_target_stmt->execute();
    $target_result = $get_target_stmt->get_result();
    $updated_target = $target_result->fetch_assoc();
    $get_target_stmt->close();

    closeConnection($conn);

    write_operation_log('merge_tags', 'tag', [$source_id, $target_id], [
        'source_tag' => $source_tag,
        'target_tag_before' => $target_tag
    ], [
        'source_tag' => null,
        'target_tag_after' => $updated_target,
        'merged_references' => $merged_count
    ]);

    json_response(200, '合并成功', [
        'source_tag' => $source_tag,
        'target_tag' => $updated_target,
        'merged_references' => $merged_count
    ]);
} catch (Exception $e) {
    $conn->rollback();
    closeConnection($conn);
    json_response(500, '合并失败: ' . $e->getMessage());
}
?>
