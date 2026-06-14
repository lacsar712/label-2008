<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="auth-container">
                <div class="auth-header">
                    <svg class="auth-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h2>创建新账户</h2>
                    <p>欢迎加入公告信息管理系统</p>
                </div>

                <form id="registerForm" class="auth-form">
                    <div class="form-group">
                        <label for="username">用户名 <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required placeholder="请输入3-50个字符的用户名">
                    </div>

                    <div class="form-group">
                        <label for="email">邮箱 <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required placeholder="请输入您的邮箱地址">
                    </div>

                    <div class="form-group">
                        <label for="password">密码 <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required placeholder="请输入至少6位密码">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">确认密码 <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="请再次输入密码">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        注册账户
                    </button>
                </form>

                <div class="auth-footer">
                    <p>已有账户？ <a href="login.php">立即登录</a></p>
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
        initAuthForm('registerForm', 'register', function(result) {
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        });
    </script>
</body>
</html>
