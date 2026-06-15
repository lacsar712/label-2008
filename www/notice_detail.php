<?php
require_once 'common.php';
header('Content-Type: text/html; charset=UTF-8');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: search_notice.php');
    exit();
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT n.*, c.name as category_name, c.emoji as category_emoji, c.color as category_color FROM notices n LEFT JOIN categories c ON n.category_id = c.id WHERE n.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();
$stmt->close();

if (!$notice) {
    closeConnection($conn);
    header('Location: search_notice.php');
    exit();
}

if ($notice['status'] !== 'published') {
    closeConnection($conn);
    header('Location: search_notice.php');
    exit();
}

$tags_stmt = $conn->prepare("SELECT t.* FROM tags t INNER JOIN notice_tags nt ON t.id = nt.tag_id WHERE nt.notice_id = ? ORDER BY t.reference_count DESC, t.id ASC");
$tags_stmt->bind_param("i", $id);
$tags_stmt->execute();
$tags_result = $tags_stmt->get_result();
$notice_tags = [];
while ($tag = $tags_result->fetch_assoc()) {
    $notice_tags[] = $tag;
}
$tags_stmt->close();

closeConnection($conn);

$notice['tags'] = $notice_tags;

async_write_view_log($id);

$current_user = get_current_user();
$can_edit = has_permission('notice:edit');
$can_delete = has_permission('notice:delete');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($notice['title']); ?> - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="notice-detail-container">
                <div class="notice-detail-header">
                    <div class="notice-detail-back">
                        <a href="search_notice.php" class="btn btn-secondary btn-sm">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 19L3 12M3 12L10 5M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            返回列表
                        </a>
                    </div>
                    
                    <div class="notice-detail-actions">
                        <?php if ($can_edit): ?>
                            <a href="add_notice.php?id=<?php echo $notice['id']; ?>" class="btn btn-primary btn-sm">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                编辑
                            </a>
                        <?php endif; ?>
                        <?php if ($can_delete): ?>
                            <a href="search_notice.php?delete=<?php echo $notice['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('确定要删除这条公告吗？');">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                删除
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <article class="notice-detail-content">
                    <header class="notice-detail-title-section">
                        <h1 class="notice-detail-title"><?php echo htmlspecialchars($notice['title']); ?></h1>
                        
                        <div class="notice-detail-meta">
                            <?php if ($notice['category_name']): ?>
                                <span class="category-badge" style="background-color: <?php echo htmlspecialchars($notice['category_color']); ?>20; color: <?php echo htmlspecialchars($notice['category_color']); ?>;">
                                    <?php echo htmlspecialchars(($notice['category_emoji'] ? $notice['category_emoji'] . ' ' : '') . $notice['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <span class="priority-badge priority-<?php echo $notice['priority']; ?>">
                                <?php echo ['high' => '高优先级', 'medium' => '中优先级', 'low' => '低优先级'][$notice['priority']]; ?>
                            </span>

                            <?php if (!empty($notice_tags)): ?>
                                <div class="notice-detail-tags">
                                    <?php foreach ($notice_tags as $tag): ?>
                                        <span class="tag-badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>20; color: <?php echo htmlspecialchars($tag['color']); ?>;">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="notice-detail-info">
                            <div class="notice-info-item">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span><?php echo htmlspecialchars($notice['author']); ?></span>
                            </div>
                            <div class="notice-info-item">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 7V3M16 7V3M7 7H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 12H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span><?php echo date('Y-m-d H:i:s', strtotime($notice['publish_date'])); ?></span>
                            </div>
                            <div class="notice-info-item">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M2.45801 12C3.73201 7.943 7.52301 5 12.001 5C16.478 5 20.269 7.943 21.543 12C20.269 16.057 16.478 19 12.001 19C7.52301 19 3.73201 16.057 2.45801 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span><?php echo $notice['views']; ?> 次浏览</span>
                            </div>
                        </div>
                    </header>

                    <div class="notice-detail-body">
                        <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
                    </div>

                    <?php if ($notice['update_date'] && $notice['update_date'] != $notice['publish_date']): ?>
                        <footer class="notice-detail-footer">
                            <p class="text-muted">最后更新于：<?php echo date('Y-m-d H:i:s', strtotime($notice['update_date'])); ?></p>
                        </footer>
                    <?php endif; ?>
                </article>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script src="app.js"></script>
</body>
</html>
