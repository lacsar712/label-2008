<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    json_response(400, '公告ID不能为空');
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT n.*, c.name as category_name, c.emoji as category_emoji, c.color as category_color FROM notices n LEFT JOIN categories c ON n.category_id = c.id WHERE n.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();
$stmt->close();

if (!$notice) {
    closeConnection($conn);
    json_response(404, '公告不存在');
}

if ($notice['status'] !== 'published') {
    closeConnection($conn);
    json_response(404, '公告不存在或未发布');
}

$tags_stmt = $conn->prepare("SELECT t.* FROM tags t INNER JOIN notice_tags nt ON t.id = nt.tag_id WHERE nt.notice_id = ? ORDER BY t.reference_count DESC, t.id ASC");
$tags_stmt->bind_param("i", $id);
$tags_stmt->execute();
$tags_result = $tags_stmt->get_result();
$tags = [];
while ($tag = $tags_result->fetch_assoc()) {
    $tags[] = $tag;
}
$tags_stmt->close();

closeConnection($conn);

$notice['tags'] = $tags;

async_write_view_log($id);

json_response(200, '获取成功', $notice);
?>