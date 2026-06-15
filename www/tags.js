let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let selectedIds = [];
let allTags = [];

document.addEventListener('DOMContentLoaded', function() {
    loadTags();

    document.getElementById('tagForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveTag();
    });

    document.getElementById('tagKeyword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadTags();
        }
    });
});

async function loadTags() {
    const keyword = document.getElementById('tagKeyword').value;

    let url = `tags/list?page=${currentPage}&per_page=${perPage}`;
    if (keyword) url += `&keyword=${encodeURIComponent(keyword)}`;

    const result = await apiRequest(url, 'GET');
    if (result.code === 200) {
        allTags = result.data.list;
        renderTags(allTags);
        renderPagination(result.data.pagination);
        totalPages = result.data.pagination.total_pages;
    } else {
        showToast(result.message, 'error');
    }
}

function renderTags(tags) {
    const tbody = document.getElementById('tagTableBody');
    
    if (tags.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data">暂无标签数据</td></tr>';
        return;
    }

    tbody.innerHTML = tags.map(tag => `
        <tr class="tag-row" data-id="${tag.id}">
            <td>
                <input type="checkbox" class="tag-checkbox" value="${tag.id}" 
                    ${selectedIds.includes(tag.id) ? 'checked' : ''} 
                    onchange="toggleSelect(${tag.id})">
            </td>
            <td>
                <span class="color-preview" style="background-color: ${tag.color};"></span>
                <code>${tag.color}</code>
            </td>
            <td>
                <span class="tag-badge" style="background-color: ${tag.color}20; color: ${tag.color};">
                    ${escapeHtml(tag.name)}
                </span>
            </td>
            <td>
                <span class="reference-count">${tag.reference_count}</span>
            </td>
            <td>${tag.created_at}</td>
            <td class="action-buttons">
                <button class="btn-icon-action edit" title="编辑" onclick="editTag(${tag.id})" data-permission="tag:edit">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button class="btn-icon-action delete" title="删除" onclick="deleteTag(${tag.id})" data-permission="tag:delete">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');

    updateBatchActions();
    initPermissionBasedUI();
}

function renderPagination(pagination) {
    const container = document.getElementById('tagPagination');
    const { page, total_pages } = pagination;
    currentPage = page;

    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';

    if (page > 1) {
        html += `<a href="javascript:;" onclick="goToPage(${page - 1})" class="page-link">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2"/></svg>
            上一页
        </a>`;
    }

    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(total_pages, page + 2);

    if (startPage > 1) {
        html += `<a href="javascript:;" onclick="goToPage(1)" class="page-number">1</a>`;
        if (startPage > 2) {
            html += '<span class="page-ellipsis">...</span>';
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<a href="javascript:;" onclick="goToPage(${i})" class="page-number ${i === page ? 'active' : ''}">${i}</a>`;
    }

    if (endPage < total_pages) {
        if (endPage < total_pages - 1) {
            html += '<span class="page-ellipsis">...</span>';
        }
        html += `<a href="javascript:;" onclick="goToPage(${total_pages})" class="page-number">${total_pages}</a>`;
    }

    if (page < total_pages) {
        html += `<a href="javascript:;" onclick="goToPage(${page + 1})" class="page-link">
            下一页
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2"/></svg>
        </a>`;
    }

    container.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadTags();
}

function resetTagSearch() {
    document.getElementById('tagKeyword').value = '';
    currentPage = 1;
    loadTags();
}

function openTagModal() {
    document.getElementById('tagModalTitle').textContent = '新增标签';
    document.getElementById('tagId').value = '';
    document.getElementById('tagForm').reset();
    document.getElementById('tagColor').value = '#6366f1';
    document.getElementById('tagModal').classList.add('show');
}

async function editTag(id) {
    const result = await apiRequest(`tags/detail?id=${id}`, 'GET');
    if (result.code === 200) {
        const tag = result.data;
        document.getElementById('tagModalTitle').textContent = '编辑标签';
        document.getElementById('tagId').value = tag.id;
        document.getElementById('tagName').value = tag.name;
        document.getElementById('tagColor').value = tag.color || '#6366f1';
        document.getElementById('tagModal').classList.add('show');
    } else {
        showToast(result.message, 'error');
    }
}

function closeTagModal() {
    document.getElementById('tagModal').classList.remove('show');
}

