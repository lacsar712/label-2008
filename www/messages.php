<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'common.php';
require_login();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>消息中心 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2>消息中心</h2>
                <div class="page-actions">
                    <button class="btn btn-secondary btn-sm" onclick="markAllRead()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        全部已读
                    </button>
                    <button class="btn btn-danger btn-sm" id="batchDeleteBtn" style="display:none;" onclick="batchDeleteMessages()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        批量删除
                    </button>
                </div>
            </div>

            <div class="messages-layout">
                <div class="messages-sidebar">
                    <div class="message-type-nav" id="messageTypeNav">
                        <div class="message-type-item active" data-type="" onclick="filterByType('')">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>全部消息</span>
                            <span class="message-type-count" id="totalCount">0</span>
                        </div>
                        <div class="message-type-item" data-type="system" onclick="filterByType('system')">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 15V17M12 7V13M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>系统消息</span>
                            <span class="message-type-count" id="systemCount">0</span>
                        </div>
                        <div class="message-type-item" data-type="notice" onclick="filterByType('notice')">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 7H17M7 12H17M7 17H13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>公告消息</span>
                            <span class="message-type-count" id="noticeCount">0</span>
                        </div>
                        <div class="message-type-item" data-type="security" onclick="filterByType('security')">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22C12 22 20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>安全消息</span>
                            <span class="message-type-count" id="securityCount">0</span>
                        </div>
                        <div class="message-type-item" data-type="activity" onclick="filterByType('activity')">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>活动消息</span>
                            <span class="message-type-count" id="activityCount">0</span>
                        </div>
                    </div>
                </div>

                <div class="messages-main">
                    <div class="messages-toolbar">
                        <div class="messages-filter">
                            <select id="readFilter" onchange="loadMessages()">
                                <option value="">全部状态</option>
                                <option value="0">未读</option>
                                <option value="1">已读</option>
                            </select>
                        </div>
                        <div class="messages-select-all">
                            <label class="checkbox-label">
                                <input type="checkbox" id="selectAllMessages" onchange="toggleSelectAll(this)">
                                全选
                            </label>
                        </div>
                    </div>

                    <div class="messages-list" id="messagesList">
                        <div class="message-loading">加载中...</div>
                    </div>

                    <div class="pagination" id="messagePagination"></div>
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
    <script src="messages.js"></script>
</body>
</html>
