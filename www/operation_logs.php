<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'common.php';
require_login();
require_permission('log:view');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作日志 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content logs-page">
            <div class="logs-header">
                <h2>操作日志</h2>
                <div class="page-actions" data-permission="log:export">
                    <button class="btn btn-success" onclick="exportCSV()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 3V15M12 15L7 10M12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 15V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        导出CSV
                    </button>
                </div>
            </div>

            <div class="logs-filter-form">
                <div class="filter-form-grid">
                    <div class="form-group">
                        <label>操作人</label>
                        <input type="text" id="filterOperator" placeholder="操作人昵称或用户名">
                    </div>
                    <div class="form-group">
                        <label>操作类型</label>
                        <select id="filterOperationType">
                            <option value="">全部操作类型</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>目标类型</label>
                        <select id="filterTargetType">
                            <option value="">全部目标类型</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>关键词</label>
                        <input type="text" id="filterKeyword" placeholder="搜索目标ID或目标内容">
                    </div>
                    <div class="form-group">
                        <label>开始时间</label>
                        <input type="datetime-local" id="filterDateFrom">
                    </div>
                    <div class="form-group">
                        <label>结束时间</label>
                        <input type="datetime-local" id="filterDateTo">
                    </div>
                </div>
                <div class="filter-form-actions">
                    <button class="btn btn-secondary" onclick="resetFilters()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 12H5M19 12H21M12 3V5M12 19V21M5.63604 5.63604L7.05025 7.05025M16.9497 16.9497L18.364 18.364M5.63604 18.364L7.05025 16.9497M16.9497 7.05025L18.364 5.63604" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        重置
                    </button>
                    <button class="btn btn-primary" onclick="loadLogs(1)">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        查询
                    </button>
                </div>
            </div>

            <div class="logs-table-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>操作人</th>
                            <th>操作类型</th>
                            <th>目标类型</th>
                            <th>目标ID</th>
                            <th>IP地址</th>
                            <th>操作时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr>
                            <td colspan="8" class="empty-state">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5C15 3.89543 14.1046 3 13 3H11C9.89543 3 9 3.89543 9 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 12H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 16H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 12H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <p>加载中...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
    <div class="drawer" id="logDrawer">
        <div class="drawer-header">
            <h3>日志详情</h3>
            <button class="drawer-close" onclick="closeDrawer()">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="drawer-body" id="drawerBody">
            <div class="empty-state">
                <p>请选择一条日志查看详情</p>
            </div>
        </div>
    </div>

    <script src="operation_logs.js"></script>
</body>
</html>
