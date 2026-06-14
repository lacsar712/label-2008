<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<?php
require_once 'common.php';
require_permission('role:view');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>角色管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .roles-table-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin-bottom: var(--spacing-xl);
        }
        .roles-table {
            width: 100%;
            border-collapse: collapse;
        }
        .roles-table thead {
            background: var(--bg-tertiary);
        }
        .roles-table th {
            padding: var(--spacing-lg);
            text-align: left;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }
        .roles-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .roles-table tbody tr:hover {
            background: var(--bg-tertiary);
        }
        .roles-table td {
            padding: var(--spacing-lg);
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .role-name-cell {
            color: var(--text-primary);
            font-weight: 500;
        }
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background: var(--primary-color);
            color: white;
        }
        .role-badge.system {
            background: var(--warning-color);
        }
        .permission-matrix {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xl);
        }
        .permission-category {
            background: var(--bg-tertiary);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
        }
        .permission-category-title {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: var(--spacing-md);
            text-transform: capitalize;
        }
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--spacing-md);
        }
        .permission-item {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            transition: background 0.2s ease;
        }
        .permission-item:hover {
            background: var(--bg-primary);
        }
        .permission-item input[type="checkbox"] {
            margin-top: 2px;
            flex-shrink: 0;
        }
        .permission-item label {
            cursor: pointer;
            flex: 1;
        }
        .permission-item-name {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.875rem;
            display: block;
        }
        .permission-item-desc {
            color: var(--text-muted);
            font-size: 0.75rem;
            display: block;
            margin-top: 2px;
        }
        .select-all-row {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
        }
        .modal-content.large {
            max-width: 900px;
        }
        .role-tags {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-xs);
        }
        .role-tag {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 500;
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2>角色管理</h2>
                <div class="page-actions" id="pageActions">
                    <button class="btn btn-primary" id="addRoleBtn">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        新增角色
                    </button>
                </div>
            </div>

            <div class="roles-table-container">
                <table class="roles-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">角色标识</th>
                            <th width="15%">角色名称</th>
                            <th width="25%">描述</th>
                            <th width="10%">用户数</th>
                            <th width="10%">权限数</th>
                            <th width="10%">创建时间</th>
                            <th width="10%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="rolesTableBody">
                        <tr>
                            <td colspan="8" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal" id="roleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="roleModalTitle">新增角色</h3>
                <button class="modal-close" onclick="closeRoleModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="roleForm">
                    <input type="hidden" id="roleId">
                    <div class="form-group">
                        <label for="roleName">角色标识 <span class="required">*</span></label>
                        <input type="text" id="roleName" name="name" required placeholder="如: custom_role">
                        <div class="form-hint">只能包含小写字母和下划线，创建后不可修改</div>
                    </div>
                    <div class="form-group">
                        <label for="roleDisplayName">角色名称 <span class="required">*</span></label>
                        <input type="text" id="roleDisplayName" name="display_name" required placeholder="如: 内容管理员">
                    </div>
                    <div class="form-group">
                        <label for="roleDescription">角色描述</label>
                        <textarea id="roleDescription" name="description" rows="3" placeholder="描述该角色的职责"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">保存</button>
                        <button type="button" class="btn btn-secondary" onclick="closeRoleModal()">取消</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal" id="permissionModal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3 id="permissionModalTitle">权限配置</h3>
                <button class="modal-close" onclick="closePermissionModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="permissionRoleId">
                <div class="permission-matrix" id="permissionMatrix">
                    <div class="loading">加载中...</div>
                </div>
                <div class="form-actions" style="margin-top: var(--spacing-xl);">
                    <button type="button" class="btn btn-primary" onclick="savePermissions()">保存权限</button>
                    <button type="button" class="btn btn-secondary" onclick="closePermissionModal()">取消</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>
    <script src="app.js"></script>
    <script src="roles.js"></script>
</body>
</html>
