const API_BASE = 'api';
let currentUserPermissions = [];
let currentUserRoles = [];
let permissionsLoaded = false;

async function loadCurrentUserPermissions() {
    if (permissionsLoaded) return currentUserPermissions;
    
    try {
        const result = await apiRequest('me/permissions', 'GET');
        if (result.code === 200 && result.data) {
            currentUserPermissions = result.data.permission_names || [];
            currentUserRoles = result.data.role_names || [];
        }
    } catch (e) {
        console.error('Failed to load permissions:', e);
    }
    permissionsLoaded = true;
    return currentUserPermissions;
}

function hasPermission(permission) {
    return currentUserPermissions.includes(permission);
}

function hasAnyPermission(permissions) {
    return permissions.some(p => currentUserPermissions.includes(p));
}

function hasRole(role) {
    return currentUserRoles.includes(role);
}

function initPermissionBasedUI() {
    document.querySelectorAll('[data-permission]').forEach(el => {
        const permission = el.getAttribute('data-permission');
        if (!hasPermission(permission)) {
            el.style.display = 'none';
        }
    });
    
    document.querySelectorAll('[data-any-permission]').forEach(el => {
        const permissions = el.getAttribute('data-any-permission').split(',');
        if (!hasAnyPermission(permissions)) {
            el.style.display = 'none';
        }
    });
    
    document.querySelectorAll('[data-role]').forEach(el => {
        const role = el.getAttribute('data-role');
        if (!hasRole(role)) {
            el.style.display = 'none';
        }
    });
}

async function apiRequest(endpoint, method = 'GET', data = null, isFormData = false) {
    const options = {
        method: method,
        headers: {}
    };

    if (data && !isFormData) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    } else if (data && isFormData) {
        options.body = data;
    }

    try {
        const response = await fetch(`${API_BASE}/${endpoint}`, options);
        const result = await response.json();
        return result;
    } catch (error) {
        return {
            code: 500,
            message: '网络请求失败，请稍后重试'
        };
    }
}

function showToast(message, type = 'error') {
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <svg class="toast-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            ${type === 'success' 
                ? '<path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                : '<path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            }
        </svg>
        <span class="toast-message">${message}</span>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('toast-show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('toast-show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function showFormError(formId, fieldName, message) {
    const form = document.getElementById(formId);
    if (!form) return;

    const field = form.querySelector(`[name="${fieldName}"]`);
    if (!field) return;

    const formGroup = field.closest('.form-group');
    if (!formGroup) return;

    formGroup.classList.add('has-error');
    
    let errorEl = formGroup.querySelector('.field-error');
    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'field-error';
        formGroup.appendChild(errorEl);
    }
    errorEl.textContent = message;
}

function clearFormErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.querySelectorAll('.form-group.has-error').forEach(group => {
        group.classList.remove('has-error');
        const errorEl = group.querySelector('.field-error');
        if (errorEl) errorEl.remove();
    });
}

function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    if (menu) {
        menu.classList.toggle('show');
    }
}

document.addEventListener('click', function(e) {
    const dropdown = document.querySelector('.user-dropdown');
    const menu = document.getElementById('userMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.remove('show');
    }
});

async function logout() {
    const result = await apiRequest('logout', 'POST');
    if (result.code === 200) {
        showToast(result.message, 'success');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 500);
    } else {
        showToast(result.message, 'error');
    }
}

async function getCurrentUser() {
    const result = await apiRequest('me', 'GET');
    if (result.code === 200) {
        return result.data;
    }
    return null;
}

function handleApiError(result, formId) {
    const errorFieldMap = {
        1001: 'username',
        1002: 'username',
        1003: 'username',
        1004: 'email',
        1005: 'email',
        1006: 'email',
        1007: 'password',
        1008: 'password',
        1009: 'confirm_password',
        1010: 'username',
        1011: 'username',
        1012: 'old_password',
        1013: 'new_password',
        1014: 'token',
        1015: 'avatar',
        1016: 'avatar',
        1017: 'avatar'
    };

    if (formId && errorFieldMap[result.code]) {
        showFormError(formId, errorFieldMap[result.code], result.message);
    } else {
        showToast(result.message, 'error');
    }
}

