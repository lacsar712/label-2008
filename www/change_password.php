<?php
require_once 'common.php';
require_login();
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="profile-container">
                <div class="profile-header">
                    <h2>修改密码</h2>
                    <p>为了账户安全，请定期修改密码</p>
                </div>

                <div class="profile-content">
                    <form id="changePasswordForm" class="profile-form">
                        <div class="form-group">
                            <label for="old_password">当前密码 <span class="required">*</span></label>
                            <input type="password" id="old_password" name="old_password" required placeholder="请输入当前密码">
                        </div>

                        <div class="form-group">
                            <label for="new_password">新密码 <span class="required">*</span></label>
                            <input type="password" id="new_password" name="new_password" required placeholder="请输入至少6位新密码">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">确认新密码 <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="请再次输入新密码">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                确认修改
                            </button>
                            <a href="profile.php" class="btn btn-secondary">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10 19L3 12M3 12L10 5M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                返回个人中心
                            </a>
                        </div>
                    </form>
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
    <script>
        initAuthForm('changePasswordForm', 'change_password', function(result) {
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
        });
    </script>
</body>
</html>
