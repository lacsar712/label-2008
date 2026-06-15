-- 设置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 创建数据库
CREATE DATABASE IF NOT EXISTS notice_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE notice_db;

-- 创建公告分类表
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '分类名称',
    emoji VARCHAR(32) DEFAULT NULL COMMENT 'emoji图标',
    color VARCHAR(32) DEFAULT '#6366f1' COMMENT '颜色(十六进制)',
    description VARCHAR(255) DEFAULT NULL COMMENT '分类描述',
    sort_order INT DEFAULT 0 COMMENT '排序值',
    status ENUM('enabled', 'disabled') DEFAULT 'enabled' COMMENT '启用状态',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建公告信息表
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL COMMENT '分类ID',
    title VARCHAR(255) NOT NULL COMMENT '公告标题',
    content TEXT NOT NULL COMMENT '公告内容',
    author VARCHAR(100) NOT NULL COMMENT '发布人',
    author_id INT DEFAULT NULL COMMENT '发布人用户ID',
    publish_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
    update_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    status ENUM('published', 'draft') DEFAULT 'published' COMMENT '状态',
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '优先级',
    views INT DEFAULT 0 COMMENT '浏览次数',
    INDEX idx_publish_date (publish_date),
    INDEX idx_status (status),
    INDEX idx_author_id (author_id),
    INDEX idx_category_id (category_id),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建用户表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT '邮箱',
    password VARCHAR(255) NOT NULL COMMENT '加密后的密码',
    avatar_url VARCHAR(255) DEFAULT NULL COMMENT '头像URL',
    nickname VARCHAR(50) DEFAULT NULL COMMENT '昵称',
    bio VARCHAR(255) DEFAULT NULL COMMENT '个人简介',
    register_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
    last_login_time DATETIME DEFAULT NULL COMMENT '最后登录时间',
    last_login_ip VARCHAR(45) DEFAULT NULL COMMENT '最后登录IP',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active' COMMENT '状态',
    reset_token VARCHAR(255) DEFAULT NULL COMMENT '密码重置令牌',
    reset_token_expire DATETIME DEFAULT NULL COMMENT '重置令牌过期时间',
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建频率限制表
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL COMMENT 'IP地址',
    action VARCHAR(50) NOT NULL COMMENT '操作类型',
    count INT DEFAULT 0 COMMENT '次数',
    window_start DATETIME NOT NULL COMMENT '时间窗口开始',
    UNIQUE KEY unique_ip_action (ip, action),
    INDEX idx_ip (ip),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入示例数据