function initAuthForm(formId, endpoint, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        clearFormErrors(formId);

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> 处理中...';

        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        const result = await apiRequest(endpoint, 'POST', data);

        submitBtn.disabled = false;
        submitBtn.textContent = originalText;

        if (result.code === 200) {
            showToast(result.message, 'success');
            if (successCallback) {
                successCallback(result);
            }
        } else {
            handleApiError(result, formId);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadCurrentUserPermissions().then(() => {
        initPermissionBasedUI();
    });
    
    const authorField = document.querySelector('input[name="author"]');
    if (authorField && !authorField.hasAttribute('data-initialized')) {
        authorField.setAttribute('data-initialized', 'true');
        
        getCurrentUser().then(user => {
            if (user && authorField.closest('.notice-form')) {
                const displayName = user.nickname || user.username;
                authorField.value = displayName;
                authorField.disabled = true;
                authorField.classList.add('disabled');
                
                const authorIdInput = document.createElement('input');
                authorIdInput.type = 'hidden';
                authorIdInput.name = 'author_id';
                authorIdInput.value = user.id;
                authorField.closest('form').appendChild(authorIdInput);
            }
        });
    }

    loadCategoryCards();
    loadTagCloud();
    initMessageNotification();
    initBannerCarousel();
});

let bannerCarouselBanners = [];
let bannerCarouselIndex = 0;
let bannerCarouselTimer = null;

async function initBannerCarousel() {
    const carousel = document.getElementById('bannerCarousel');
    if (!carousel) return;

    const result = await apiRequest('banners/active', 'GET');
    if (result.code === 200 && result.data && result.data.length > 0) {
        bannerCarouselBanners = result.data;
        renderBannerCarousel();
        carousel.style.display = 'block';
        startBannerAutoPlay();
    }
}

function renderBannerCarousel() {
    const slidesContainer = document.getElementById('bannerCarouselSlides');
    const dotsContainer = document.getElementById('bannerCarouselDots');

    slidesContainer.innerHTML = bannerCarouselBanners.map((banner, index) => `
        <div class="banner-carousel-slide ${index === 0 ? 'active' : ''}">
            ${banner.link_url ? `<a href="${escapeHtml(banner.link_url)}" target="_blank">` : ''}
                <img src="${escapeHtml(banner.image_url)}" alt="${escapeHtml(banner.title || 'Banner')}">
            ${banner.link_url ? `</a>` : ''}
            ${banner.title || banner.subtitle ? `
                <div class="banner-carousel-overlay">
                    ${banner.title ? `<h3>${escapeHtml(banner.title)}</h3>` : ''}
                    ${banner.subtitle ? `<p>${escapeHtml(banner.subtitle)}</p>` : ''}
                </div>
            ` : ''}
        </div>
    `).join('');

    dotsContainer.innerHTML = bannerCarouselBanners.map((_, index) => `
        <span class="banner-carousel-dot ${index === 0 ? 'active' : ''}" onclick="goToBannerSlide(${index})"></span>
    `).join('');
}

function startBannerAutoPlay() {
    if (bannerCarouselTimer) clearInterval(bannerCarouselTimer);
    bannerCarouselTimer = setInterval(() => {
        nextBannerSlide();
    }, 5000);
}

function goToBannerSlide(index) {
    const slides = document.querySelectorAll('.banner-carousel-slide');
    const dots = document.querySelectorAll('.banner-carousel-dot');
    
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
    
    bannerCarouselIndex = index;
    if (bannerCarouselTimer) {
        clearInterval(bannerCarouselTimer);
        startBannerAutoPlay();
    }
}

function prevBannerSlide() {
    if (bannerCarouselBanners.length === 0) return;
    const newIndex = (bannerCarouselIndex - 1 + bannerCarouselBanners.length) % bannerCarouselBanners.length;
    goToBannerSlide(newIndex);
}

function nextBannerSlide() {
    if (bannerCarouselBanners.length === 0) return;
    const newIndex = (bannerCarouselIndex + 1) % bannerCarouselBanners.length;
    goToBannerSlide(newIndex);
}

async function loadTagCloud() {
    const container = document.getElementById('tagCloud');
    if (!container) return;

    const result = await apiRequest('tags/popular?limit=30', 'GET');
    if (result.code === 200 && result.data && result.data.length > 0) {
        const tags = result.data;
        const maxCount = Math.max(...tags.map(t => t.reference_count), 1);
        const minCount = Math.min(...tags.map(t => t.reference_count), 0);
        
        container.innerHTML = tags.map(tag => {
            let fontSize = 14;
            if (maxCount > minCount) {
                fontSize = 12 + ((tag.reference_count - minCount) / (maxCount - minCount)) * 20;
            }
            return `
                <a href="search_notice.php?search_tags=${tag.id}" 
                   class="tag-cloud-item" 
                   style="font-size: ${fontSize}px; color: ${tag.color};"
                   title="${escapeHtml(tag.name)} (${tag.reference_count} 条公告)">
                    ${escapeHtml(tag.name)}
                </a>
            `;
        }).join('');
    } else {
        container.innerHTML = '<p class="no-data">暂无标签</p>';
    }
}

