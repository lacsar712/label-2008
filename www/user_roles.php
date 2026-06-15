<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'common.php';
require_permission('user:view');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户角色分配 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght=300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .users-table-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin-bottom: var(--spacing-xl);
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table thead {
            background: var(--bg-tertiary);
        }
        .users-table th {
            padding: var(--spacing-lg);
            text-align: left;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }
        .users-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .users-table tbody tr:hover {
            background: var(--bg-tertiary);
        }
        .users-table tbody tr.editing {
            background: rgba(99, 102, 241, 0.1);
        }
        .users-table td {
            padding: var(--spacing-lg);
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .user-name-cell {
            color: var(--text-primary);
            font-weight: 500;
        }
        .user-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: var(--spacing-sm);
            vertical-align: middle;
        }
        .user-avatar-placeholder-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--bg-tertiary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--spacing-sm);
            vertical-align: middle;
        }
        .user-avatar-placeholder-small svg {
            width: 16px;
            height: 16px;
            color: var(--text-muted);
        }
        .role-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            align-items: center;
        }
        .role-checkbox-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-xs) var(--spacing-sm);
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }
        .role-checkbox-item:hover {
            background: var(--bg-primary);
        }
        .role-checkbox-item input[type="checkbox"] {
            margin: 0;
        }
        .role-checkbox-item label {
            cursor: pointer;
            font-size: 0.8rem;
            color: var(--text-primary);
            white-space: nowrap;
        }
        .save-btn {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.75rem;
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .save-btn:hover {
            filter: brightness(1.1);
        }
        .save-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .cancel-btn {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.75rem;
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .cancel-btn:hover {
            background: var(--bg-primary);
        }
        .edit-btn-inline {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.75rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .edit-btn-inline:hover {
            filter: brightness(1.1);
        }
        .user-search {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
            box-shadow: var(--shadow-lg);
        }
        .user-search-form {
            display: flex;
            gap: var(--spacing-md);
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .user-search-form .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }
        .status-badge.active {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }
        .status-badge.inactive {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning-color);
        }
        .status-badge.banned {
            background: rgba(239, 68, 68, 0.2);
            color: var(--error-color);
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
        .role-tag.system {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning-color);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2>用户角色分配</h2>
            </div>

            <div class="user-search">
                <form class="user-search-form" id="searchForm">
                    <div class="form-group">
                        <label for="searchInput">搜索用户</label>
                        <input type="text" id="searchInput" name="search" placeholder="输入用户名、昵称或邮箱搜索...">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        搜索
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetSearch()">重置</button>
                </form>
            </div>

            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="20%">用户</th>
                            <th width="20%">邮箱</th>
                            <th width="10%">状态</th>
                            <th width="25%">角色</th>
                            <th width="10%">注册时间</th>
                            <th width="10%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="7" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>
    <script src="app.js"></script>
    <script src="user_roles.js"></script>
</body>
</html>
