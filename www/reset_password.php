<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'common.php';
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置密码 - 公告信息管理系统</title>
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
                    <h2>设置新密码</h2>
                    <p>请输入您的新密码</p>
                </div>

                <form id="resetForm" class="auth-form">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">新密码 <span class="required">*</span></label>
                        <input type="password" id="new_password" name="new_password" required placeholder="请输入至少6位新密码">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">确认新密码 <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="请再次输入新密码">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        重置密码
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
        initAuthForm('resetForm', 'reset_password', function(result) {
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
        });
    </script>
</body>
</html>
