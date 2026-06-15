<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();

try {
    $user_id = $_SESSION['user_id'];
    
    $permissions = get_user_permissions($user_id);
    $roles = get_user_roles($user_id);
    
    $permission_names = array_column($permissions, 'name');
    $role_names = array_column($roles, 'name');
    
    $grouped_permissions = [];
    foreach ($permissions as $perm) {
        $category = $perm['category'];
        if (!isset($grouped_permissions[$category])) {
            $grouped_permissions[$category] = [];
        }
        $grouped_permissions[$category][] = $perm;
    }
    
    json_response(200, '获取成功', [
        'permissions' => $permissions,
        'permission_names' => $permission_names,
        'roles' => $roles,
        'role_names' => $role_names,
        'grouped_permissions' => $grouped_permissions
    ]);
} catch (Exception $e) {
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
