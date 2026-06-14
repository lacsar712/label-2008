<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['id']) ? '编辑公告' : '添加公告'; ?> - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php
    require_once 'config.php';
    
    $edit_mode = false;
    $notice = null;
    
    // 检查是否为编辑模式
    if (isset($_GET['id'])) {
        $edit_mode = true;
        $id = intval($_GET['id']);
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notice = $result->fetch_assoc();
        $stmt->close();
        closeConnection($conn);
        
        if (!$notice) {
            header("Location: search_notice.php");
            exit();
        }
    }
    
    // 处理表单提交
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $author = sanitize($_POST['author']);
        $priority = sanitize($_POST['priority']);
        $status = sanitize($_POST['status']);
        
        $conn = getConnection();
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // 更新公告
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE notices SET title=?, content=?, author=?, priority=?, status=? WHERE id=?");
            $stmt->bind_param("sssssi", $title, $content, $author, $priority, $status, $id);
            
            if ($stmt->execute()) {
                $success_message = "公告更新成功！";
            } else {
                $error_message = "更新失败: " . $conn->error;
            }
            $stmt->close();
        } else {
            // 添加新公告
            $stmt = $conn->prepare("INSERT INTO notices (title, content, author, priority, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $title, $content, $author, $priority, $status);
            
            if ($stmt->execute()) {
                $success_message = "公告添加成功！";
            } else {
                $error_message = "添加失败: " . $conn->error;
            }
            $stmt->close();
        }
        
        closeConnection($conn);
    }
    ?>
    
    <!-- 导航栏 -->
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
                <li><a href="index.php">首页</a></li>
                <li><a href="add_notice.php" class="active">添加公告</a></li>
                <li><a href="search_notice.php">查询公告</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- 主要内容 -->
        <div class="main-content">
            <div class="form-container">
                <div class="form-header">
                    <h2><?php echo $edit_mode ? '编辑公告' : '添加公告'; ?></h2>
                    <p><?php echo $edit_mode ? '修改公告信息' : '发布新的公告信息'; ?></p>
                </div>

                <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo $success_message; ?>
                    <a href="search_notice.php" class="alert-link">查看所有公告</a>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="" class="notice-form">
                    <?php if ($edit_mode): ?>
                    <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">公告标题 <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo $edit_mode ? htmlspecialchars($notice['title']) : ''; ?>"
                               placeholder="请输入公告标题">
                    </div>

                    <div class="form-group">
                        <label for="content">公告内容 <span class="required">*</span></label>
                        <textarea id="content" name="content" rows="10" required 
                                  placeholder="请输入公告内容"><?php echo $edit_mode ? htmlspecialchars($notice['content']) : ''; ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="author">发布人 <span class="required">*</span></label>
                            <input type="text" id="author" name="author" required 
                                   value="<?php echo $edit_mode ? htmlspecialchars($notice['author']) : ''; ?>"
                                   placeholder="请输入发布人姓名">
                        </div>

                        <div class="form-group">
                            <label for="priority">优先级 <span class="required">*</span></label>
                            <select id="priority" name="priority" required>
                                <option value="low" <?php echo ($edit_mode && $notice['priority'] == 'low') ? 'selected' : ''; ?>>低</option>
                                <option value="medium" <?php echo ($edit_mode && $notice['priority'] == 'medium') ? 'selected' : ''; ?> <?php echo !$edit_mode ? 'selected' : ''; ?>>中</option>
                                <option value="high" <?php echo ($edit_mode && $notice['priority'] == 'high') ? 'selected' : ''; ?>>高</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">状态 <span class="required">*</span></label>
                            <select id="status" name="status" required>
                                <option value="published" <?php echo ($edit_mode && $notice['status'] == 'published') ? 'selected' : ''; ?> <?php echo !$edit_mode ? 'selected' : ''; ?>>已发布</option>
                                <option value="draft" <?php echo ($edit_mode && $notice['status'] == 'draft') ? 'selected' : ''; ?>>草稿</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php echo $edit_mode ? '更新公告' : '发布公告'; ?>
                        </button>
                        <a href="search_notice.php" class="btn btn-secondary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 19L3 12M3 12L10 5M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            返回列表
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
