<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$sorted_ids = isset($input['sorted_ids']) ? $input['sorted_ids'] : [];

if (empty($sorted_ids) || !is_array($sorted_ids)) {
    json_response(400, '参数错误');
}

$conn = getConnection();

$sort_order = 1;
$success_count = 0;

foreach ($sorted_ids as $id) {
    $id = intval($id);
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
        $stmt->bind_param("ii", $sort_order, $id);
        if ($stmt->execute()) {
            $success_count++;
        }
        $stmt->close();
    }
    $sort_order++;
}

closeConnection($conn);

json_response(200, '排序成功，共更新 ' . $success_count . ' 条记录');
?>