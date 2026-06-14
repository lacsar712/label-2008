<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_permission('role:view');

try {
    $conn = getConnection();
    
    $result = $conn->query("SELECT * FROM permissions ORDER BY category, id ASC");
    $permissions = [];
    
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    closeConnection($conn);
    
    $grouped = [];
    foreach ($permissions as $perm) {
        $category = $perm['category'];
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $perm;
    }
    
    json_response(200, '获取成功', [
        'list' => $permissions,
        'grouped' => $grouped
    ]);
} catch (Exception $e) {
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
