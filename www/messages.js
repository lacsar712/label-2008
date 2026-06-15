let currentType = '';
let currentPage = 1;
const perPage = 10;
let selectedIds = [];

document.addEventListener('DOMContentLoaded', function() {
    loadTypeStats();
    loadMessages();
});

async function loadTypeStats() {
    const result = await apiRequest('messages/type_stats', 'GET');
    if (result.code === 200 && result.data) {
        const types = result.data.types || [];
        const total = result.data.total || {};

        document.getElementById('totalCount').textContent = total.unread_count || 0;
        
        types.forEach(function(t) {
            const el = document.getElementById(t.type + 'Count');
            if (el) {
                el.textContent = t.unread_count || 0;
            }
        });

        const knownTypes = ['system', 'notice', 'security', 'activity'];
        knownTypes.forEach(function(type) {
            const found = types.find(function(t) { return t.type === type; });
            const el = document.getElementById(type + 'Count');
            if (el && !found) {
                el.textContent = '0';
            }
        });
    }
}

async function loadMessages() {
    selectedIds = [];
    const selectAllCb = document.getElementById('selectAllMessages');
    if (selectAllCb) {
        selectAllCb.checked = false;
    }

    const listEl = document.getElementById('messagesList');
    const readFilter = document.getElementById('readFilter').value;

    let url = 'messages/list?page=' + currentPage + '&per_page=' + perPage;
    if (currentType) {
        url += '&type=' + currentType;
    }
    if (readFilter !== '') {
        url += '&is_read=' + readFilter;
    }

    listEl.innerHTML = '<div class="message-loading">加载中...</div>';

    const result = await apiRequest(url, 'GET');
    if (result.code === 200 && result.data) {
        const messages = result.data.list || [];
        const pagination = result.data.pagination || {};

        if (messages.length === 0) {
            listEl.innerHTML = '<div class="message-empty-state"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>暂无消息</p></div>';
            document.getElementById('messagePagination').innerHTML = '';
            return;
        }

        listEl.innerHTML = messages.map(function(msg) {
            return '<div class="message-card ' + (msg.is_read == 1 ? 'message-read' : 'message-unread') + '" data-id="' + msg.id + '">' +
                '<div class="message-card-check">' +
                    '<input type="checkbox" class="message-checkbox" value="' + msg.id + '" onchange="updateSelection()">' +
                '</div>' +
                '<div class="message-card-content" onclick="handleMessageClick(' + msg.id + ', ' + msg.is_read + ')">' +
                    '<div class="message-card-header">' +
                        '<span class="message-type-badge message-type-' + escapeHtml(msg.type) + '">' + getTypeLabel(msg.type) + '</span>' +
                        (msg.is_read == 0 ? '<span class="message-unread-dot"></span>' : '') +
                        '<span class="message-card-time">' + formatMessageTime(msg.created_at) + '</span>' +
                    '</div>' +
                    '<div class="message-card-title">' + escapeHtml(msg.title) + '</div>' +
                    (msg.body ? '<div class="message-card-body">' + escapeHtml(msg.body.substring(0, 120) + (msg.body.length > 120 ? '...' : '')) + '</div>' : '') +
                '</div>' +
                '<div class="message-card-actions">' +
                    (msg.is_read == 0 ? '<button class="btn-icon-action read" title="标记已读" onclick="markSingleRead(' + msg.id + ', event)"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>' : '') +
                '</div>' +
            '</div>';
        }).join('');

        renderPagination(pagination);
        updateBatchDeleteBtn();
    } else {
        listEl.innerHTML = '<div class="message-empty-state"><p>加载失败</p></div>';
    }
}

function filterByType(type) {
    currentType = type;
    currentPage = 1;

    document.querySelectorAll('.message-type-item').forEach(function(item) {
        item.classList.remove('active');
        if (item.dataset.type === type) {
            item.classList.add('active');
        }
    });

    loadMessages();
}

async function handleMessageClick(id, isRead) {
    if (isRead == 0) {
        await apiRequest('messages/mark_read', 'POST', { id: id });
    }
    loadMessages();
    loadTypeStats();
    if (typeof updateUnreadCount === 'function') {
        updateUnreadCount();
    }
}

async function markSingleRead(id, event) {
    event.stopPropagation();
    const result = await apiRequest('messages/mark_read', 'POST', { id: id });
    if (result.code === 200) {
        loadMessages();
        loadTypeStats();
        if (typeof updateUnreadCount === 'function') {
            updateUnreadCount();
        }
    }
}

async function markAllRead() {
    const result = await apiRequest('messages/mark_all_read', 'POST');
    if (result.code === 200) {
        showToast('全部标记已读成功', 'success');
        loadMessages();
        loadTypeStats();
        if (typeof updateUnreadCount === 'function') {
            updateUnreadCount();
        }
    } else {
        showToast(result.message, 'error');
    }
}

function updateSelection() {
    selectedIds = [];
    document.querySelectorAll('.message-checkbox:checked').forEach(function(cb) {
        selectedIds.push(parseInt(cb.value));
    });
    updateBatchDeleteBtn();
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.message-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = checkbox.checked;
    });
    updateSelection();
}

function updateBatchDeleteBtn() {
    const btn = document.getElementById('batchDeleteBtn');
    if (selectedIds.length > 0) {
        btn.style.display = 'inline-flex';
    } else {
        btn.style.display = 'none';
    }
}

async function batchDeleteMessages() {
    if (selectedIds.length === 0) {
        showToast('请先选择要删除的消息', 'error');
        return;
    }

    if (!confirm('确定要删除选中的 ' + selectedIds.length + ' 条消息吗？')) {
        return;
    }

    const result = await apiRequest('messages/delete', 'POST', { ids: selectedIds });
    if (result.code === 200) {
        showToast('删除成功', 'success');
        selectedIds = [];
        document.getElementById('selectAllMessages').checked = false;
        loadMessages();
        loadTypeStats();
        if (typeof updateUnreadCount === 'function') {
            updateUnreadCount();
        }
    } else {
        showToast(result.message, 'error');
    }
}

function renderPagination(pagination) {
    const container = document.getElementById('messagePagination');
    if (!pagination || pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    const page = pagination.page;
    const totalPages = pagination.total_pages;
    let html = '';

    if (page > 1) {
        html += '<a href="javascript:void(0)" class="page-link" onclick="goToPage(' + (page - 1) + ')"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> 上一页</a>';
    }

    var startPage = Math.max(1, page - 2);
    var endPage = Math.min(totalPages, page + 2);

    if (startPage > 1) {
        html += '<a href="javascript:void(0)" class="page-number" onclick="goToPage(1)">1</a>';
        if (startPage > 2) html += '<span class="page-ellipsis">...</span>';
    }

    for (var i = startPage; i <= endPage; i++) {
        html += '<a href="javascript:void(0)" class="page-number ' + (i === page ? 'active' : '') + '" onclick="goToPage(' + i + ')">' + i + '</a>';
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span class="page-ellipsis">...</span>';
        html += '<a href="javascript:void(0)" class="page-number" onclick="goToPage(' + totalPages + ')">' + totalPages + '</a>';
    }

    if (page < totalPages) {
        html += '<a href="javascript:void(0)" class="page-link" onclick="goToPage(' + (page + 1) + ')">下一页 <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>';
    }

    container.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadMessages();
}

async function refreshMessages() {
    await Promise.all([
        loadMessages(),
        loadTypeStats()
    ]);
    if (typeof updateUnreadCount === 'function') {
        updateUnreadCount();
    }
    showToast('消息列表已刷新', 'success');
}
