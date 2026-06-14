<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_permission('role:view');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, '方法不允许');
}

$role_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($role_id <= 0) {
    json_response(400, '角色ID无效');
}

try {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $role = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$role) {
        closeConnection($conn);
        json_response(404, '角色不存在');
    }
    
    $stmt = $conn->prepare("SELECT p.* FROM permissions p 
                            INNER JOIN role_permissions rp ON p.id = rp.permission_id 
                            WHERE rp.role_id = ?
                            ORDER BY p.category, p.id ASC");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $permissions = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    $stmt->close();
    
    $role['permissions'] = $permissions;
    $role['permission_ids'] = array_column($permissions, 'id');
    $role['permission_names'] = array_column($permissions, 'name');
    
    closeConnection($conn);
    
    json_response(200, '获取成功', $role);
} catch (Exception $e) {
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
