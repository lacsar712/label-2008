<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_permission('role:edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, '方法不允许');
}

$data = json_decode(file_get_contents('php://input'), true);

$role_id = intval($data['id'] ?? 0);
$display_name = trim($data['display_name'] ?? '');
$description = trim($data['description'] ?? '');

if ($role_id <= 0) {
    json_response(400, '角色ID无效');
}

if (empty($display_name)) {
    json_response(400, '角色名称不能为空');
}

try {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT id, name FROM roles WHERE id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $role = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$role) {
        closeConnection($conn);
        json_response(404, '角色不存在');
    }
    
    if (in_array($role['name'], ['super_admin', 'editor', 'guest'])) {
        closeConnection($conn);
        json_response(400, '系统预置角色不可修改');
    }
    
    $stmt = $conn->prepare("UPDATE roles SET display_name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $display_name, $description, $role_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeConnection($conn);
        json_response(200, '更新成功');
    } else {
        $stmt->close();
        closeConnection($conn);
        json_response(500, '更新失败');
    }
} catch (Exception $e) {
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