async function saveTag() {
    const id = document.getElementById('tagId').value;
    const data = {
        name: document.getElementById('tagName').value.trim(),
        color: document.getElementById('tagColor').value
    };

    if (!data.name) {
        showToast('标签名称不能为空', 'error');
        return;
    }

    const endpoint = id ? 'tags/update' : 'tags/create';
    if (id) data.id = parseInt(id);

    const result = await apiRequest(endpoint, 'POST', data);
    if (result.code === 200) {
        showToast(id ? '更新成功' : '创建成功', 'success');
        closeTagModal();
        loadTags();
    } else {
        showToast(result.message, 'error');
    }
}

async function deleteTag(id) {
    if (!confirm('确定要删除这个标签吗？删除后已关联的公告标签将被清空。')) {
        return;
    }

    const result = await apiRequest('tags/delete', 'POST', { id: id });
    if (result.code === 200) {
        showToast('删除成功', 'success');
        loadTags();
    } else {
        showToast(result.message, 'error');
    }
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.tag-checkbox');
    const isChecked = checkbox.checked;
    
    checkboxes.forEach(cb => {
        cb.checked = isChecked;
        const id = parseInt(cb.value);
        if (isChecked && !selectedIds.includes(id)) {
            selectedIds.push(id);
        } else if (!isChecked) {
            selectedIds = selectedIds.filter(sid => sid !== id);
        }
    });
    
    updateBatchActions();
}

function toggleSelect(id) {
    const index = selectedIds.indexOf(id);
    if (index > -1) {
        selectedIds.splice(index, 1);
    } else {
        selectedIds.push(id);
    }
    
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.tag-checkbox');
    selectAll.checked = checkboxes.length > 0 && selectedIds.length === checkboxes.length;
    
    updateBatchActions();
}

function clearSelection() {
    selectedIds = [];
    document.querySelectorAll('.tag-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBatchActions();
}

function updateBatchActions() {
    const batchActions = document.getElementById('batchActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedIds.length > 0) {
        batchActions.style.display = 'flex';
        selectedCount.textContent = selectedIds.length;
    } else {
        batchActions.style.display = 'none';
    }
}

async function batchDeleteTags() {
    if (selectedIds.length === 0) {
        showToast('请先选择要删除的标签', 'error');
        return;
    }

    if (!confirm(`确定要删除选中的 ${selectedIds.length} 个标签吗？此操作不可撤销。`)) {
        return;
    }

    const result = await apiRequest('tags/batch_delete', 'POST', {
        ids: selectedIds
    });

    if (result.code === 200) {
        showToast(result.message, 'success');
        clearSelection();
        loadTags();
    } else {
        showToast(result.message, 'error');
    }
}

async function openMergeModal() {
    if (selectedIds.length < 1) {
        showToast('请先选择要合并的标签', 'error');
        return;
    }

    const selectedTags = allTags.filter(t => selectedIds.includes(t.id));
    const previewContainer = document.getElementById('mergeSourceTags');
    previewContainer.innerHTML = selectedTags.map(tag => `
        <span class="tag-badge" style="background-color: ${tag.color}20; color: ${tag.color};">
            ${escapeHtml(tag.name)} (${tag.reference_count})
        </span>
    `).join('');

    const targetSelect = document.getElementById('mergeTargetId');
    const allTagsResult = await apiRequest('tags/list?per_page=1000', 'GET');
    
    if (allTagsResult.code === 200) {
        const availableTags = allTagsResult.data.list.filter(t => !selectedIds.includes(t.id));
        targetSelect.innerHTML = '<option value="">请选择目标标签</option>' + 
            availableTags.map(tag => `
                <option value="${tag.id}">${escapeHtml(tag.name)} (${tag.reference_count})</option>
            `).join('');
    }

    document.getElementById('mergeModal').classList.add('show');
}

function closeMergeModal() {
    document.getElementById('mergeModal').classList.remove('show');
}

async function confirmMerge() {
    const targetId = parseInt(document.getElementById('mergeTargetId').value);
    
    if (!targetId) {
        showToast('请选择目标标签', 'error');
        return;
    }

    if (selectedIds.length === 0) {
        showToast('没有要合并的标签', 'error');
        return;
    }

    if (!confirm(`确定要将选中的 ${selectedIds.length} 个标签合并到目标标签吗？此操作不可撤销。`)) {
        return;
    }

    let mergeCount = 0;
    for (const sourceId of selectedIds) {
        if (sourceId === targetId) continue;
        
        const result = await apiRequest('tags/merge', 'POST', {
            source_id: sourceId,
            target_id: targetId
        });

        if (result.code === 200) {
            mergeCount++;
        } else {
            showToast(`合并标签ID ${sourceId} 失败: ${result.message}`, 'error');
        }
    }

    if (mergeCount > 0) {
        showToast(`成功合并 ${mergeCount} 个标签`, 'success');
        closeMergeModal();
        clearSelection();
        loadTags();
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
