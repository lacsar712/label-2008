let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let selectedIds = [];
let allCategories = [];
let dragSrcEl = null;

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();

    document.getElementById('categoryForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveCategory();
    });

    document.getElementById('categoryKeyword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadCategories();
        }
    });
});

async function loadCategories() {
    const keyword = document.getElementById('categoryKeyword').value;
    const status = document.getElementById('categoryStatus').value;

    let url = `categories/list?page=${currentPage}&per_page=${perPage}`;
    if (keyword) url += `&keyword=${encodeURIComponent(keyword)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;

    const result = await apiRequest(url, 'GET');
    if (result.code === 200) {
        allCategories = result.data.list;
        renderCategories(allCategories);
        renderPagination(result.data.pagination);
        totalPages = result.data.pagination.total_pages;
    } else {
        showToast(result.message, 'error');
    }
}

function renderCategories(categories) {
    const tbody = document.getElementById('categoryTableBody');
    
    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="no-data">暂无分类数据</td></tr>';
        return;
    }

    tbody.innerHTML = categories.map(cat => `
        <tr class="category-row" draggable="true" data-id="${cat.id}">
            <td>
                <input type="checkbox" class="category-checkbox" value="${cat.id}" 
                    ${selectedIds.includes(cat.id) ? 'checked' : ''} 
                    onchange="toggleSelect(${cat.id})">
            </td>
            <td>
                <span class="drag-handle" title="拖拽排序">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                        <circle cx="9" cy="6" r="1.5" fill="currentColor"/>
                        <circle cx="9" cy="12" r="1.5" fill="currentColor"/>
                        <circle cx="9" cy="18" r="1.5" fill="currentColor"/>
                        <circle cx="15" cy="6" r="1.5" fill="currentColor"/>
                        <circle cx="15" cy="12" r="1.5" fill="currentColor"/>
                        <circle cx="15" cy="18" r="1.5" fill="currentColor"/>
                    </svg>
                </span>
                <span class="sort-order">${cat.sort_order}</span>
            </td>
            <td>
                <span class="category-emoji" style="color: ${cat.color};">
                    ${cat.emoji || '📁'}
                </span>
            </td>
            <td class="category-name-cell">${escapeHtml(cat.name)}</td>
            <td>${escapeHtml(cat.description || '-')}</td>
            <td>
                <span class="color-preview" style="background-color: ${cat.color};"></span>
                <code>${cat.color}</code>
            </td>
            <td>
                <span class="status-badge ${cat.status}">
                    ${cat.status === 'enabled' ? '启用' : '禁用'}
                </span>
            </td>
            <td>${cat.created_at}</td>
            <td class="action-buttons">
                <button class="btn-icon-action edit" title="编辑" onclick="editCategory(${cat.id})">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button class="btn-icon-action delete" title="删除" onclick="deleteCategory(${cat.id})">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');

    initDragAndDrop();
    updateBatchActions();
}

function initDragAndDrop() {
    const rows = document.querySelectorAll('.category-row');
    rows.forEach(row => {
        row.addEventListener('dragstart', handleDragStart);
        row.addEventListener('dragend', handleDragEnd);
        row.addEventListener('dragover', handleDragOver);
        row.addEventListener('drop', handleDrop);
        row.addEventListener('dragenter', handleDragEnter);
        row.addEventListener('dragleave', handleDragLeave);
    });
}

function handleDragStart(e) {
    dragSrcEl = this;
    this.style.opacity = '0.4';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.outerHTML);
}

