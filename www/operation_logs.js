let currentPage = 1;
let perPage = 20;
let totalPages = 1;
let currentFilters = {};

document.addEventListener('DOMContentLoaded', function() {
    loadFilterOptions();
    loadLogs(1);

    document.getElementById('filterKeyword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadLogs(1);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDrawer();
        }
    });
});

async function loadFilterOptions() {
    const result = await apiRequest('operation_logs/list?page=1&per_page=1', 'GET');
    if (result.code === 200 && result.data.filters) {
        const operationTypeSelect = document.getElementById('filterOperationType');
        const targetTypeSelect = document.getElementById('filterTargetType');

        if (result.data.filters.operation_types) {
            result.data.filters.operation_types.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                operationTypeSelect.appendChild(option);
            });
        }

        if (result.data.filters.target_types) {
            result.data.filters.target_types.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                targetTypeSelect.appendChild(option);
            });
        }
    }
}

async function loadLogs(page) {
    currentPage = page;
    currentFilters = {
        operator: document.getElementById('filterOperator').value,
        operation_type: document.getElementById('filterOperationType').value,
        target_type: document.getElementById('filterTargetType').value,
        keyword: document.getElementById('filterKeyword').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value
    };

    let url = `operation_logs/list?page=${currentPage}&per_page=${perPage}`;
    if (currentFilters.operator) url += `&operator=${encodeURIComponent(currentFilters.operator)}`;
    if (currentFilters.operation_type) url += `&operation_type=${encodeURIComponent(currentFilters.operation_type)}`;
    if (currentFilters.target_type) url += `&target_type=${encodeURIComponent(currentFilters.target_type)}`;
    if (currentFilters.keyword) url += `&keyword=${encodeURIComponent(currentFilters.keyword)}`;
    if (currentFilters.date_from) url += `&date_from=${encodeURIComponent(currentFilters.date_from)}`;
    if (currentFilters.date_to) url += `&date_to=${encodeURIComponent(currentFilters.date_to)}`;

    const result = await apiRequest(url, 'GET');
    if (result.code === 200) {
        renderLogs(result.data.list);
        renderPagination(result.data.pagination);
        totalPages = result.data.pagination.total_pages;
    } else {
        showToast(result.message, 'error');
    }
}

