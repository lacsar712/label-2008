<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">

        <!-- 主要内容 -->
        <div class="main-content">
            <!-- 欢迎横幅 -->
            <div class="welcome-banner">
                <div class="banner-content">
                    <h2>欢迎使用公告信息管理系统</h2>
                    <p>高效管理您的公告信息，让信息传递更加便捷</p>
                </div>
                <div class="banner-stats">
                    <?php
                    require_once 'config.php';
                    $conn = getConnection();
                    
                    // 获取统计数据
                    $total_result = $conn->query("SELECT COUNT(*) as total FROM notices");
                    $total = $total_result->fetch_assoc()['total'];
                    
                    $today_result = $conn->query("SELECT COUNT(*) as today FROM notices WHERE DATE(publish_date) = CURDATE()");
                    $today = $today_result->fetch_assoc()['today'];
                    
                    $views_result = $conn->query("SELECT SUM(views) as total_views FROM notices");
                    $total_views = $views_result->fetch_assoc()['total_views'] ?? 0;
                    
                    closeConnection($conn);
                    ?>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total; ?></div>
                        <div class="stat-label">总公告数</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $today; ?></div>
                        <div class="stat-label">今日发布</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_views; ?></div>
                        <div class="stat-label">总浏览量</div>
                    </div>
                </div>
            </div>

            <!-- 快捷操作 -->
            <div class="quick-actions">
                <h3>快捷操作</h3>
                <div class="action-cards">
                    <a href="add_notice.php" class="action-card">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>添加公告</h4>
                        <p>发布新的公告信息</p>
                    </a>
                    <a href="search_notice.php" class="action-card">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>查询公告</h4>
                        <p>搜索和浏览公告</p>
                    </a>
                    <a href="search_notice.php" class="action-card">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>管理公告</h4>
                        <p>编辑和删除公告</p>
                    </a>
                </div>
            </div>

            <!-- 最新公告列表 -->
            <div class="recent-notices">
                <div class="section-header">
                    <h3>最新公告</h3>
                    <a href="search_notice.php" class="view-all">查看全部 →</a>
                </div>
                <div class="notices-grid">
                    <?php
                    $conn = getConnection();
                    $sql = "SELECT * FROM notices WHERE status = 'published' ORDER BY publish_date DESC LIMIT 6";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $priority_class = 'priority-' . $row['priority'];
                            $priority_text = [
                                'high' => '高',
                                'medium' => '中',
                                'low' => '低'
                            ][$row['priority']];
                            ?>
                            <div class="notice-card">
                                <div class="notice-header">
                                    <span class="priority-badge <?php echo $priority_class; ?>">
                                        <?php echo $priority_text; ?>
                                    </span>
                                    <span class="notice-date">
                                        <?php echo date('Y-m-d', strtotime($row['publish_date'])); ?>
                                    </span>
                                </div>
                                <h4 class="notice-title"><?php echo htmlspecialchars($row['title']); ?></h4>
                                <p class="notice-excerpt">
                                    <?php 
                                    $content = htmlspecialchars($row['content']);
                                    echo mb_substr($content, 0, 80, 'UTF-8') . (mb_strlen($content, 'UTF-8') > 80 ? '...' : ''); 
                                    ?>
                                </p>
                                <div class="notice-footer">
                                    <span class="notice-author">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <?php echo htmlspecialchars($row['author']); ?>
                                    </span>
                                    <span class="notice-views">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M2.45801 12C3.73201 7.943 7.52301 5 12.001 5C16.478 5 20.269 7.943 21.543 12C20.269 16.057 16.478 19 12.001 19C7.52301 19 3.73201 16.057 2.45801 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <?php echo $row['views']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="no-data">暂无公告信息</p>';
                    }
                    closeConnection($conn);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>
    <script src="app.js"></script>
</body>
</html>