INSERT INTO notices (title, content, author, priority, status, publish_date) VALUES
('欢迎使用公告信息管理系统', '这是一个功能完善的公告信息管理系统，支持添加、编辑、删除和查询公告信息。', '系统管理员', 'high', 'published', NOW()),
('系统维护通知', '本系统将于本周六进行例行维护，维护时间为凌晨2:00-6:00，期间系统将暂停服务。', '技术部', 'high', 'published', NOW()),
('新功能上线', '我们很高兴地宣布，系统新增了分页显示和高级搜索功能，欢迎体验！', '产品部', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('安全提醒', '请各位用户定期修改密码，确保账户安全。如发现异常情况，请及时联系管理员。', '安全部', 'high', 'published', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('假期安排通知', '根据国家法定节假日安排，本系统将在春节期间正常运行，技术支持团队将保持在线。', '人事部', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('用户调查问卷', '为了更好地改进我们的服务，诚邀您参与用户满意度调查，您的意见对我们非常重要。', '客服部', 'low', 'published', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('培训课程通知', '本月将举办系统使用培训课程，欢迎新用户报名参加，详情请查看培训中心。', '培训部', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 7 DAY)),
('版本更新说明', '系统已更新至v2.0版本，新增了数据导出、批量操作等功能，提升了系统性能。', '技术部', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 10 DAY));

-- 插入示例分类数据
INSERT INTO categories (name, emoji, color, description, sort_order, status) VALUES
('系统公告', '📢', '#6366f1', '系统相关的公告通知', 1, 'enabled'),
('技术维护', '🔧', '#3b82f6', '技术维护和升级通知', 2, 'enabled'),
('安全提醒', '🔒', '#ef4444', '安全相关的提醒公告', 3, 'enabled'),
('人事通知', '📋', '#f59e0b', '人事相关的通知公告', 4, 'enabled'),
('活动通知', '🎉', '#10b981', '各类活动通知', 5, 'enabled'),
('产品更新', '🚀', '#8b5cf6', '产品功能更新说明', 6, 'disabled');

-- 创建角色表
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE COMMENT '角色标识',
    display_name VARCHAR(100) NOT NULL COMMENT '角色显示名称',
    description VARCHAR(255) DEFAULT NULL COMMENT '角色描述',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建权限表
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE COMMENT '权限标识',
    display_name VARCHAR(100) NOT NULL COMMENT '权限显示名称',
    description VARCHAR(255) DEFAULT NULL COMMENT '权限描述',
    category VARCHAR(50) DEFAULT 'other' COMMENT '权限分类',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_name (name),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建角色权限关联表
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL COMMENT '角色ID',
    permission_id INT NOT NULL COMMENT '权限ID',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建用户角色关联表
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '用户ID',
    role_id INT NOT NULL COMMENT '角色ID',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    UNIQUE KEY unique_user_role (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入预置权限数据
INSERT INTO permissions (name, display_name, description, category) VALUES
-- 公告相关权限
('notice:view', '查看公告', '查看公告列表和详情', 'notice'),
('notice:create', '创建公告', '发布新公告', 'notice'),
('notice:edit', '编辑公告', '修改已有公告', 'notice'),
('notice:delete', '删除公告', '删除公告', 'notice'),
('notice:recycle', '回收站管理', '管理回收站和恢复公告', 'notice'),
-- 分类相关权限
('category:view', '查看分类', '查看分类列表', 'category'),
('category:create', '创建分类', '添加新分类', 'category'),
('category:edit', '编辑分类', '修改分类信息', 'category'),
('category:delete', '删除分类', '删除分类', 'category'),
-- 用户相关权限
('user:view', '查看用户', '查看用户列表', 'user'),
('user:assign_role', '分配用户角色', '为用户分配或解绑角色', 'user'),
-- 角色相关权限
('role:view', '查看角色', '查看角色列表和详情', 'role'),
('role:create', '创建角色', '添加新角色', 'role'),
('role:edit', '编辑角色', '修改角色信息', 'role'),
('role:delete', '删除角色', '删除角色', 'role'),
('role:assign_permission', '分配权限', '为角色分配权限', 'role');

-- 插入预置角色数据
INSERT INTO roles (name, display_name, description) VALUES
('super_admin', '超级管理员', '拥有系统所有权限'),
('editor', '编辑', '负责公告和分类的管理'),
('guest', '访客', '仅拥有查看权限');

-- 为超级管理员分配所有权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- 为编辑分配公告和分类管理权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE category IN ('notice', 'category');

-- 创建标签表
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE COMMENT '标签名称',
    color VARCHAR(32) DEFAULT '#6366f1' COMMENT '标签颜色(十六进制)',
    reference_count INT DEFAULT 0 COMMENT '引用次数',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_name (name),
    INDEX idx_reference_count (reference_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建公告标签关联表
CREATE TABLE IF NOT EXISTS notice_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notice_id INT NOT NULL COMMENT '公告ID',
    tag_id INT NOT NULL COMMENT '标签ID',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    UNIQUE KEY unique_notice_tag (notice_id, tag_id),
    INDEX idx_notice_id (notice_id),
    INDEX idx_tag_id (tag_id),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入标签相关权限
INSERT INTO permissions (name, display_name, description, category) VALUES
('tag:view', '查看标签', '查看标签列表', 'tag'),
('tag:create', '创建标签', '添加新标签', 'tag'),
('tag:edit', '编辑标签', '修改标签信息', 'tag'),
('tag:delete', '删除标签', '删除标签', 'tag'),
('tag:merge', '合并标签', '合并两个标签', 'tag');

-- 为超级管理员分配标签权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE category = 'tag';

-- 为编辑分配标签管理权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE category = 'tag';

-- 为访客分配仅查看标签权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE name = 'tag:view';

-- 为访客分配仅查看权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE name IN ('notice:view', 'category:view');

-- 创建站内消息表
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receiver_id INT NOT NULL COMMENT '接收人用户ID',
    type VARCHAR(50) NOT NULL DEFAULT 'system' COMMENT '消息类型: system, notice, security, activity',
    title VARCHAR(255) NOT NULL COMMENT '消息标题',
    body TEXT DEFAULT NULL COMMENT '消息正文',
    entity_type VARCHAR(50) DEFAULT NULL COMMENT '关联实体类型: notice, category, tag等',
    entity_id INT DEFAULT NULL COMMENT '关联实体ID',
    is_read TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已读: 0未读, 1已读',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_receiver_id (receiver_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_receiver_read (receiver_id, is_read),
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入消息相关权限
INSERT INTO permissions (name, display_name, description, category) VALUES
('message:view', '查看消息', '查看站内消息', 'message'),
('message:send', '发送消息', '发送站内消息', 'message'),
('message:manage', '管理消息', '管理所有用户消息', 'message');

-- 为超级管理员分配消息权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE category = 'message';

-- 为编辑分配查看消息权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE name = 'message:view';

-- 为访客分配查看消息权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE name = 'message:view';

-- 插入数据导入导出相关权限
INSERT INTO permissions (name, display_name, description, category) VALUES
('notice:export', '导出公告', '导出公告数据为CSV/Excel', 'notice'),
('notice:import', '导入公告', '从CSV/Excel批量导入公告', 'notice');

-- 为超级管理员分配导入导出权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name IN ('notice:export', 'notice:import');

-- 为编辑分配导入导出权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE name IN ('notice:export', 'notice:import');

-- 创建操作日志表
CREATE TABLE IF NOT EXISTS operation_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT '操作人ID',
    user_nickname VARCHAR(100) DEFAULT NULL COMMENT '操作人昵称',
    operation_type VARCHAR(50) NOT NULL COMMENT '操作类型: create, update, delete, batch_update, batch_delete, assign_permission, assign_role',
    target_type VARCHAR(50) NOT NULL COMMENT '目标类型: notice, category, tag, role, user_permission, user_role',
    target_id VARCHAR(100) DEFAULT NULL COMMENT '目标ID，批量操作时用逗号分隔',
    before_data JSON DEFAULT NULL COMMENT '变更前数据快照',
    after_data JSON DEFAULT NULL COMMENT '变更后数据快照',
    ip VARCHAR(45) DEFAULT NULL COMMENT '操作IP地址',
    user_agent VARCHAR(500) DEFAULT NULL COMMENT '浏览器User-Agent',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
    INDEX idx_user_id (user_id),
    INDEX idx_operation_type (operation_type),
    INDEX idx_target_type (target_type),
    INDEX idx_target_id (target_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入操作日志相关权限
INSERT INTO permissions (name, display_name, description, category) VALUES
('log:view', '查看操作日志', '查看系统操作日志', 'log'),
('log:export', '导出操作日志', '导出操作日志为CSV', 'log');

-- 为超级管理员分配操作日志权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE category = 'log';

-- 创建浏览日志表
CREATE TABLE IF NOT EXISTS view_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    notice_id INT NOT NULL COMMENT '公告ID',
    visitor_id VARCHAR(100) DEFAULT NULL COMMENT '访客ID（登录用户为用户ID，未登录为session_id）',
    ip VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
    region VARCHAR(100) DEFAULT NULL COMMENT '地区',
    client_type VARCHAR(20) DEFAULT NULL COMMENT '客户端类型: desktop, mobile, tablet, other',
    user_agent VARCHAR(500) DEFAULT NULL COMMENT '浏览器User-Agent',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '访问时间',
    INDEX idx_notice_id (notice_id),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_created_at (created_at),
    INDEX idx_ip (ip),
    INDEX idx_region (region),
    INDEX idx_client_type (client_type),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入浏览分析相关权限
INSERT INTO permissions (name, display_name, description, category) VALUES
('view_analysis:view', '查看浏览分析', '查看公告浏览行为分析数据', 'analysis');

-- 为超级管理员分配浏览分析权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE category = 'analysis';

-- 为编辑分配浏览分析权限
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE category = 'analysis';
