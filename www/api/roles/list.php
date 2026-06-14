<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_permission('role:view');

try {
    $conn = getConnection();
    
    $sql = "SELECT r.*, 
            (SELECT COUNT(*) FROM user_roles ur WHERE ur.role_id = r.id) as user_count,
            (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) as permission_count
            FROM roles r 
            ORDER BY r.id ASC";
    
    $result = $conn->query($sql);
    $roles = [];
    
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
    
    closeConnection($conn);
    
    json_response(200, '获取成功', $roles);
} catch (Exception $e) {
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