async function loadCategoryCards() {
    const container = document.getElementById('categoryCards');
    if (!container) return;

    const result = await apiRequest('categories/stats', 'GET');
    if (result.code === 200 && result.data) {
        if (result.data.length === 0) {
            container.innerHTML = '<p class="no-data">暂无分类</p>';
            return;
        }
        
        container.innerHTML = result.data.map(cat => `
            <a href="search_notice.php?search_category=${cat.id}" class="category-card" style="--cat-color: ${cat.color};">
                <div class="category-card-header">
                    <span class="category-card-emoji">${cat.emoji || '📁'}</span>
                    <span class="category-card-count">${cat.notice_count}</span>
                </div>
                <h4 class="category-card-name">${escapeHtml(cat.name)}</h4>
                <p class="category-card-desc">${escapeHtml(cat.description || '查看该分类下的公告')}</p>
            </a>
        `).join('');
    } else {
        container.innerHTML = '<p class="no-data">加载失败</p>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

let messagePollingInterval = null;

async function initMessageNotification() {
    const badge = document.getElementById('messageBadge');
    if (!badge) return;

    await updateUnreadCount();

    messagePollingInterval = setInterval(updateUnreadCount, 30000);
}

async function updateUnreadCount() {
    const badge = document.getElementById('messageBadge');
    if (!badge) return;

    try {
        const result = await apiRequest('messages/unread_count', 'GET');
        if (result.code === 200 && result.data) {
            const count = result.data.count;
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    } catch (e) {
        console.error('Failed to fetch unread count:', e);
    }
}

function toggleMessagePanel() {
    const dropdown = document.getElementById('messageDropdown');
    if (!dropdown) return;

    const isOpen = dropdown.classList.contains('show');
    if (isOpen) {
        dropdown.classList.remove('show');
    } else {
        dropdown.classList.add('show');
        loadRecentMessages();
    }
}

async function loadRecentMessages() {
    const listEl = document.getElementById('messageDropdownList');
    if (!listEl) return;

    listEl.innerHTML = '<div class="message-loading">加载中...</div>';

    const result = await apiRequest('messages/recent', 'GET');
    if (result.code === 200 && result.data) {
        if (result.data.length === 0) {
            listEl.innerHTML = '<div class="message-empty">暂无未读消息</div>';
            return;
        }

        listEl.innerHTML = result.data.map(msg => `
            <div class="message-item" onclick="markMessageRead(${msg.id})">
                <div class="message-item-header">
                    <span class="message-type-badge message-type-${escapeHtml(msg.type)}">${getTypeLabel(msg.type)}</span>
                    <span class="message-item-time">${formatMessageTime(msg.created_at)}</span>
                </div>
                <div class="message-item-title">${escapeHtml(msg.title)}</div>
                ${msg.body ? `<div class="message-item-body">${escapeHtml(msg.body).substring(0, 60)}${msg.body.length > 60 ? '...' : ''}</div>` : ''}
            </div>
        `).join('');
    } else {
        listEl.innerHTML = '<div class="message-empty">加载失败</div>';
    }
}

async function markMessageRead(id) {
    const result = await apiRequest('messages/mark_read', 'POST', { id: id });
    if (result.code === 200) {
        updateUnreadCount();
        loadRecentMessages();
    }
}

function getTypeLabel(type) {
    const labels = {
        system: '系统',
        notice: '公告',
        security: '安全',
        activity: '活动'
    };
    return labels[type] || type;
}

function formatMessageTime(timeStr) {
    const now = new Date();
    const time = new Date(timeStr.replace(/-/g, '/'));
    const diff = now - time;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return '刚刚';
    if (minutes < 60) return `${minutes}分钟前`;
    if (hours < 24) return `${hours}小时前`;
    if (days < 7) return `${days}天前`;
    return timeStr.substring(0, 16);
}

document.addEventListener('click', function(e) {
    const notification = document.querySelector('.message-notification');
    const dropdown = document.getElementById('messageDropdown');
    if (notification && dropdown && !notification.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});
