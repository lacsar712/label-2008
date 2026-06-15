<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'common.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>找回密码 - 公告信息管理系统</title>
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
                        <path d="M15 7V5C15 3.89543 14.1046 3 13 3H5C3.89543 3 3 3.89543 3 5V13C3 14.1046 3.89543 15 5 15H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17.5 10L9 18.5L4 20L5.5 15L14 6.5C14.7825 5.71751 16.0508 5.71751 16.8333 6.5L17.5 7.16667C18.2825 7.94915 18.2825 9.21751 17.5 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h2>找回密码</h2>
                    <p>请输入您注册时使用的邮箱</p>
                </div>

                <form id="forgotForm" class="auth-form">
                    <div class="form-group">
                        <label for="email">邮箱地址 <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required placeholder="请输入注册邮箱">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        发送重置链接
                    </button>
                </form>

                <div class="auth-footer">
                    <p><a href="login.php">← 返回登录</a></p>
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
        initAuthForm('forgotForm', 'forgot_password', function(result) {
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        });
    </script>
</body>
</html>
