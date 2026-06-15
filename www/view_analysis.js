let currentDays = 7;
let currentStartDate = null;
let currentEndDate = null;
let trendChart = null;
let timeDistributionChart = null;
let regionDistributionChart = null;

const chartColors = {
    pv: 'rgba(99, 102, 241, 1)',
    pvBg: 'rgba(99, 102, 241, 0.1)',
    uv: 'rgba(16, 185, 129, 1)',
    uvBg: 'rgba(16, 185, 129, 0.1)',
    pie: [
        'rgba(99, 102, 241, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(245, 158, 11, 0.8)',
        'rgba(239, 68, 68, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(236, 72, 153, 0.8)',
        'rgba(14, 165, 233, 0.8)'
    ],
    bar: [
        'rgba(99, 102, 241, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(245, 158, 11, 0.8)',
        'rgba(239, 68, 68, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(236, 72, 153, 0.8)',
        'rgba(14, 165, 233, 0.8)',
        'rgba(168, 85, 247, 0.8)',
        'rgba(34, 197, 94, 0.8)',
        'rgba(251, 146, 60, 0.8)'
    ]
};

document.addEventListener('DOMContentLoaded', function() {
    initTimeRangeSelector();
    loadAllData();
    initDateInputs();
});

function initTimeRangeSelector() {
    document.querySelectorAll('.time-range-btn[data-days]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.time-range-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentDays = parseInt(this.dataset.days);
            currentStartDate = null;
            currentEndDate = null;
            document.getElementById('customDateRange').style.display = 'none';
            loadAllData();
        });
    });

    document.getElementById('customRangeBtn').addEventListener('click', function() {
        document.querySelectorAll('.time-range-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('customDateRange').style.display = 'flex';
    });

    document.getElementById('applyCustomRange').addEventListener('click', function() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            showToast('请选择开始和结束日期', 'error');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            showToast('开始日期不能晚于结束日期', 'error');
            return;
        }

        const diffDays = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24));
        if (diffDays > 365) {
            showToast('日期间隔不能超过365天', 'error');
            return;
        }

        currentStartDate = startDate;
        currentEndDate = endDate;
        currentDays = null;
        loadAllData();
    });

    document.getElementById('cancelCustomRange').addEventListener('click', function() {
        document.getElementById('customDateRange').style.display = 'none';
        document.querySelectorAll('.time-range-btn[data-days]').forEach((btn, index) => {
            btn.classList.toggle('active', index === 0);
        });
        document.getElementById('customRangeBtn').classList.remove('active');
        currentDays = 7;
        currentStartDate = null;
        currentEndDate = null;
        loadAllData();
    });
}

function initDateInputs() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('endDate').value = today;
    
    const defaultStart = new Date();
    defaultStart.setDate(defaultStart.getDate() - 30);
    document.getElementById('startDate').value = defaultStart.toISOString().split('T')[0];
    
    document.getElementById('startDate').max = today;
    document.getElementById('endDate').max = today;
}

function getTrendParams() {
    if (currentStartDate && currentEndDate) {
        return `start_date=${currentStartDate}&end_date=${currentEndDate}`;
    }
    return `days=${currentDays || 30}`;
}

async function loadAllData() {
    showLoadingState();

    const days = currentDays || 30;

    const requests = [
        apiRequest('view_analysis/today_stats', 'GET'),
        apiRequest(`view_analysis/period_stats?days=${days}`, 'GET'),
        apiRequest(`view_analysis/top_notices?days=${days}&limit=10`, 'GET'),
        apiRequest(`view_analysis/time_distribution?days=${days}`, 'GET'),
        apiRequest(`view_analysis/region_distribution?days=${days}&limit=10`, 'GET'),
        apiRequest(`view_analysis/trend?${getTrendParams()}`, 'GET')
    ];

    try {
        const results = await Promise.all(requests);

        if (results[0].code === 200) {
            updateTodayStats(results[0].data);
        }
        if (results[1].code === 200) {
            updatePeriodStats(results[1].data);
        }
        if (results[2].code === 200) {
            updateTopNotices(results[2].data);
        }
        if (results[3].code === 200) {
            updateTimeDistribution(results[3].data);
        }
        if (results[4].code === 200) {
            updateRegionDistribution(results[4].data);
        }
        if (results[5].code === 200) {
            updateTrendChart(results[5].data);
        }
    } catch (error) {
        console.error('Failed to load data:', error);
        showToast('数据加载失败，请稍后重试', 'error');
    }
}

