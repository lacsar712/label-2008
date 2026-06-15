<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('notice:edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$notice_id = isset($input['notice_id']) ? intval($input['notice_id']) : 0;
$tag_ids = isset($input['tag_ids']) ? $input['tag_ids'] : [];

if ($notice_id <= 0) {
    json_response(400, '参数错误');
}

if (!is_array($tag_ids)) {
    json_response(400, 'tag_ids必须是数组');
}

$conn = getConnection();

$conn->begin_transaction();

try {
    $notice_stmt = $conn->prepare("SELECT id FROM notices WHERE id = ?");
    $notice_stmt->bind_param("i", $notice_id);
    $notice_stmt->execute();
    $notice_result = $notice_stmt->get_result();
    if (!$notice_result->fetch_assoc()) {
        $notice_stmt->close();
        throw new Exception('公告不存在');
    }
    $notice_stmt->close();

    $old_tags_stmt = $conn->prepare("SELECT tag_id FROM notice_tags WHERE notice_id = ?");
    $old_tags_stmt->bind_param("i", $notice_id);
    $old_tags_stmt->execute();
    $old_tags_result = $old_tags_stmt->get_result();
    $old_tag_ids = [];
    while ($row = $old_tags_result->fetch_assoc()) {
        $old_tag_ids[] = $row['tag_id'];
    }
    $old_tags_stmt->close();

    $delete_nt_stmt = $conn->prepare("DELETE FROM notice_tags WHERE notice_id = ?");
    $delete_nt_stmt->bind_param("i", $notice_id);
    $delete_nt_stmt->execute();
    $delete_nt_stmt->close();

    $tag_ids = array_unique(array_map('intval', $tag_ids));
    $tag_ids = array_filter($tag_ids, function($id) {
        return $id > 0;
    });

    if (!empty($tag_ids)) {
        $insert_stmt = $conn->prepare("INSERT IGNORE INTO notice_tags (notice_id, tag_id) VALUES (?, ?)");
        foreach ($tag_ids as $tag_id) {
            $insert_stmt->bind_param("ii", $notice_id, $tag_id);
            $insert_stmt->execute();
        }
        $insert_stmt->close();
    }

    $all_tag_ids = array_unique(array_merge($old_tag_ids, $tag_ids));
    foreach ($all_tag_ids as $tag_id) {
        $update_stmt = $conn->prepare("
            UPDATE tags SET reference_count = (
                SELECT COUNT(*) FROM notice_tags WHERE tag_id = ?
            ) WHERE id = ?
        ");
        $update_stmt->bind_param("ii", $tag_id, $tag_id);
        $update_stmt->execute();
        $update_stmt->close();
    }

    // 获取旧标签详细信息
    $old_tags_stmt_detail = $conn->prepare("SELECT t.id, t.name FROM tags t WHERE t.id IN (" . implode(',', array_fill(0, count($old_tag_ids), '?')) . ")");
    $old_tag_types = str_repeat('i', count($old_tag_ids));
    $old_tags_stmt_detail->bind_param($old_tag_types, ...$old_tag_ids);
    $old_tags_stmt_detail->execute();
    $old_tags_result = $old_tags_stmt_detail->get_result();
    $old_tags_detail = [];
    while ($row = $old_tags_result->fetch_assoc()) {
        $old_tags_detail[] = $row;
    }
    $old_tags_stmt_detail->close();

    $conn->commit();

    $get_tags_stmt = $conn->prepare("
        SELECT t.* FROM tags t
        INNER JOIN notice_tags nt ON t.id = nt.tag_id
        WHERE nt.notice_id = ?
        ORDER BY t.reference_count DESC, t.id ASC
    ");
    $get_tags_stmt->bind_param("i", $notice_id);
    $get_tags_stmt->execute();
    $tags_result = $get_tags_stmt->get_result();
    $new_tags = [];
    while ($row = $tags_result->fetch_assoc()) {
        $new_tags[] = $row;
    }
    $get_tags_stmt->close();

    closeConnection($conn);

    write_operation_log('set_tags', 'notice', $notice_id, [
        'tags' => $old_tags_detail
    ], [
        'tags' => $new_tags
    ]);

    json_response(200, '设置成功', [
        'notice_id' => $notice_id,
        'old_tag_count' => count($old_tag_ids),
        'new_tag_count' => count($tag_ids),
        'tags' => $new_tags
    ]);
} catch (Exception $e) {
    $conn->rollback();
    closeConnection($conn);
    json_response(500, '设置失败: ' . $e->getMessage());
}
?>
