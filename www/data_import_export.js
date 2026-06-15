let currentUploadKey = null;

function toggleAllFields(checked) {
    document.querySelectorAll('input[name="export_field"]').forEach(cb => {
        cb.checked = checked;
    });
}

function doExport(format) {
    const checkedFields = [];
    document.querySelectorAll('input[name="export_field"]:checked').forEach(cb => {
        checkedFields.push(cb.value);
    });

    if (checkedFields.length === 0) {
        showToast('请至少选择一个导出字段', 'error');
        return;
    }

    const params = new URLSearchParams();
    params.set('format', format);
    params.set('fields', checkedFields.join(','));

    const priority = document.getElementById('exportPriority').value;
    if (priority) params.set('priority', priority);

    const status = document.getElementById('exportStatus').value;
    if (status) params.set('status', status);

    const category_id = document.getElementById('exportCategory').value;
    if (category_id) params.set('category_id', category_id);

    const date_from = document.getElementById('exportDateFrom').value;
    if (date_from) params.set('date_from', date_from);

    const date_to = document.getElementById('exportDateTo').value;
    if (date_to) params.set('date_to', date_to);

    window.location.href = 'api/export?' + params.toString();
}

function initUploadArea() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('importFile');
    const placeholder = document.getElementById('uploadPlaceholder');

    if (!uploadArea || !fileInput) return;

    uploadArea.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-file')) return;
        fileInput.click();
    });

    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.add('drag-over');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('drag-over');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileUpload(files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileUpload(this.files[0]);
        }
    });
}

async function handleFileUpload(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['csv', 'xls', 'xlsx'].includes(ext)) {
        showToast('仅支持 CSV、XLS、XLSX 格式文件', 'error');
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        showToast('文件大小不能超过5MB', 'error');
        return;
    }

    document.getElementById('uploadPlaceholder').style.display = 'none';
    document.getElementById('uploadProgress').style.display = 'flex';
    document.getElementById('uploadFileInfo').style.display = 'none';

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch('api/import/upload', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        document.getElementById('uploadProgress').style.display = 'none';

        if (result.code === 200) {
            currentUploadKey = result.data.upload_key;

            document.getElementById('uploadFileInfo').style.display = 'flex';
            document.getElementById('uploadFileName').textContent = file.name;

            renderPreview(result.data);
            showToast('文件解析成功', 'success');
        } else {
            resetUploadUI();
            showToast(result.message || '文件解析失败', 'error');
        }
    } catch (error) {
        document.getElementById('uploadProgress').style.display = 'none';
        resetUploadUI();
        showToast('文件上传失败，请稍后重试', 'error');
    }

    document.getElementById('importFile').value = '';
}

function renderPreview(data) {
    const previewSection = document.getElementById('previewSection');
    const previewTableBody = document.getElementById('previewTableBody');
    const previewCount = document.getElementById('previewCount');
    const importActions = document.getElementById('importActions');

    previewCount.textContent = `(共 ${data.total_rows} 行，有效 ${data.valid_rows} 行，无效 ${data.invalid_rows} 行，显示前10行)`;

    previewTableBody.innerHTML = '';
    data.preview.forEach(item => {
        const row = document.createElement('tr');
        row.className = item.valid ? 'row-valid' : 'row-invalid';

        const statusHtml = item.valid
            ? '<span class="validation-badge badge-valid">通过</span>'
            : '<span class="validation-badge badge-invalid">失败</span>';

        row.innerHTML = `
            <td>${item.row_index}</td>
            <td title="${escapeHtml(item.data['公告标题'] || '')}">${escapeHtml(truncate(item.data['公告标题'] || '', 20))}</td>
            <td>${escapeHtml(item.data['发布人'] || '')}</td>
            <td>${escapeHtml(item.data['分类名称'] || '')}</td>
            <td>${escapeHtml(item.data['优先级'] || '')}</td>
            <td>${escapeHtml(item.data['状态'] || '')}</td>
            <td>${statusHtml}${item.errors.length > 0 ? '<small class="error-detail">' + escapeHtml(item.errors.join('；')) + '</small>' : ''}</td>
        `;
        previewTableBody.appendChild(row);
    });

    previewSection.style.display = 'block';
    importActions.style.display = 'block';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function truncate(str, maxLen) {
    if (!str) return '';
    return str.length > maxLen ? str.substring(0, maxLen) + '...' : str;
}

async function confirmImport() {
    if (!currentUploadKey) {
        showToast('请先上传文件', 'error');
        return;
    }

    const confirmBtn = document.getElementById('confirmImportBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="spinner"></span> 导入中...';

    document.getElementById('importActions').style.display = 'none';
    document.getElementById('importProgressSection').style.display = 'block';

    animateProgress(0, 80, 3000);

    try {
        const response = await fetch('api/import/confirm', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ upload_key: currentUploadKey })
        });
        const result = await response.json();

        animateProgress(80, 100, 500);

        setTimeout(() => {
            document.getElementById('importProgressSection').style.display = 'none';
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;

            if (result.code === 200) {
                renderImportResult(result.data);
            } else {
                showToast(result.message || '导入失败', 'error');
                document.getElementById('importActions').style.display = 'block';
            }
        }, 600);
    } catch (error) {
        document.getElementById('importProgressSection').style.display = 'none';
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        document.getElementById('importActions').style.display = 'block';
        showToast('导入请求失败，请稍后重试', 'error');
    }
}

function animateProgress(from, to, duration) {
    const bar = document.getElementById('importProgressBar');
    const startTime = Date.now();

    function update() {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = from + (to - from) * progress;
        bar.style.width = current + '%';

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    requestAnimationFrame(update);
}

function renderImportResult(data) {
    document.getElementById('resultSuccessCount').textContent = data.success_count;
    document.getElementById('resultFailCount').textContent = data.fail_count;

    const resultDetail = document.getElementById('resultDetail');
    const resultTableBody = document.getElementById('resultTableBody');

    const failedRows = data.results.filter(r => !r.success);

    if (failedRows.length > 0) {
        resultDetail.style.display = 'block';
        resultTableBody.innerHTML = '';
        failedRows.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.row_index}</td>
                <td>${escapeHtml(item.title || '')}</td>
                <td class="error-text">${escapeHtml(item.errors.join('；'))}</td>
            `;
            resultTableBody.appendChild(row);
        });
    } else {
        resultDetail.style.display = 'none';
    }

    document.getElementById('importResultSection').style.display = 'block';
    document.getElementById('previewSection').style.display = 'none';

    if (data.fail_count === 0) {
        showToast(`导入完成！成功导入 ${data.success_count} 条公告`, 'success');
    } else {
        showToast(`导入完成！成功 ${data.success_count} 条，失败 ${data.fail_count} 条`, 'error');
    }
}

function clearUpload() {
    currentUploadKey = null;
    resetUploadUI();
    document.getElementById('previewSection').style.display = 'none';
    document.getElementById('importActions').style.display = 'none';
    document.getElementById('importResultSection').style.display = 'none';
    document.getElementById('importProgressSection').style.display = 'none';
}

function resetUploadUI() {
    document.getElementById('uploadPlaceholder').style.display = 'flex';
    document.getElementById('uploadProgress').style.display = 'none';
    document.getElementById('uploadFileInfo').style.display = 'none';
}

function resetImport() {
    clearUpload();
    document.getElementById('importFile').value = '';
}

document.addEventListener('DOMContentLoaded', function() {
    initUploadArea();
});
