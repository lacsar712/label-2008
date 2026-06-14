<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_permission('role:delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, '方法不允许');
}

$data = json_decode(file_get_contents('php://input'), true);
$role_id = intval($data['id'] ?? 0);

if ($role_id <= 0) {
    json_response(400, '角色ID无效');
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
        json_response(400, '系统预置角色不可删除');
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_roles WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    if ($count > 0) {
        closeConnection($conn);
        json_response(400, '该角色下还有用户，无法删除');
    }
    
    $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->bind_param("i", $role_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeConnection($conn);
        json_response(200, '删除成功');
    } else {
        $stmt->close();
        closeConnection($conn);
        json_response(500, '删除失败');
    }
} catch (Exception $e) {
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
