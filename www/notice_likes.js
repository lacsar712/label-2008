let currentNoticeId = null;
let currentNoticeTitle = '';
let currentPage = 1;
let pageSize = 20;
let sortBy = 'created_at';
let sortOrder = 'desc';
let currentClientType = '';
let trendChart = null;
let searchTimeout = null;

const clientTypeLabels = {
    desktop: '桌面端',
    mobile: '移动端',
    tablet: '平板',
    other: '其他'
};

document.addEventListener('DOMContentLoaded', function() {
    initNoticeSearch();
    initOutsideClick();
    loadCurrentUserPermissions().then(initPermissionBasedUI);
});

function initNoticeSearch() {
    const input = document.getElementById('noticeSearchInput');
    const dropdown = document.getElementById('noticeDropdown');

    input.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(searchTimeout);

        if (query.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            searchNotices(query);
        }, 300);
    });

    input.addEventListener('focus', function() {
        if (this.value.trim().length > 0) {
            dropdown.style.display = 'block';
        }
    });
}

function initOutsideClick() {
    document.addEventListener('click', function(e) {
        const wrapper = document.querySelector('.search-select-wrapper');
        const dropdown = document.getElementById('noticeDropdown');
        if (!wrapper.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

async function searchNotices(query) {
    try {
        const result = await apiRequest(`notices/search?q=${encodeURIComponent(query)}&limit=10`, 'GET');
        if (result.code === 200 && result.data) {
            renderNoticeDropdown(result.data.list || []);
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

function renderNoticeDropdown(notices) {
    const dropdown = document.getElementById('noticeDropdown');

    if (notices.length === 0) {
        dropdown.innerHTML = '<div class="search-no-result">未找到相关公告</div>';
        dropdown.style.display = 'block';
        return;
    }

    dropdown.innerHTML = notices.map(notice => `
        <div class="search-option" onclick="selectNotice(${notice.id}, '${escapeHtml(notice.title)}', '${escapeHtml(notice.author)}', '${notice.publish_date}')">
            <div class="search-option-title">${escapeHtml(notice.title)}</div>
            <div class="search-option-meta">${escapeHtml(notice.author)} · ${formatDate(notice.publish_date)}</div>
        </div>
    `).join('');
    dropdown.style.display = 'block';
}

function selectNotice(id, title, author, publishDate) {
    currentNoticeId = id;
    currentNoticeTitle = title;

    document.getElementById('noticeSearchInput').value = title;
    document.getElementById('noticeDropdown').style.display = 'none';

    const selectedNotice = document.getElementById('selectedNotice');
    selectedNotice.style.display = 'block';
    document.getElementById('selectedNoticeTitle').textContent = title;
    document.getElementById('selectedNoticeMeta').textContent = `${author} · ${formatDate(publishDate)}`;

    document.getElementById('exportBtn').disabled = false;
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('statsOverview').style.display = 'grid';
    document.getElementById('trendSection').style.display = 'block';
    document.getElementById('likesTableSection').style.display = 'block';

    loadAllLikeData();
}

async function loadAllLikeData() {
    if (!currentNoticeId) return;

    const requests = [
        apiRequest(`notice_likes/stats?notice_id=${currentNoticeId}`, 'GET'),
        apiRequest(`notice_likes/trend?notice_id=${currentNoticeId}&days=30`, 'GET'),
        loadLikesList(1)
    ];

    try {
        const results = await Promise.all(requests);

        if (results[0].code === 200) {
            updateStats(results[0].data);
        }
        if (results[1].code === 200) {
            updateTrendChart(results[1].data);
        }
    } catch (error) {
        console.error('Failed to load data:', error);
        showToast('数据加载失败，请稍后重试', 'error');
    }
}

function updateStats(data) {
    document.getElementById('totalCount').textContent = formatNumber(data.total_count);
    document.getElementById('todayCount').textContent = formatNumber(data.today_count);
    document.getElementById('totalUv').textContent = formatNumber(data.total_uv);

    const changeEl = document.getElementById('todayChange');
    const icon = changeEl.querySelector('.change-icon');
    const value = changeEl.querySelector('.change-value');
    const growth = data.growth_rate;

    if (growth > 0) {
        changeEl.classList.remove('change-negative');
        changeEl.classList.add('change-positive');
        icon.textContent = '↑';
    } else if (growth < 0) {
        changeEl.classList.remove('change-positive');
        changeEl.classList.add('change-negative');
        icon.textContent = '↓';
    } else {
        changeEl.classList.remove('change-positive', 'change-negative');
        icon.textContent = '—';
    }
    value.textContent = Math.abs(growth) + '%';
}

function updateTrendChart(data) {
    const ctx = document.getElementById('trendChart').getContext('2d');

    if (trendChart) {
        trendChart.destroy();
    }

    const labels = data.map(item => item.date.substring(5));
    const countData = data.map(item => item.count);

    trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '点赞数',
                data: countData,
                borderColor: 'rgba(99, 102, 241, 1)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 10
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

async function loadLikesList(page) {
    if (!currentNoticeId) return;

    currentPage = page;
    currentClientType = document.getElementById('clientTypeFilter').value;

    const params = new URLSearchParams({
        notice_id: currentNoticeId,
        page: page,
        page_size: pageSize,
        sort_by: sortBy,
        sort_order: sortOrder
    });

    if (currentClientType) {
        params.append('client_type', currentClientType);
    }

    try {
        const result = await apiRequest(`notice_likes/list?${params.toString()}`, 'GET');
        if (result.code === 200) {
            renderLikesTable(result.data);
            renderPagination(result.data);
        }
    } catch (error) {
        console.error('Failed to load likes list:', error);
    }
}

function renderLikesTable(data) {
    const tbody = document.querySelector('#likesTable tbody');

    if (data.list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data-cell">暂无点赞数据</td></tr>';
        return;
    }

    tbody.innerHTML = data.list.map(item => `
        <tr>
            <td>${item.id}</td>
            <td>${escapeHtml(item.nickname || '匿名')}</td>
            <td><code class="visitor-id">${escapeHtml(item.visitor_id)}</code></td>
            <td>${escapeHtml(item.ip || '-')}</td>
            <td>
                <span class="client-type-badge client-${item.client_type || 'other'}">
                    ${clientTypeLabels[item.client_type] || '其他'}
                </span>
            </td>
            <td>${formatDateTime(item.created_at)}</td>
        </tr>
    `).join('');
}

function renderPagination(data) {
    const pagination = document.getElementById('likesPagination');
    const info = document.getElementById('paginationInfo');
    const numbers = document.getElementById('pageNumbers');
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');

    if (data.total === 0) {
        pagination.style.display = 'none';
        return;
    }

    pagination.style.display = 'flex';
    info.textContent = `共 ${data.total} 条 · 第 ${data.page}/${data.total_pages} 页`;

    prevBtn.disabled = data.page <= 1;
    nextBtn.disabled = data.page >= data.total_pages;

    let pages = [];
    const totalPages = data.total_pages;
    const current = data.page;

    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) {
            pages.push(i);
        }
    } else {
        if (current <= 4) {
            pages = [1, 2, 3, 4, 5, '...', totalPages];
        } else if (current >= totalPages - 3) {
            pages = [1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
        } else {
            pages = [1, '...', current - 1, current, current + 1, '...', totalPages];
        }
    }

    numbers.innerHTML = pages.map(p => {
        if (p === '...') {
            return '<span class="page-ellipsis">...</span>';
        }
        return `<button class="page-num ${p === current ? 'active' : ''}" onclick="loadLikesList(${p})">${p}</button>`;
    }).join('');
}

function prevPage() {
    if (currentPage > 1) {
        loadLikesList(currentPage - 1);
    }
}

function nextPage() {
    loadLikesList(currentPage + 1);
}

function toggleSort(field) {
    const headers = document.querySelectorAll('.sortable-header');

    if (sortBy === field) {
        sortOrder = sortOrder === 'desc' ? 'asc' : 'desc';
    } else {
        sortBy = field;
        sortOrder = 'desc';
    }

    headers.forEach(header => {
        const icon = header.querySelector('.sort-icon');
        if (header.dataset.sort === field) {
            header.classList.add('active-sort');
            icon.textContent = sortOrder === 'desc' ? '↓' : '↑';
        } else {
            header.classList.remove('active-sort');
            icon.textContent = '↕';
        }
    });

    loadLikesList(1);
}

function exportLikes() {
    if (!currentNoticeId) return;

    const params = new URLSearchParams({
        notice_id: currentNoticeId
    });

    if (currentClientType) {
        params.append('client_type', currentClientType);
    }

    const url = `${API_BASE}/notice_likes/export.php?${params.toString()}`;

    const link = document.createElement('a');
    link.href = url;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showToast('导出已开始', 'success');
}

function formatNumber(num) {
    if (num >= 10000) {
        return (num / 10000).toFixed(1) + '万';
    }
    return num.toLocaleString();
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function formatDateTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
