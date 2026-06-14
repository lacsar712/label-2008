let currentUserPermissions = [];
let rolesData = [];
let permissionsData = [];

document.addEventListener('DOMContentLoaded', function() {
    loadCurrentUserPermissions().then(() => {
        initPageActions();
        loadRoles();
    });
    initRoleForm();
});

async function loadCurrentUserPermissions() {
    const result = await apiRequest('me/permissions', 'GET');
    if (result.code === 200 && result.data) {
        currentUserPermissions = result.data.permission_names || [];
    }
}

function initPageActions() {
    const addBtn = document.getElementById('addRoleBtn');
    if (addBtn && !hasPermission('role:create')) {
        addBtn.style.display = 'none';
    } else if (addBtn) {
        addBtn.addEventListener('click', openAddRoleModal);
    }
}

function hasPermission(permission) {
    return currentUserPermissions.includes(permission);
}

async function loadRoles() {
    const tbody = document.getElementById('rolesTableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="loading">加载中...</td></tr>';
    
    const result = await apiRequest('roles/list', 'GET');
    
    if (result.code === 200 && result.data) {
        rolesData = result.data;
        renderRolesTable(result.data);
    } else {
        tbody.innerHTML = '<tr><td colspan="8" class="no-data">加载失败: ' + result.message + '</td></tr>';
    }
}

function renderRolesTable(roles) {
    const tbody = document.getElementById('rolesTableBody');
    
    if (roles.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="no-data">暂无角色数据</td></tr>';
        return;
    }
    
    const systemRoles = ['super_admin', 'editor', 'guest'];
    
    tbody.innerHTML = roles.map(role => {
        const isSystemRole = systemRoles.includes(role.name);
        const canEdit = hasPermission('role:edit') && !isSystemRole;
        const canDelete = hasPermission('role:delete') && !isSystemRole;
        const canAssignPermission = hasPermission('role:assign_permission');
        
        return `
            <tr>
                <td>${role.id}</td>
                <td>
                    <span class="role-badge ${isSystemRole ? 'system' : ''}">${role.name}</span>
                </td>
                <td class="role-name-cell">${escapeHtml(role.display_name)}</td>
                <td>${escapeHtml(role.description || '-')}</td>
                <td>${role.user_count || 0}</td>
                <td>${role.permission_count || 0}</td>
                <td>${formatDate(role.created_at)}</td>
                <td class="action-buttons">
                    ${canAssignPermission ? `
                    <button class="btn-icon-action edit" title="权限配置" onclick="openPermissionModal(${role.id}, '${escapeHtml(role.display_name)}')">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 15L9 12L10.5 10.5L12 12L16.5 7.5L18 9L12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 14L4.5 18.5L3 21L5.5 19.5L10 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 4L18.5 8.5L16 11L11.5 6.5L14 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13 5L11 7L7 11L5 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    ` : ''}
                    ${canEdit ? `
                    <button class="btn-icon-action edit" title="编辑" onclick="openEditRoleModal(${role.id})">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    ` : ''}
                    ${canDelete ? `
                    <button class="btn-icon-action delete" title="删除" onclick="deleteRole(${role.id})">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

function openAddRoleModal() {
    document.getElementById('roleModalTitle').textContent = '新增角色';
    document.getElementById('roleId').value = '';
    document.getElementById('roleName').value = '';
    document.getElementById('roleName').disabled = false;
    document.getElementById('roleDisplayName').value = '';
    document.getElementById('roleDescription').value = '';
    document.getElementById('roleModal').classList.add('show');
}

function openEditRoleModal(roleId) {
    const role = rolesData.find(r => r.id === roleId);
    if (!role) return;
    
    document.getElementById('roleModalTitle').textContent = '编辑角色';
    document.getElementById('roleId').value = role.id;
    document.getElementById('roleName').value = role.name;
    document.getElementById('roleName').disabled = true;
    document.getElementById('roleDisplayName').value = role.display_name;
    document.getElementById('roleDescription').value = role.description || '';
    document.getElementById('roleModal').classList.add('show');
}

function closeRoleModal() {
    document.getElementById('roleModal').classList.remove('show');
}

function initRoleForm() {
    const form = document.getElementById('roleForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const roleId = document.getElementById('roleId').value;
        const name = document.getElementById('roleName').value.trim();
        const display_name = document.getElementById('roleDisplayName').value.trim();
        const description = document.getElementById('roleDescription').value.trim();
        
        if (!display_name) {
            showToast('请输入角色名称', 'error');
            return;
        }
        
        if (!roleId && !name) {
            showToast('请输入角色标识', 'error');
            return;
        }
        
        if (!roleId && !/^[a-z_]+$/.test(name)) {
            showToast('角色标识只能包含小写字母和下划线', 'error');
            return;
        }
        
        const data = {
            display_name,
            description
        };
        
        if (!roleId) {
            data.name = name;
        } else {
            data.id = parseInt(roleId);
        }
        
        const endpoint = roleId ? 'roles/update' : 'roles/create';
        const result = await apiRequest(endpoint, 'POST', data);
        
        if (result.code === 200) {
            showToast(result.message, 'success');
            closeRoleModal();
            loadRoles();
        } else {
            showToast(result.message, 'error');
        }
    });
}

async function deleteRole(roleId) {
    if (!confirm('确定要删除这个角色吗？删除后无法恢复。')) return;
    
    const result = await apiRequest('roles/delete', 'POST', { id: roleId });
    
    if (result.code === 200) {
        showToast(result.message, 'success');
        loadRoles();
    } else {
        showToast(result.message, 'error');
    }
}

async function openPermissionModal(roleId, roleName) {
    document.getElementById('permissionModalTitle').textContent = `权限配置 - ${roleName}`;
    document.getElementById('permissionRoleId').value = roleId;
    document.getElementById('permissionMatrix').innerHTML = '<div class="loading">加载中...</div>';
    document.getElementById('permissionModal').classList.add('show');
    
    const [permsResult, roleResult] = await Promise.all([
        apiRequest('permissions/list', 'GET'),
        apiRequest(`roles/detail?id=${roleId}`, 'GET')
    ]);
    
    if (permsResult.code === 200 && permsResult.data) {
        permissionsData = permsResult.data.list || [];
    }
    
    let rolePermissionIds = [];
    if (roleResult.code === 200 && roleResult.data) {
        rolePermissionIds = roleResult.data.permission_ids || [];
    }
    
    renderPermissionMatrix(permissionsData, rolePermissionIds);
}

function renderPermissionMatrix(permissions, selectedIds) {
    const container = document.getElementById('permissionMatrix');
    const grouped = {};
    
    permissions.forEach(perm => {
        const category = perm.category;
        if (!grouped[category]) {
            grouped[category] = [];
        }
        grouped[category].push(perm);
    });
    
    const categoryNames = {
        'notice': '公告管理',
        'category': '分类管理',
        'user': '用户管理',
        'role': '角色管理',
        'other': '其他'
    };
    
    container.innerHTML = Object.keys(grouped).map(category => {
        const perms = grouped[category];
        const categoryName = categoryNames[category] || category;
        const allSelected = perms.every(p => selectedIds.includes(p.id));
        
        return `
            <div class="permission-category">
                <div class="select-all-row">
                    <input type="checkbox" id="select_all_${category}" ${allSelected ? 'checked' : ''} 
                           onchange="toggleCategoryPermissions('${category}', this.checked)">
                    <label for="select_all_${category}" style="font-weight: 600; color: var(--text-primary);">全选 ${categoryName}</label>
                    <span class="text-muted" style="margin-left: auto; font-size: 0.75rem;">
                        ${perms.filter(p => selectedIds.includes(p.id)).length}/${perms.length}
                    </span>
                </div>
                <div class="permission-grid">
                    ${perms.map(perm => `
                        <div class="permission-item">
                            <input type="checkbox" id="perm_${perm.id}" value="${perm.id}" 
                                   class="perm-checkbox" data-category="${category}"
                                   ${selectedIds.includes(perm.id) ? 'checked' : ''}
                                   onchange="updateCategorySelectAll('${category}')">
                            <label for="perm_${perm.id}">
                                <span class="permission-item-name">${escapeHtml(perm.display_name)}</span>
                                <span class="permission-item-desc">${escapeHtml(perm.description || '')}</span>
                            </label>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }).join('');
}

function toggleCategoryPermissions(category, checked) {
    document.querySelectorAll(`.perm-checkbox[data-category="${category}"]`).forEach(checkbox => {
        checkbox.checked = checked;
    });
}

function updateCategorySelectAll(category) {
    const checkboxes = document.querySelectorAll(`.perm-checkbox[data-category="${category}"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    const selectAll = document.getElementById(`select_all_${category}`);
    if (selectAll) {
        selectAll.checked = allChecked;
    }
}

function closePermissionModal() {
    document.getElementById('permissionModal').classList.remove('show');
}

async function savePermissions() {
    const roleId = parseInt(document.getElementById('permissionRoleId').value);
    const selectedIds = Array.from(document.querySelectorAll('.perm-checkbox:checked'))
        .map(cb => parseInt(cb.value));
    
    const result = await apiRequest('roles/save_permissions', 'POST', {
        role_id: roleId,
        permission_ids: selectedIds
    });
    
    if (result.code === 200) {
        showToast(result.message, 'success');
        closePermissionModal();
        loadRoles();
    } else {
        showToast(result.message, 'error');
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('zh-CN');
}
