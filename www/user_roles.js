let currentUserPermissions = [];
let usersData = [];
let rolesData = [];
let currentPage = 1;
let perPage = 10;
let currentSearch = '';
let editingUserId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadCurrentUserPermissions().then(() => {
        loadRoles();
        loadUsers();
    });
    initSearchForm();
});

async function loadCurrentUserPermissions() {
    const result = await apiRequest('me/permissions', 'GET');
    if (result.code === 200 && result.data) {
        currentUserPermissions = result.data.permission_names || [];
    }
}

function hasPermission(permission) {
    return currentUserPermissions.includes(permission);
}

async function loadRoles() {
    const result = await apiRequest('roles/list', 'GET');
    if (result.code === 200 && result.data) {
        rolesData = result.data;
    }
}

function initSearchForm() {
    const form = document.getElementById('searchForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            currentSearch = document.getElementById('searchInput').value.trim();
            currentPage = 1;
            loadUsers();
        });
    }
}

function resetSearch() {
    document.getElementById('searchInput').value = '';
    currentSearch = '';
    currentPage = 1;
    loadUsers();
}

async function loadUsers() {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="loading">加载中...</td></tr>';
    
    let url = `users/list?page=${currentPage}&per_page=${perPage}`;
    if (currentSearch) {
        url += `&search=${encodeURIComponent(currentSearch)}`;
    }
    
    const result = await apiRequest(url, 'GET');
    
    if (result.code === 200 && result.data) {
        usersData = result.data.list || [];
        renderUsersTable(usersData);
        renderPagination(result.data.pagination);
    } else {
        tbody.innerHTML = '<tr><td colspan="7" class="no-data">加载失败: ' + result.message + '</td></tr>';
    }
}

function renderUsersTable(users) {
    const tbody = document.getElementById('usersTableBody');
    const canAssignRole = hasPermission('user:assign_role');
    const systemRoles = ['super_admin', 'editor', 'guest'];
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="no-data">暂无用户数据</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => {
        const isEditing = editingUserId === user.id;
        const displayName = user.nickname || user.username;
        
        return `
            <tr class="${isEditing ? 'editing' : ''}">
                <td>${user.id}</td>
                <td>
                    ${user.avatar_url ? 
                        `<img src="${escapeHtml(user.avatar_url)}" alt="" class="user-avatar-small">` :
                        `<div class="user-avatar-placeholder-small">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>`
                    }
                    <span class="user-name-cell">${escapeHtml(displayName)}</span>
                    <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 2px;">@${escapeHtml(user.username)}</div>
                </td>
                <td>${escapeHtml(user.email)}</td>
                <td>
                    <span class="status-badge ${user.status}">${getStatusText(user.status)}</span>
                </td>
                <td>
                    ${isEditing ? renderRoleCheckboxes(user) : renderRoleTags(user.roles)}
                </td>
                <td>${formatDate(user.register_time)}</td>
                <td>
                    ${canAssignRole ? (isEditing ? `
                        <button class="save-btn" onclick="saveUserRoles(${user.id})" id="saveBtn_${user.id}">保存</button>
                        <button class="cancel-btn" onclick="cancelEdit()" style="margin-left: 4px;">取消</button>
                    ` : `
                        <button class="edit-btn-inline" onclick="startEdit(${user.id})">分配角色</button>
                    `) : ''}
                </td>
            </tr>
        `;
    }).join('');
}

function renderRoleTags(roles) {
    if (!roles || roles.length === 0) {
        return '<span class="text-muted">未分配角色</span>';
    }
    
    const systemRoles = ['super_admin', 'editor', 'guest'];
    
    return `
        <div class="role-tags">
            ${roles.map(role => `
                <span class="role-tag ${systemRoles.includes(role.name) ? 'system' : ''}">
                    ${escapeHtml(role.display_name)}
                </span>
            `).join('')}
        </div>
    `;
}

function renderRoleCheckboxes(user) {
    const userRoleIds = user.role_ids || [];
    
    return `
        <div class="role-checkboxes" id="roleCheckboxes_${user.id}">
            ${rolesData.map(role => `
                <div class="role-checkbox-item">
                    <input type="checkbox" id="role_${user.id}_${role.id}" value="${role.id}" 
                           ${userRoleIds.includes(role.id) ? 'checked' : ''}>
                    <label for="role_${user.id}_${role.id}">${escapeHtml(role.display_name)}</label>
                </div>
            `).join('')}
        </div>
    `;
}

function getStatusText(status) {
    const statusMap = {
        'active': '正常',
        'inactive': '未激活',
        'banned': '已禁用'
    };
    return statusMap[status] || status;
}

function startEdit(userId) {
    if (editingUserId !== null) {
        cancelEdit();
    }
    editingUserId = userId;
    renderUsersTable(usersData);
}

function cancelEdit() {
    editingUserId = null;
    renderUsersTable(usersData);
}

async function saveUserRoles(userId) {
    const saveBtn = document.getElementById(`saveBtn_${userId}`);
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = '保存中...';
    }
    
    const checkboxes = document.querySelectorAll(`#roleCheckboxes_${userId} input[type="checkbox"]:checked`);
    const roleIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    const result = await apiRequest('users/assign_roles', 'POST', {
        user_id: userId,
        role_ids: roleIds
    });
    
    if (result.code === 200) {
        showToast(result.message, 'success');
        editingUserId = null;
        loadUsers();
    } else {
        showToast(result.message, 'error');
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = '保存';
        }
    }
}

function renderPagination(pagination) {
    const container = document.getElementById('pagination');
    if (!pagination || pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    const { page, total_pages } = pagination;
    let html = '';
    
    if (page > 1) {
        html += `
            <a href="javascript:void(0)" class="page-link" onclick="goToPage(${page - 1})">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                上一页
            </a>
        `;
    }
    
    const start_page = Math.max(1, page - 2);
    const end_page = Math.min(total_pages, page + 2);
    
    if (start_page > 1) {
        html += `<a href="javascript:void(0)" class="page-number" onclick="goToPage(1)">1</a>`;
        if (start_page > 2) {
            html += `<span class="page-ellipsis">...</span>`;
        }
    }
    
    for (let i = start_page; i <= end_page; i++) {
        html += `
            <a href="javascript:void(0)" class="page-number ${i === page ? 'active' : ''}" 
               onclick="goToPage(${i})">${i}</a>
        `;
    }
    
    if (end_page < total_pages) {
        if (end_page < total_pages - 1) {
            html += `<span class="page-ellipsis">...</span>`;
        }
        html += `<a href="javascript:void(0)" class="page-number" onclick="goToPage(${total_pages})">${total_pages}</a>`;
    }
    
    if (page < total_pages) {
        html += `
            <a href="javascript:void(0)" class="page-link" onclick="goToPage(${page + 1})">
                下一页
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        `;
    }
    
    container.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadUsers();
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('zh-CN');
}
