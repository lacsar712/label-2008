let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let selectedIds = [];
let allBanners = [];
let dragSrcEl = null;
let previewCurrentIndex = 0;
let previewBanners = [];
let previewTimer = null;

document.addEventListener('DOMContentLoaded', function() {
    loadBanners();

    document.getElementById('bannerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveBanner();
    });

    document.getElementById('bannerKeyword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadBanners();
        }
    });

    document.getElementById('bannerImageInput').addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;
        await uploadBannerImage(file);
        e.target.value = '';
    });
});

async function loadBanners() {
    const keyword = document.getElementById('bannerKeyword').value;
    const status = document.getElementById('bannerStatus').value;

    let url = `banners/list?page=${currentPage}&per_page=${perPage}`;
    if (keyword) url += `&keyword=${encodeURIComponent(keyword)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;

    const result = await apiRequest(url, 'GET');
    if (result.code === 200) {
        allBanners = result.data.list;
        renderBanners(allBanners);
        renderPagination(result.data.pagination);
        totalPages = result.data.pagination.total_pages;
    } else {
        showToast(result.message, 'error');
    }
}

function renderBanners(banners) {
    const tbody = document.getElementById('bannerTableBody');
    
    if (banners.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="no-data">暂无Banner数据</td></tr>';
        return;
    }

    tbody.innerHTML = banners.map(banner => `
        <tr class="banner-row" draggable="true" data-id="${banner.id}">
            <td>
                <input type="checkbox" class="banner-checkbox" value="${banner.id}" 
                    ${selectedIds.includes(banner.id) ? 'checked' : ''} 
                    onchange="toggleSelect(${banner.id})">
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
                <span class="sort-order">${banner.sort_order}</span>
            </td>
            <td>
                <div class="banner-thumbnail" onclick="previewImage('${escapeHtml(banner.image_url)}')">
                    <img src="${escapeHtml(banner.image_url)}" alt="${escapeHtml(banner.title || 'Banner')}">
                </div>
            </td>
            <td class="banner-title-cell">${escapeHtml(banner.title || '-')}</td>
            <td>${escapeHtml(banner.subtitle || '-')}</td>
            <td>
                ${formatEffectiveTime(banner.start_time, banner.end_time)}
            </td>
            <td>
                <label class="toggle-switch">
                    <input type="checkbox" ${banner.status === 'enabled' ? 'checked' : ''} onchange="toggleBannerStatus(${banner.id}, '${banner.status}')">
                    <span class="toggle-slider"></span>
                </label>
            </td>
            <td>${banner.created_at}</td>
            <td class="action-buttons">
                <button class="btn-icon-action edit" title="编辑" onclick="editBanner(${banner.id})">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button class="btn-icon-action delete" title="删除" onclick="deleteBanner(${banner.id})">
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

function formatEffectiveTime(start, end) {
    if (!start && !end) return '<span class="text-muted">永久有效</span>';
    let html = '';
    if (start) {
        html += `<div><small class="text-muted">开始:</small> ${start.substring(0, 16)}</div>`;
    }
    if (end) {
        html += `<div><small class="text-muted">结束:</small> ${end.substring(0, 16)}</div>`;
    }
    return html;
}

function previewImage(url) {
    const modal = document.createElement('div');
    modal.className = 'image-preview-modal';
    modal.onclick = () => modal.remove();
    modal.innerHTML = `
        <div class="image-preview-content" onclick="event.stopPropagation()">
            <img src="${escapeHtml(url)}" alt="Preview">
            <button class="image-preview-close" onclick="this.closest('.image-preview-modal').remove()">&times;</button>
        </div>
    `;
    document.body.appendChild(modal);
}

function initDragAndDrop() {
    const rows = document.querySelectorAll('.banner-row');
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
    document.querySelectorAll('.banner-row').forEach(row => {
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
        const allRows = Array.from(document.querySelectorAll('.banner-row'));
        const srcIndex = allRows.indexOf(dragSrcEl);
        const targetIndex = allRows.indexOf(this);

        const tbody = document.getElementById('bannerTableBody');
        const rows = Array.from(tbody.children);
        
        if (srcIndex < targetIndex) {
            this.parentNode.insertBefore(dragSrcEl, this.nextSibling);
        } else {
            this.parentNode.insertBefore(dragSrcEl, this);
        }

        const newSortedIds = Array.from(document.querySelectorAll('.banner-row'))
            .map(row => parseInt(row.dataset.id));
        
        await saveSortOrder(newSortedIds);
    }
    return false;
}

async function saveSortOrder(ids) {
    const result = await apiRequest('banners/sort', 'POST', { sorted_ids: ids });
    if (result.code === 200) {
        showToast('排序已保存', 'success');
        loadBanners();
    } else {
        showToast(result.message, 'error');
    }
}

function renderPagination(pagination) {
    const container = document.getElementById('bannerPagination');
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
    loadBanners();
}

function resetBannerSearch() {
    document.getElementById('bannerKeyword').value = '';
    document.getElementById('bannerStatus').value = '';
    currentPage = 1;
    loadBanners();
}

function openBannerModal() {
    document.getElementById('modalTitle').textContent = '新增Banner';
    document.getElementById('bannerId').value = '';
    document.getElementById('bannerForm').reset();
    document.getElementById('bannerImageUrl').value = '';
    document.getElementById('bannerFormStatus').value = 'enabled';
    document.getElementById('bannerImagePreview').style.display = 'none';
    document.getElementById('bannerUploadPlaceholder').style.display = 'flex';
    document.getElementById('bannerModal').classList.add('show');
}

async function editBanner(id) {
    const result = await apiRequest(`banners/detail?id=${id}`, 'GET');
    if (result.code === 200) {
        const banner = result.data;
        document.getElementById('modalTitle').textContent = '编辑Banner';
        document.getElementById('bannerId').value = banner.id;
        document.getElementById('bannerTitle').value = banner.title || '';
        document.getElementById('bannerSubtitle').value = banner.subtitle || '';
        document.getElementById('bannerLinkUrl').value = banner.link_url || '';
        document.getElementById('bannerStartTime').value = banner.start_time ? banner.start_time.substring(0, 16) : '';
        document.getElementById('bannerEndTime').value = banner.end_time ? banner.end_time.substring(0, 16) : '';
        document.getElementById('bannerFormStatus').value = banner.status;
        document.getElementById('bannerImageUrl').value = banner.image_url;
        
        if (banner.image_url) {
            document.getElementById('bannerImagePreview').src = banner.image_url;
            document.getElementById('bannerImagePreview').style.display = 'block';
            document.getElementById('bannerUploadPlaceholder').style.display = 'none';
        }
        
        document.getElementById('bannerModal').classList.add('show');
    } else {
        showToast(result.message, 'error');
    }
}

function closeBannerModal() {
    document.getElementById('bannerModal').classList.remove('show');
}

async function uploadBannerImage(file) {
    const formData = new FormData();
    formData.append('image', file);

    const uploadArea = document.getElementById('bannerUploadArea');
    const placeholder = document.getElementById('bannerUploadPlaceholder');
    const originalContent = placeholder.innerHTML;
    placeholder.innerHTML = '<div class="spinner" style="margin-bottom: 8px;"></div><p>上传中...</p>';

    try {
        const result = await apiRequest('banners/upload_image', 'POST', formData, true);
        
        if (result.code === 200) {
            document.getElementById('bannerImageUrl').value = result.data.image_url;
            document.getElementById('bannerImagePreview').src = result.data.image_url;
            document.getElementById('bannerImagePreview').style.display = 'block';
            placeholder.style.display = 'none';
            showToast('图片上传成功', 'success');
        } else {
            placeholder.innerHTML = originalContent;
            showToast(result.message, 'error');
        }
    } catch (error) {
        placeholder.innerHTML = originalContent;
        showToast('上传失败，请稍后重试', 'error');
    }
}

async function saveBanner() {
    const id = document.getElementById('bannerId').value;
    const data = {
        image_url: document.getElementById('bannerImageUrl').value,
        title: document.getElementById('bannerTitle').value,
        subtitle: document.getElementById('bannerSubtitle').value,
        link_url: document.getElementById('bannerLinkUrl').value,
        start_time: document.getElementById('bannerStartTime').value || null,
        end_time: document.getElementById('bannerEndTime').value || null,
        status: document.getElementById('bannerFormStatus').value
    };

    if (!data.image_url) {
        showToast('请先上传Banner图片', 'error');
        return;
    }

    const endpoint = id ? 'banners/update' : 'banners/create';
    if (id) data.id = parseInt(id);

    const result = await apiRequest(endpoint, 'POST', data);
    if (result.code === 200) {
        showToast(id ? '更新成功' : '创建成功', 'success');
        closeBannerModal();
        loadBanners();
    } else {
        showToast(result.message, 'error');
    }
}

async function deleteBanner(id) {
    if (!confirm('确定要删除这个Banner吗？')) {
        return;
    }

    const result = await apiRequest('banners/delete', 'POST', { id: id });
    if (result.code === 200) {
        showToast('删除成功', 'success');
        loadBanners();
    } else {
        showToast(result.message, 'error');
    }
}

async function toggleBannerStatus(id, currentStatus) {
    const newStatus = currentStatus === 'enabled' ? 'disabled' : 'enabled';
    const result = await apiRequest('banners/update', 'POST', {
        id: id,
        status: newStatus
    });
    if (result.code !== 200) {
        showToast(result.message, 'error');
        loadBanners();
    }
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.banner-checkbox');
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
    const checkboxes = document.querySelectorAll('.banner-checkbox');
    selectAll.checked = checkboxes.length > 0 && selectedIds.length === checkboxes.length;
    
    updateBatchActions();
}

function clearSelection() {
    selectedIds = [];
    document.querySelectorAll('.banner-checkbox').forEach(cb => cb.checked = false);
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
        showToast('请先选择要操作的Banner', 'error');
        return;
    }

    const result = await apiRequest('banners/batch_status', 'POST', {
        ids: selectedIds,
        status: status
    });

    if (result.code === 200) {
        showToast(result.message, 'success');
        clearSelection();
        loadBanners();
    } else {
        showToast(result.message, 'error');
    }
}

async function openPreviewModal() {
    const result = await apiRequest('banners/active', 'GET');
    if (result.code === 200 && result.data && result.data.length > 0) {
        previewBanners = result.data;
        previewCurrentIndex = 0;
        renderPreviewSlides();
        document.getElementById('previewModal').classList.add('show');
        startPreviewAutoPlay();
    } else {
        showToast('暂无生效中的Banner', 'info');
    }
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.remove('show');
    if (previewTimer) {
        clearInterval(previewTimer);
        previewTimer = null;
    }
}

function renderPreviewSlides() {
    const slidesContainer = document.getElementById('bannerPreviewSlides');
    const dotsContainer = document.getElementById('bannerPreviewDots');

    slidesContainer.innerHTML = previewBanners.map((banner, index) => `
        <div class="banner-preview-slide ${index === 0 ? 'active' : ''}">
            <img src="${escapeHtml(banner.image_url)}" alt="${escapeHtml(banner.title || 'Banner')}">
            ${banner.title || banner.subtitle ? `
                <div class="banner-preview-overlay">
                    ${banner.title ? `<h3>${escapeHtml(banner.title)}</h3>` : ''}
                    ${banner.subtitle ? `<p>${escapeHtml(banner.subtitle)}</p>` : ''}
                </div>
            ` : ''}
        </div>
    `).join('');

    dotsContainer.innerHTML = previewBanners.map((_, index) => `
        <span class="banner-preview-dot ${index === 0 ? 'active' : ''}" onclick="goToPreviewSlide(${index})"></span>
    `).join('');
}

function startPreviewAutoPlay() {
    if (previewTimer) clearInterval(previewTimer);
    previewTimer = setInterval(() => {
        nextPreviewSlide();
    }, 4000);
}

function goToPreviewSlide(index) {
    const slides = document.querySelectorAll('.banner-preview-slide');
    const dots = document.querySelectorAll('.banner-preview-dot');
    
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
    
    previewCurrentIndex = index;
    if (previewTimer) {
        clearInterval(previewTimer);
        startPreviewAutoPlay();
    }
}

function prevPreviewSlide() {
    const newIndex = (previewCurrentIndex - 1 + previewBanners.length) % previewBanners.length;
    goToPreviewSlide(newIndex);
}

function nextPreviewSlide() {
    const newIndex = (previewCurrentIndex + 1) % previewBanners.length;
    goToPreviewSlide(newIndex);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
