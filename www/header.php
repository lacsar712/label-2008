<?php
require_once 'common.php';
$current_user = get_current_user();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="container">
        <div class="nav-brand">
            <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7 7H17M7 12H17M7 17H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1>公告信息管理系统</h1>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">首页</a></li>
            <li data-permission="notice:create"><a href="add_notice.php" class="<?php echo $current_page == 'add_notice.php' ? 'active' : ''; ?>">添加公告</a></li>
            <li><a href="search_notice.php" class="<?php echo $current_page == 'search_notice.php' ? 'active' : ''; ?>">查询公告</a></li>
            <li data-permission="category:view"><a href="categories.php" class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">分类管理</a></li>
            <li data-permission="banner:view"><a href="banners.php" class="<?php echo $current_page == 'banners.php' ? 'active' : ''; ?>">Banner管理</a></li>
            <li data-permission="role:view"><a href="roles.php" class="<?php echo $current_page == 'roles.php' ? 'active' : ''; ?>">角色管理</a></li>
            <li data-permission="tag:view"><a href="tags.php" class="<?php echo $current_page == 'tags.php' ? 'active' : ''; ?>">标签管理</a></li>
            <li data-any-permission="notice:export,notice:import"><a href="data_import_export.php" class="<?php echo $current_page == 'data_import_export.php' ? 'active' : ''; ?>">数据导入导出</a></li>
            <li data-permission="user:view"><a href="user_roles.php" class="<?php echo $current_page == 'user_roles.php' ? 'active' : ''; ?>">用户角色</a></li>
            <li data-permission="log:view"><a href="operation_logs.php" class="<?php echo $current_page == 'operation_logs.php' ? 'active' : ''; ?>">操作日志</a></li>
            <li data-permission="view_analysis:view"><a href="view_analysis.php" class="<?php echo $current_page == 'view_analysis.php' ? 'active' : ''; ?>">浏览分析</a></li>
            <li data-permission="notice_like:view"><a href="notice_likes.php" class="<?php echo $current_page == 'notice_likes.php' ? 'active' : ''; ?>">点赞洞察</a></li>
        </ul>
        <div class="nav-user">
            <?php if ($current_user): ?>
                <div class="message-notification">
                    <button class="message-bell-btn" onclick="toggleMessagePanel()">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="message-badge" id="messageBadge" style="display: none;">0</span>
                    </button>
                    <div class="message-dropdown" id="messageDropdown">
                        <div class="message-dropdown-header">
                            <h4>未读消息</h4>
                            <a href="messages.php" class="message-view-all">查看全部</a>
                        </div>
                        <div class="message-dropdown-list" id="messageDropdownList">
                            <div class="message-loading">加载中...</div>
                        </div>
                    </div>
                </div>
                <div class="user-dropdown">
                    <button class="user-avatar-btn" onclick="toggleUserMenu()">
                        <?php if ($current_user['avatar_url']): ?>
                            <img src="<?php echo htmlspecialchars($current_user['avatar_url']); ?>" alt="头像" class="user-avatar">
                        <?php else: ?>
                            <div class="user-avatar-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        <span class="user-name"><?php echo htmlspecialchars($current_user['nickname'] ?: $current_user['username']); ?></span>
                        <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="user-menu" id="userMenu">
                        <a href="messages.php" class="menu-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            消息中心
                        </a>
                        <a href="profile.php" class="menu-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            个人中心
                        </a>
                        <a href="change_password.php" class="menu-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 7V5C15 3.89543 14.1046 3 13 3H5C3.89543 3 3 3.89543 3 5V13C3 14.1046 3.89543 15 5 15H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M17.5 10L9 18.5L4 20L5.5 15L14 6.5C14.7825 5.71751 16.0508 5.71751 16.8333 6.5L17.5 7.16667C18.2825 7.94915 18.2825 9.21751 17.5 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            修改密码
                        </a>
                        <div class="menu-divider"></div>
                        <button class="menu-item logout-btn" onclick="logout()">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            退出登录
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-login">登录</a>
                    <a href="register.php" class="btn btn-register">注册</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