function handleDragEnd(e) {
    this.style.opacity = '1';
    document.querySelectorAll('.category-row').forEach(row => {
        row.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDragEnter(e) {
    this.classList.add('drag-over');
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

async function handleDrop(e) {
    e.preventDefault();
    if (dragSrcEl !== this) {
        const allRows = Array.from(document.querySelectorAll('.category-row'));
        const srcIndex = allRows.indexOf(dragSrcEl);
        const targetIndex = allRows.indexOf(this);

        const tbody = document.getElementById('categoryTableBody');
        const rows = Array.from(tbody.children);
        
        if (srcIndex < targetIndex) {
            this.parentNode.insertBefore(dragSrcEl, this.nextSibling);
        } else {
            this.parentNode.insertBefore(dragSrcEl, this);
        }

        const newSortedIds = Array.from(document.querySelectorAll('.category-row'))
            .map(row => parseInt(row.dataset.id));
        
        await saveSortOrder(newSortedIds);
    }
    return false;
}

async function saveSortOrder(ids) {
    const result = await apiRequest('categories/sort', 'POST', { sorted_ids: ids });
    if (result.code === 200) {
        showToast('排序已保存', 'success');
        loadCategories();
    } else {
        showToast(result.message, 'error');
    }
}

function renderPagination(pagination) {
    const container = document.getElementById('categoryPagination');
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
    loadCategories();
}

function resetCategorySearch() {
    document.getElementById('categoryKeyword').value = '';
    document.getElementById('categoryStatus').value = '';
    currentPage = 1;
    loadCategories();
}

function openCategoryModal() {
    document.getElementById('modalTitle').textContent = '新增分类';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryColor').value = '#6366f1';
    document.getElementById('categoryFormStatus').value = 'enabled';
    document.getElementById('categoryModal').classList.add('show');
}

async function editCategory(id) {
    const result = await apiRequest(`categories/detail?id=${id}`, 'GET');
    if (result.code === 200) {
        const cat = result.data;
        document.getElementById('modalTitle').textContent = '编辑分类';
        document.getElementById('categoryId').value = cat.id;
        document.getElementById('categoryName').value = cat.name;
        document.getElementById('categoryEmoji').value = cat.emoji || '';
        document.getElementById('categoryColor').value = cat.color || '#6366f1';
        document.getElementById('categoryDescription').value = cat.description || '';
        document.getElementById('categoryFormStatus').value = cat.status;
        document.getElementById('categoryModal').classList.add('show');
    } else {
        showToast(result.message, 'error');
    }
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('show');
}

async function saveCategory() {
    const id = document.getElementById('categoryId').value;
    const data = {
        name: document.getElementById('categoryName').value,
        emoji: document.getElementById('categoryEmoji').value,
        color: document.getElementById('categoryColor').value,
        description: document.getElementById('categoryDescription').value,
        status: document.getElementById('categoryFormStatus').value
    };

    const endpoint = id ? 'categories/update' : 'categories/create';
    if (id) data.id = parseInt(id);

    const result = await apiRequest(endpoint, 'POST', data);
    if (result.code === 200) {
        showToast(id ? '更新成功' : '创建成功', 'success');
        closeCategoryModal();
        loadCategories();
    } else {
        showToast(result.message, 'error');
    }
}

async function deleteCategory(id) {
    if (!confirm('确定要删除这个分类吗？删除后已关联的公告分类将被清空。')) {
        return;
    }

    const result = await apiRequest('categories/delete', 'POST', { id: id });
    if (result.code === 200) {
        showToast('删除成功', 'success');
        loadCategories();
    } else {
        showToast(result.message, 'error');
    }
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.category-checkbox');
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
    const checkboxes = document.querySelectorAll('.category-checkbox');
    selectAll.checked = checkboxes.length > 0 && selectedIds.length === checkboxes.length;
    
    updateBatchActions();
}

function clearSelection() {
    selectedIds = [];
    document.querySelectorAll('.category-checkbox').forEach(cb => cb.checked = false);
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

async function batchUpdateStatus(status) {
    if (selectedIds.length === 0) {
        showToast('请先选择要操作的分类', 'error');
        return;
    }

    const result = await apiRequest('categories/batch_status', 'POST', {
        ids: selectedIds,
        status: status
    });

    if (result.code === 200) {
        showToast(result.message, 'success');
        clearSelection();
        loadCategories();
    } else {
        showToast(result.message, 'error');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}