function renderLogs(logs) {
    const tbody = document.getElementById('logsTableBody');

    if (logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5C15 3.89543 14.1046 3 13 3H11C9.89543 3 9 3.89543 9 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>暂无操作日志</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = logs.map(log => {
        const initial = log.user_nickname ? log.user_nickname.charAt(0).toUpperCase() : '?';
        return `
            <tr>
                <td>${log.id}</td>
                <td>
                    <div class="operation-user">
                        <div class="user-avatar-small">${initial}</div>
                        <div>
                            <div>${escapeHtml(log.user_nickname || '系统')}</div>
                            <small style="color: var(--text-muted);">UID: ${log.user_id || '-'}</small>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-${log.operation_type}">${escapeHtml(log.operation_type_label)}</span></td>
                <td><span class="badge badge-${log.target_type}">${escapeHtml(log.target_type_label)}</span></td>
                <td>${escapeHtml(log.target_id || '-')}</td>
                <td>${escapeHtml(log.ip || '-')}</td>
                <td>${escapeHtml(log.created_at)}</td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="viewLogDetail(${log.id})">
                        查看详情
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPagination(pagination) {
    const container = document.getElementById('pagination');

    if (pagination.total_pages <= 1) {
        container.innerHTML = `<div class="pagination-info">共 ${pagination.total} 条记录</div>`;
        return;
    }

    let html = `<div class="pagination-info">共 ${pagination.total} 条记录，第 ${pagination.current_page}/${pagination.total_pages} 页</div>`;

    if (pagination.current_page > 1) {
        html += `<button class="btn btn-sm btn-secondary" onclick="loadLogs(1)">首页</button>`;
        html += `<button class="btn btn-sm btn-secondary" onclick="loadLogs(${pagination.current_page - 1})">上一页</button>`;
    }

    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        const active = i === pagination.current_page ? 'btn-primary' : 'btn-secondary';
        html += `<button class="btn btn-sm ${active}" onclick="loadLogs(${i})">${i}</button>`;
    }

    if (pagination.current_page < pagination.total_pages) {
        html += `<button class="btn btn-sm btn-secondary" onclick="loadLogs(${pagination.current_page + 1})">下一页</button>`;
        html += `<button class="btn btn-sm btn-secondary" onclick="loadLogs(${pagination.total_pages})">末页</button>`;
    }

    container.innerHTML = html;
}

async function viewLogDetail(id) {
    const result = await apiRequest(`operation_logs/detail?id=${id}`, 'GET');
    if (result.code === 200) {
        renderLogDetail(result.data);
        openDrawer();
    } else {
        showToast(result.message, 'error');
    }
}

function renderLogDetail(log) {
    const drawerBody = document.getElementById('drawerBody');

    const initial = log.user_nickname ? log.user_nickname.charAt(0).toUpperCase() : '?';

    let html = `
        <div class="drawer-section">
            <h4>基本信息</h4>
            <div class="log-detail-grid">
                <div class="log-detail-label">日志ID</div>
                <div class="log-detail-value">${log.id}</div>

                <div class="log-detail-label">操作人</div>
                <div class="log-detail-value">
                    <div class="operation-user">
                        <div class="user-avatar-small">${initial}</div>
                        <div>
                            <div>${escapeHtml(log.user_nickname || '系统')}</div>
                            <small style="color: var(--text-muted);">UID: ${log.user_id || '-'}</small>
                        </div>
                    </div>
                </div>

                <div class="log-detail-label">操作类型</div>
                <div class="log-detail-value"><span class="badge badge-${log.operation_type}">${escapeHtml(log.operation_type_label)}</span></div>

                <div class="log-detail-label">目标类型</div>
                <div class="log-detail-value"><span class="badge badge-${log.target_type}">${escapeHtml(log.target_type_label)}</span></div>

                <div class="log-detail-label">目标ID</div>
                <div class="log-detail-value">${escapeHtml(log.target_id || '-')}</div>

                <div class="log-detail-label">IP地址</div>
                <div class="log-detail-value">${escapeHtml(log.ip || '-')}</div>

                <div class="log-detail-label">操作时间</div>
                <div class="log-detail-value">${escapeHtml(log.created_at)}</div>

                <div class="log-detail-label">User-Agent</div>
                <div class="log-detail-value" style="word-break: break-all;">${escapeHtml(log.user_agent || '-')}</div>
            </div>
        </div>
    `;

    if (log.diff && log.diff.length > 0) {
        html += `
            <div class="drawer-section">
                <h4>变更对比</h4>
                <div class="diff-container">
                    <div class="diff-column">
                        <div class="diff-column-header before">变更前 (Before)</div>
                        ${renderDiffColumn(log.diff, 'before')}
                    </div>
                    <div class="diff-column">
                        <div class="diff-column-header after">变更后 (After)</div>
                        ${renderDiffColumn(log.diff, 'after')}
                    </div>
                </div>
            </div>
        `;
    } else if (log.before_data || log.after_data) {
        html += `
            <div class="drawer-section">
                <h4>数据快照</h4>
                <div class="diff-container">
                    <div class="diff-column">
                        <div class="diff-column-header before">变更前 (Before)</div>
                        ${log.before_data ? `<pre style="white-space: pre-wrap; word-break: break-all; font-size: 0.75rem; color: var(--text-secondary);">${escapeHtml(JSON.stringify(log.before_data, null, 2))}</pre>` : '<div class="diff-field empty">无数据</div>'}
                    </div>
                    <div class="diff-column">
                        <div class="diff-column-header after">变更后 (After)</div>
                        ${log.after_data ? `<pre style="white-space: pre-wrap; word-break: break-all; font-size: 0.75rem; color: var(--text-secondary);">${escapeHtml(JSON.stringify(log.after_data, null, 2))}</pre>` : '<div class="diff-field empty">无数据</div>'}
                    </div>
                </div>
            </div>
        `;
    } else {
        html += `
            <div class="drawer-section">
                <div class="no-changes">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.5;">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>此操作无数据变更对比</p>
                </div>
            </div>
        `;
    }

    drawerBody.innerHTML = html;
}

function renderDiffColumn(diff, side) {
    return diff.map(item => {
        const value = side === 'before' ? item.old_value : item.new_value;
        const changed = item.old_value !== item.new_value;
        const cssClass = changed ? (side === 'before' ? 'changed-before' : 'changed-after') : 'unchanged';

        if (value === null || value === undefined) {
            return `
                <div class="diff-field ${cssClass}">
                    <div class="diff-field-label">${escapeHtml(item.field_label)}</div>
                    <div class="empty">无</div>
                </div>
            `;
        }

        let displayValue = value;
        if (typeof value === 'object') {
            displayValue = JSON.stringify(value, null, 2);
        }

        return `
            <div class="diff-field ${cssClass}">
                <div class="diff-field-label">${escapeHtml(item.field_label)}</div>
                <div>${escapeHtml(String(displayValue))}</div>
            </div>
        `;
    }).join('');
}

function openDrawer() {
    document.getElementById('drawerOverlay').classList.add('show');
    document.getElementById('logDrawer').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('show');
    document.getElementById('logDrawer').classList.remove('show');
    document.body.style.overflow = '';
}

function resetFilters() {
    document.getElementById('filterOperator').value = '';
    document.getElementById('filterOperationType').value = '';
    document.getElementById('filterTargetType').value = '';
    document.getElementById('filterKeyword').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    loadLogs(1);
}

function exportCSV() {
    let url = 'operation_logs/export?';
    const params = new URLSearchParams();
    if (currentFilters.operator) params.append('operator', currentFilters.operator);
    if (currentFilters.operation_type) params.append('operation_type', currentFilters.operation_type);
    if (currentFilters.target_type) params.append('target_type', currentFilters.target_type);
    if (currentFilters.keyword) params.append('keyword', currentFilters.keyword);
    if (currentFilters.date_from) params.append('date_from', currentFilters.date_from);
    if (currentFilters.date_to) params.append('date_to', currentFilters.date_to);

    window.location.href = url + params.toString();
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
