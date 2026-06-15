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
    require_once 'common.php';
    
    $edit_mode = false;
    $notice = null;
    
    // 检查是否为编辑模式
    if (isset($_GET['id'])) {
        require_permission('notice:edit');
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
    } else {
        require_permission('notice:create');
    }
    
    // 获取公告已有标签（编辑模式）
    $notice_tags = [];
    if ($edit_mode && $notice) {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT t.* FROM tags t INNER JOIN notice_tags nt ON t.id = nt.tag_id WHERE nt.notice_id = ? ORDER BY t.reference_count DESC, t.id ASC");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notice_tags[] = $row;
        }
        $stmt->close();
        closeConnection($conn);
    }

    // 处理表单提交
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            require_permission('notice:edit');
        } else {
            require_permission('notice:create');
        }
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $author = sanitize($_POST['author']);
        $priority = sanitize($_POST['priority']);
        $status = sanitize($_POST['status']);
        $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $author_id = isset($_POST['author_id']) ? intval($_POST['author_id']) : null;
        $tag_ids = isset($_POST['tag_ids']) ? json_decode($_POST['tag_ids'], true) : [];
        $tag_names = isset($_POST['tag_names']) ? json_decode($_POST['tag_names'], true) : [];
        
        if (!is_array($tag_ids)) {
            $tag_ids = [];
        }
        if (!is_array($tag_names)) {
            $tag_names = [];
        }
        
        if (empty($author) && is_logged_in()) {
            $current_user = get_current_user();
            if ($current_user) {
                $author = $current_user['nickname'] ?: $current_user['username'];
                $author_id = $current_user['id'];
            }
        }
        
        $conn = getConnection();
        $conn->begin_transaction();
        
        try {
            $notice_id = null;
            $is_update = isset($_POST['id']) && !empty($_POST['id']);
            $before_data = null;
            $after_data = null;
            
            if ($is_update) {
                // 更新公告 - 先获取变更前数据
                $notice_id = intval($_POST['id']);
                $before_stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
                $before_stmt->bind_param("i", $notice_id);
                $before_stmt->execute();
                $before_result = $before_stmt->get_result();
                $before_data = $before_result->fetch_assoc();
                $before_stmt->close();
                
                $stmt = $conn->prepare("UPDATE notices SET title=?, content=?, author=?, author_id=?, priority=?, status=?, category_id=? WHERE id=?");
                $stmt->bind_param("sssissii", $title, $content, $author, $author_id, $priority, $status, $category_id, $notice_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("更新失败: " . $conn->error);
                }
                $stmt->close();
            } else {
                // 添加新公告
                if ($author_id) {
                    $stmt = $conn->prepare("INSERT INTO notices (title, content, author, author_id, priority, status, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssisssi", $title, $content, $author, $author_id, $priority, $status, $category_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO notices (title, content, author, priority, status, category_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssi", $title, $content, $author, $priority, $status, $category_id);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("添加失败: " . $conn->error);
                }
                $notice_id = $conn->insert_id;
                $stmt->close();
            }
            
            // 处理标签：先创建新标签（通过名称），再设置关联
            $final_tag_ids = $tag_ids;
            
            // 创建新标签（如果有通过名称添加的标签）
            if (!empty($tag_names)) {
                foreach ($tag_names as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (empty($tag_name)) continue;
                    
                    // 检查标签是否已存在
                    $check_stmt = $conn->prepare("SELECT id FROM tags WHERE name = ?");
                    $check_stmt->bind_param("s", $tag_name);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $existing_tag = $check_result->fetch_assoc();
                        $final_tag_ids[] = intval($existing_tag['id']);
                    } else {
                        // 创建新标签
                        $color = '#' . substr(md5($tag_name), 0, 6);
                        $insert_stmt = $conn->prepare("INSERT INTO tags (name, color) VALUES (?, ?)");
                        $insert_stmt->bind_param("ss", $tag_name, $color);
                        if ($insert_stmt->execute()) {
                            $final_tag_ids[] = intval($conn->insert_id);
                        }
                        $insert_stmt->close();
                    }
                    $check_stmt->close();
                }
            }
            
            // 设置公告标签关联
            $final_tag_ids = array_unique(array_map('intval', $final_tag_ids));
            $final_tag_ids = array_filter($final_tag_ids, function($id) {
                return $id > 0;
            });
            
            // 获取旧标签信息用于更新引用计数和日志（必须在删除前查询）
            $old_tags_stmt = $conn->prepare("SELECT t.id, t.name FROM tags t INNER JOIN notice_tags nt ON t.id = nt.tag_id WHERE nt.notice_id = ?");
            $old_tags_stmt->bind_param("i", $notice_id);
            $old_tags_stmt->execute();
            $old_tags_result = $old_tags_stmt->get_result();
            $old_tag_ids = [];
            $old_tag_info = [];
            while ($row = $old_tags_result->fetch_assoc()) {
                $old_tag_ids[] = $row['id'];
                $old_tag_info[] = $row;
            }
            $old_tags_stmt->close();
            
            // 删除旧关联
            $delete_nt_stmt = $conn->prepare("DELETE FROM notice_tags WHERE notice_id = ?");
            $delete_nt_stmt->bind_param("i", $notice_id);
            $delete_nt_stmt->execute();
            $delete_nt_stmt->close();
            
            // 插入新关联
            if (!empty($final_tag_ids)) {
                $insert_nt_stmt = $conn->prepare("INSERT IGNORE INTO notice_tags (notice_id, tag_id) VALUES (?, ?)");
                foreach ($final_tag_ids as $tag_id) {
                    $insert_nt_stmt->bind_param("ii", $notice_id, $tag_id);
                    $insert_nt_stmt->execute();
                }
                $insert_nt_stmt->close();
            }
            
            // 更新所有相关标签的引用计数
            $all_related_tag_ids = array_unique(array_merge($old_tag_ids, $final_tag_ids));
            foreach ($all_related_tag_ids as $tag_id) {
                $update_count_stmt = $conn->prepare("UPDATE tags SET reference_count = (SELECT COUNT(*) FROM notice_tags WHERE tag_id = ?) WHERE id = ?");
                $update_count_stmt->bind_param("ii", $tag_id, $tag_id);
                $update_count_stmt->execute();
                $update_count_stmt->close();
            }
            
            // 获取变更后的数据
            $after_stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
            $after_stmt->bind_param("i", $notice_id);
            $after_stmt->execute();
            $after_result = $after_stmt->get_result();
            $after_data = $after_result->fetch_assoc();
            $after_stmt->close();
            
            // 获取标签信息
            $tags_stmt = $conn->prepare("SELECT t.id, t.name FROM tags t INNER JOIN notice_tags nt ON t.id = nt.tag_id WHERE nt.notice_id = ?");
            $tags_stmt->bind_param("i", $notice_id);
            $tags_stmt->execute();
            $tags_result = $tags_stmt->get_result();
            $notice_tags = [];
            while ($tag = $tags_result->fetch_assoc()) {
                $notice_tags[] = $tag;
            }
            $tags_stmt->close();
            
            $after_data['tags'] = $notice_tags;
            if ($before_data) {
                $before_data['tags'] = $old_tag_info;
            }
            
            $conn->commit();
            $success_message = $is_update ? "公告更新成功！" : "公告添加成功！";
            
            if ($is_update) {
                send_message_to_all('notice', '公告已更新', '公告「' . $title . '」已被更新', 'notice', $notice_id);
                write_operation_log('update', 'notice', $notice_id, $before_data, $after_data);
            } else {
                send_message_to_all('notice', '新公告发布', '新公告「' . $title . '」已发布', 'notice', $notice_id);
                write_operation_log('create', 'notice', $notice_id, null, $after_data);
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
        
        closeConnection($conn);
    }
    
    // 获取分类列表
    $conn = getConnection();
    $category_result = $conn->query("SELECT * FROM categories WHERE status = 'enabled' ORDER BY sort_order ASC, id ASC");
    $categories = [];
    while ($cat = $category_result->fetch_assoc()) {
        $categories[] = $cat;
    }
    closeConnection($conn);
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

                <form method="POST" action="" class="notice-form" id="noticeForm">
                    <?php if ($edit_mode): ?>
                    <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                    <?php endif; ?>
                    <input type="hidden" name="tag_ids" id="tagIds" value='<?php echo htmlspecialchars(json_encode(array_column($notice_tags, 'id'))); ?>'>
                    <input type="hidden" name="tag_names" id="tagNames" value='[]'>
                    
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
                            <label for="category_id">分类</label>
                            <select id="category_id" name="category_id">
                                <option value="">请选择分类</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($edit_mode && $notice['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($cat['emoji'] ? $cat['emoji'] . ' ' : '') . $cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

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

                    <div class="form-group">
                        <label>标签</label>
                        <div class="tag-input-wrapper">
                            <div class="selected-tags" id="selectedTags">
                                <?php foreach ($notice_tags as $tag): ?>
                                    <span class="tag-item" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>20; color: <?php echo htmlspecialchars($tag['color']); ?>;" data-id="<?php echo $tag['id']; ?>" data-name="<?php echo htmlspecialchars($tag['name']); ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                        <button type="button" class="tag-remove" onclick="removeTag(<?php echo $tag['id']; ?>, null)">×</button>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <div class="tag-input-container">
                                <input type="text" id="tagSearchInput" placeholder="输入标签名称，回车添加，或搜索已有标签..." autocomplete="off">
                                <div class="tag-suggestions" id="tagSuggestions"></div>
                            </div>
                        </div>
                        <small class="form-text text-muted">输入标签名称后按回车添加，或从搜索结果中选择已有标签。点击标签上的 × 可移除标签。</small>
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
    <script src="app.js"></script>
    <script>
    let selectedTagIds = <?php echo json_encode(array_column($notice_tags, 'id')); ?>;
    let selectedTagNames = [];
    let searchTimeout = null;

    document.addEventListener('DOMContentLoaded', function() {
        const tagInput = document.getElementById('tagSearchInput');
        const suggestions = document.getElementById('tagSuggestions');

        tagInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length > 0) {
                searchTimeout = setTimeout(() => searchTags(query), 300);
            } else {
                suggestions.innerHTML = '';
                suggestions.style.display = 'none';
            }
        });

        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const value = this.value.trim();
                if (value) {
                    addNewTagName(value);
                    this.value = '';
                    suggestions.innerHTML = '';
                    suggestions.style.display = 'none';
                }
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.tag-input-container')) {
                suggestions.innerHTML = '';
                suggestions.style.display = 'none';
            }
        });

        updateHiddenFields();
    });

    async function searchTags(query) {
        const suggestions = document.getElementById('tagSuggestions');
        
        const result = await apiRequest(`tags/search?q=${encodeURIComponent(query)}`, 'GET');
        if (result.code === 200 && result.data && result.data.length > 0) {
            const filteredTags = result.data.filter(tag => !selectedTagIds.includes(tag.id));
            
            if (filteredTags.length > 0) {
                suggestions.innerHTML = filteredTags.map(tag => `
                    <div class="tag-suggestion-item" onclick="selectSuggestedTag(${tag.id}, '${escapeHtml(tag.name)}', '${tag.color}')">
                        <span class="tag-badge" style="background-color: ${tag.color}20; color: ${tag.color};">
                            ${escapeHtml(tag.name)}
                        </span>
                        <span class="tag-ref-count">${tag.reference_count} 次引用</span>
                    </div>
                `).join('');
                suggestions.style.display = 'block';
            } else {
                suggestions.innerHTML = `<div class="tag-suggestion-item no-results">输入"${escapeHtml(query)}"并按回车创建新标签</div>`;
                suggestions.style.display = 'block';
            }
        } else {
            suggestions.innerHTML = `<div class="tag-suggestion-item no-results">输入"${escapeHtml(query)}"并按回车创建新标签</div>`;
            suggestions.style.display = 'block';
        }
    }

    function selectSuggestedTag(id, name, color) {
        if (selectedTagIds.includes(id)) return;
        
        selectedTagIds.push(id);
        renderTagItem(id, name, color);
        document.getElementById('tagSearchInput').value = '';
        document.getElementById('tagSuggestions').innerHTML = '';
        document.getElementById('tagSuggestions').style.display = 'none';
        updateHiddenFields();
    }

    function addNewTagName(name) {
        name = name.trim();
        if (!name) return;
        
        if (selectedTagNames.includes(name)) {
            showToast('该标签已添加', 'error');
            return;
        }
        
        selectedTagNames.push(name);
        const color = '#' + md5(name).substring(0, 6);
        renderTagItem(null, name, color, true);
        updateHiddenFields();
    }

    function renderTagItem(id, name, color, isNew = false) {
        const container = document.getElementById('selectedTags');
        const tagSpan = document.createElement('span');
        tagSpan.className = 'tag-item';
        tagSpan.style.backgroundColor = color + '20';
        tagSpan.style.color = color;
        tagSpan.dataset.id = id || '';
        tagSpan.dataset.name = name;
        tagSpan.innerHTML = `
            ${escapeHtml(name)}
            <button type="button" class="tag-remove" onclick="removeTag(${id}, '${escapeHtml(name)}')">×</button>
        `;
        container.appendChild(tagSpan);
    }

    function removeTag(id, name) {
        if (id !== null) {
            selectedTagIds = selectedTagIds.filter(tid => tid !== id);
        }
        if (name !== null) {
            selectedTagNames = selectedTagNames.filter(tname => tname !== name);
        }
        
        const container = document.getElementById('selectedTags');
        const tagItems = container.querySelectorAll('.tag-item');
        tagItems.forEach(item => {
            const itemId = parseInt(item.dataset.id) || null;
            const itemName = item.dataset.name;
            if ((id !== null && itemId === id) || (name !== null && itemName === name)) {
                item.remove();
            }
        });
        
        updateHiddenFields();
    }

    function updateHiddenFields() {
        document.getElementById('tagIds').value = JSON.stringify(selectedTagIds);
        document.getElementById('tagNames').value = JSON.stringify(selectedTagNames);
    }

    function md5(string) {
        function rotateLeft(lValue, iShiftBits) {
            return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
        }
        function addUnsigned(lX, lY) {
            var lX4, lY4, lX8, lY8, lResult;
            lX8 = (lX & 0x80000000);
            lY8 = (lY & 0x80000000);
            lX4 = (lX & 0x40000000);
            lY4 = (lY & 0x40000000);
            lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
            if (lX4 & lY4) return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
            if (lX4 | lY4) {
                if (lResult & 0x40000000) return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                else return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
            } else return (lResult ^ lX8 ^ lY8);
        }
        function F(x, y, z) { return (x & y) | ((~x) & z); }
        function G(x, y, z) { return (x & z) | (y & (~z)); }
        function H(x, y, z) { return (x ^ y ^ z); }
        function I(x, y, z) { return (y ^ (x | (~z))); }
        function FF(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(F(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        }
        function GG(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(G(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        }
        function HH(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(H(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        }
        function II(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(I(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        }
        function convertToWordArray(str) {
            var lWordCount;
            var lMessageLength = str.length;
            var lNumberOfWords_temp1 = lMessageLength + 8;
            var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
            var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
            var lWordArray = new Array(lNumberOfWords - 1);
            var lBytePosition = 0;
            var lByteCount = 0;
            while (lByteCount < lMessageLength) {
                lWordCount = (lByteCount - (lByteCount % 4)) / 4;
                lBytePosition = (lByteCount % 4) * 8;
                lWordArray[lWordCount] = (lWordArray[lWordCount] | (str.charCodeAt(lByteCount) << lBytePosition));
                lByteCount++;
            }
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;
            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
            lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
            lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
            return lWordArray;
        }
        function wordToHex(lValue) {
            var wordToHexValue = '', wordToHexValue_temp = '', lByte, lCount;
            for (lCount = 0; lCount <= 3; lCount++) {
                lByte = (lValue >>> (lCount * 8)) & 255;
                wordToHexValue_temp = '0' + lByte.toString(16);
                wordToHexValue = wordToHexValue + wordToHexValue_temp.substr(wordToHexValue_temp.length - 2, 2);
            }
            return wordToHexValue;
        }
        var x = convertToWordArray(string);
        var a = 0x67452301, b = 0xEFCDAB89, c = 0x98BADCFE, d = 0x10325476;
        var S11 = 7, S12 = 12, S13 = 17, S14 = 22;
        var S21 = 5, S22 = 9, S23 = 14, S24 = 20;
        var S31 = 4, S32 = 11, S33 = 16, S34 = 23;
        var S41 = 6, S42 = 10, S43 = 15, S44 = 21;
        var k;
        var AA, BB, CC, DD;
        for (k = 0; k < x.length; k += 16) {
            AA = a; BB = b; CC = c; DD = d;
            a = FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
            d = FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
            c = FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
            b = FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
            a = FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
            d = FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
            c = FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
            b = FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
            a = FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
            d = FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
            c = FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
            b = FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
            a = FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
            d = FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
            c = FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
            b = FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
            a = GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
            d = GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
            c = GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
            b = GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
            a = GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
            d = GG(d, a, b, c, x[k + 10], S22, 0x02441453);
            c = GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
            b = GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
            a = GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
            d = GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
            c = GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
            b = GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
            a = GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
            d = GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
            c = GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
            b = GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
            a = HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
            d = HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
            c = HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
            b = HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
            a = HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
            d = HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
            c = HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
            b = HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
            a = HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
            d = HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
            c = HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
            b = HH(b, c, d, a, x[k + 6], S34, 0x04881D05);
            a = HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
            d = HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
            c = HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
            b = HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
            a = II(a, b, c, d, x[k + 0], S41, 0xF4292244);
            d = II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
            c = II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
            b = II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
            a = II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
            d = II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
            c = II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
            b = II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
            a = II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
            d = II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
            c = II(c, d, a, b, x[k + 6], S43, 0xA3014314);
            b = II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
            a = II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
            d = II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
            c = II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
            b = II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
            a = addUnsigned(a, AA);
            b = addUnsigned(b, BB);
            c = addUnsigned(c, CC);
            d = addUnsigned(d, DD);
        }
        return (wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d)).toLowerCase();
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
</body>
</html>