function showLoadingState() {
    document.getElementById('topNoticesTable').querySelector('tbody').innerHTML = `
        <tr><td colspan="4" class="loading-cell">加载中...</td></tr>
    `;
}

function updateTodayStats(data) {
    document.getElementById('todayPv').textContent = formatNumber(data.today_pv);
    document.getElementById('todayUv').textContent = formatNumber(data.today_uv);

    updateChangeBadge('todayPvChange', data.pv_change);
    updateChangeBadge('todayUvChange', data.uv_change);
}

function updatePeriodStats(data) {
    document.getElementById('periodPv').textContent = formatNumber(data.pv);
    document.getElementById('periodUv').textContent = formatNumber(data.uv);

    updateChangeBadge('periodPvChange', data.pv_change);
    updateChangeBadge('periodUvChange', data.uv_change);
}

function updateChangeBadge(elementId, change) {
    const element = document.getElementById(elementId);
    const icon = element.querySelector('.change-icon');
    const value = element.querySelector('.change-value');

    if (change > 0) {
        element.classList.remove('change-negative');
        element.classList.add('change-positive');
        icon.textContent = '↑';
    } else if (change < 0) {
        element.classList.remove('change-positive');
        element.classList.add('change-negative');
        icon.textContent = '↓';
        change = Math.abs(change);
    } else {
        element.classList.remove('change-positive', 'change-negative');
        icon.textContent = '—';
    }

    value.textContent = change + '%';
}

function updateTopNotices(data) {
    const tbody = document.getElementById('topNoticesTable').querySelector('tbody');

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="no-data-cell">暂无数据</td></tr>';
        return;
    }

    tbody.innerHTML = data.map((notice, index) => {
        const rankClass = index < 3 ? `rank-${index + 1}` : '';
        return `
            <tr class="clickable-row" onclick="viewNoticeDetail(${notice.id})">
                <td><span class="rank-badge ${rankClass}">${index + 1}</span></td>
                <td class="notice-title-cell" title="${escapeHtml(notice.title)}">
                    ${escapeHtml(notice.title)}
                </td>
                <td>${escapeHtml(notice.author)}</td>
                <td><strong>${formatNumber(notice.view_count)}</strong></td>
            </tr>
        `;
    }).join('');
}

function viewNoticeDetail(id) {
    window.open(`add_notice.php?id=${id}`, '_blank');
}

function updateTrendChart(data) {
    const ctx = document.getElementById('trendChart').getContext('2d');

    if (trendChart) {
        trendChart.destroy();
    }

    const labels = data.map(item => item.date.substring(5));
    const pvData = data.map(item => item.pv);
    const uvData = data.map(item => item.uv);

    trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'PV',
                    data: pvData,
                    borderColor: chartColors.pv,
                    backgroundColor: chartColors.pvBg,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 6
                },
                {
                    label: 'UV',
                    data: uvData,
                    borderColor: chartColors.uv,
                    backgroundColor: chartColors.uvBg,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 6
                }
            ]
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
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
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
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
}

function updateTimeDistribution(data) {
    const ctx = document.getElementById('timeDistributionChart').getContext('2d');

    if (timeDistributionChart) {
        timeDistributionChart.destroy();
    }

    const distribution = data.distribution || [];

    if (distribution.length === 0) {
        return;
    }

    const labels = distribution.map(item => item.time_slot);
    const values = distribution.map(item => item.count);

    timeDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: chartColors.pie.slice(0, labels.length),
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            const item = distribution[context.dataIndex];
                            return `${item.time_slot}: ${item.count} (${item.percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function updateRegionDistribution(data) {
    const ctx = document.getElementById('regionDistributionChart').getContext('2d');

    if (regionDistributionChart) {
        regionDistributionChart.destroy();
    }

    const distribution = data.distribution || [];

    if (distribution.length === 0) {
        return;
    }

    const labels = distribution.map(item => item.region);
    const values = distribution.map(item => item.count);

    regionDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '访问量',
                data: values,
                backgroundColor: chartColors.bar.slice(0, labels.length),
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            const item = distribution[context.dataIndex];
                            return `${item.count} (${item.percentage}%)`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function formatNumber(num) {
    if (num >= 10000) {
        return (num / 10000).toFixed(1) + '万';
    }
    return num.toLocaleString();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
