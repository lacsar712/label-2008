<?php
require_once 'common.php';
require_login();
header('Content-Type: text/html; charset=UTF-8');
$current_user = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="profile-container">
                <div class="profile-header">
                    <h2>个人中心</h2>
                    <p>管理您的账户信息</p>
                </div>

                <div class="profile-content">
                    <div class="profile-avatar-section">
                        <div class="avatar-wrapper">
                            <?php if ($current_user['avatar_url']): ?>
                                <img id="avatarPreview" src="<?php echo htmlspecialchars($current_user['avatar_url']); ?>" alt="头像" class="profile-avatar">
                            <?php else: ?>
                                <div id="avatarPreview" class="profile-avatar-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <div class="avatar-overlay" onclick="document.getElementById('avatarInput').click()">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M23 19V21H1V19H23ZM3 17V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V17H3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M16 9C16 11.2091 14.2091 13 12 13C9.79086 13 8 11.2091 8 9C8 6.79086 9.79086 5 12 5C14.2091 5 16 6.79086 16 9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>更换头像</span>
                            </div>
                        </div>
                        <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/gif" style="display: none;">
                        <p class="avatar-hint">支持 JPG、PNG、GIF 格式，最大 2MB</p>
                    </div>

                    <form id="profileForm" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">用户名</label>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($current_user['username']); ?>" disabled>
                                <small class="form-hint">用户名不可修改</small>
                            </div>

                            <div class="form-group">
                                <label for="email">邮箱</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($current_user['email']); ?>" disabled>
                                <small class="form-hint">邮箱不可修改</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nickname">昵称</label>
                                <input type="text" id="nickname" name="nickname" 
                                       value="<?php echo htmlspecialchars($current_user['nickname'] ?: ''); ?>"
                                       placeholder="请输入昵称（选填）">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bio">个人简介</label>
                            <textarea id="bio" name="bio" rows="4" 
                                      placeholder="介绍一下自己吧（选填，最多255字）"><?php echo htmlspecialchars($current_user['bio'] ?: ''); ?></textarea>
                        </div>

                        <div class="form-info-section">
                            <h4>账户信息</h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">注册时间</span>
                                    <span class="info-value"><?php echo date('Y-m-d H:i:s', strtotime($current_user['register_time'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">最后登录</span>
                                    <span class="info-value"><?php echo $current_user['last_login_time'] ? date('Y-m-d H:i:s', strtotime($current_user['last_login_time'])) : '首次登录'; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">账户状态</span>
                                    <span class="info-value status-<?php echo $current_user['status']; ?>">
                                        <?php 
                                            $status_map = ['active' => '正常', 'inactive' => '未激活', 'banned' => '已封禁'];
                                            echo $status_map[$current_user['status']];
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16L21 8V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17 3V8H22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 13L11 15L15 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                保存修改
                            </button>
                            <a href="change_password.php" class="btn btn-secondary">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15 7V5C15 3.89543 14.1046 3 13 3H5C3.89543 3 3 3.89543 3 5V13C3 14.1046 3.89543 15 5 15H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17.5 10L9 18.5L4 20L5.5 15L14 6.5C14.7825 5.71751 16.0508 5.71751 16.8333 6.5L17.5 7.16667C18.2825 7.94915 18.2825 9.21751 17.5 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                修改密码
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
        document.getElementById('avatarInput').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('avatar', file);

            const submitBtn = document.querySelector('#profileForm button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> 上传中...';

            try {
                const result = await apiRequest('upload_avatar', 'POST', formData, true);
                
                if (result.code === 200) {
                    showToast(result.message, 'success');
                    const avatarPreview = document.getElementById('avatarPreview');
                    if (avatarPreview.tagName === 'IMG') {
                        avatarPreview.src = result.data.avatar_url;
                    } else {
                        const img = document.createElement('img');
                        img.id = 'avatarPreview';
                        img.src = result.data.avatar_url;
                        img.alt = '头像';
                        img.className = 'profile-avatar';
                        avatarPreview.parentNode.insertBefore(img, avatarPreview);
                        avatarPreview.remove();
                    }
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    handleApiError(result, 'profileForm');
                }
            } catch (error) {
                showToast('上传失败，请稍后重试', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }

            e.target.value = '';
        });

        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearFormErrors('profileForm');

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> 保存中...';

            const data = {
                nickname: document.getElementById('nickname').value,
                bio: document.getElementById('bio').value
            };

            try {
                const result = await apiRequest('profile', 'POST', data);
                
                if (result.code === 200) {
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    handleApiError(result, 'profileForm');
                }
            } catch (error) {
                showToast('保存失败，请稍后重试', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    </script>
</body>
</html>
