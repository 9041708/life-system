<?php
/** @var string $template */
/** @var array $resume */
/** @var string $resumeName */
/** @var int $resumeId */
/** @var array $resumes */
$resumeJson = json_encode($resume, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0">📄 简历配置</h5>
        <select id="resumeSelect" class="form-select form-select-sm" style="width:200px">
            <?php foreach ($resumes as $r): ?>
            <option value="<?= $r['id'] ?>" <?= $r['id'] === $resumeId ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
            <?php if (empty($resumes)): ?>
            <option value="0">暂无简历</option>
            <?php endif; ?>
        </select>
        <button class="btn btn-sm btn-outline-secondary" title="重命名" onclick="renameResume()" <?= !$resumeId ? 'disabled' : '' ?>>✏️</button>
    </div>
    <div class="d-flex gap-2">
        <select id="templateSelect" class="form-select form-select-sm" style="width:140px">
            <option value="simple" <?= $template === 'simple' ? 'selected' : '' ?>>简洁版</option>
            <option value="pro" <?= $template === 'pro' ? 'selected' : '' ?>>专业版</option>
            <option value="creative" <?= $template === 'creative' ? 'selected' : '' ?>>创意版</option>
        </select>
        <button class="btn btn-sm btn-outline-secondary" onclick="newResume()">➕ 新建</button>
        <button class="btn btn-sm btn-outline-info" onclick="copyResume()">📋 复制</button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteResume()">🗑 删除</button>
        <button class="btn btn-sm btn-primary" onclick="saveResume()">💾 保存</button>
        <button class="btn btn-sm btn-outline-success" onclick="exportPDF()">📄 生成PDF</button>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5" style="max-height:calc(100vh - 140px);overflow-y:auto">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-white d-flex align-items-center" style="cursor:pointer" onclick="var b=this.nextElementSibling;if(b){var a=this.querySelector('.basic-arrow');if(b.style.display==='none'){b.style.display='block';if(a)a.textContent='▾'}else{b.style.display='none';if(a)a.textContent='▸'}}">
                <span class="flex-grow-1"><strong>基本信息</strong> <small class="text-muted basic-name-label"><?= htmlspecialchars($resume['basic']['name'] ?: '未填写') ?></small></span>
                <span class="basic-arrow" style="font-size:12px;color:#94a3b8">▾</span>
            </div>
            <div class="card-body py-2 basic-body" style="display:block">
                <div class="row g-2">
                    <div class="col-6"><label class="form-label small mb-0">姓名</label><input class="form-control form-control-sm" data-field="basic.name"></div>
                    <div class="col-6"><label class="form-label small mb-0">职位</label><input class="form-control form-control-sm" data-field="basic.title"></div>
                    <div class="col-6"><label class="form-label small mb-0">电话</label><input class="form-control form-control-sm" data-field="basic.phone"></div>
                    <div class="col-6"><label class="form-label small mb-0">邮箱</label><input class="form-control form-control-sm" data-field="basic.email"></div>
                    <div class="col-4"><label class="form-label small mb-0">出生日期</label><input class="form-control form-control-sm" data-field="basic.birth" placeholder="1990-01"></div>
                    <div class="col-4"><label class="form-label small mb-0">所在地</label><input class="form-control form-control-sm" data-field="basic.location"></div>
                    <div class="col-4"><label class="form-label small mb-0">个人网站</label><input class="form-control form-control-sm" data-field="basic.website"></div>
                    <div class="col-12">
                        <label class="form-label small mb-0">头像</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="file" id="avatarFile" accept="image/*" class="form-control form-control-sm" style="max-width:260px">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="uploadAvatar()">上传</button>
                            <span id="avatarStatus" class="small text-muted"></span>
                            <img id="avatarPreview" src="<?= htmlspecialchars($resume['basic']['avatar'] ?? '') ?>" style="width:32px;height:32px;border-radius:4px;object-fit:cover;<?= empty($resume['basic']['avatar']) ? 'display:none' : '' ?>">
                        </div>
                        <input type="hidden" data-field="basic.avatar" id="basicAvatar">
                    </div>
                    <div class="col-12"><label class="form-label small mb-0">个人简介</label><textarea class="form-control form-control-sm" rows="3" data-field="basic.summary"></textarea></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                <strong>工作经历</strong>
                <button class="btn btn-sm btn-outline-primary" onclick="addItem('experience')">+ 添加</button>
            </div>
            <div class="card-body py-2" id="experienceContainer"></div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                <strong>教育背景</strong>
                <button class="btn btn-sm btn-outline-primary" onclick="addItem('education')">+ 添加</button>
            </div>
            <div class="card-body py-2" id="educationContainer"></div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                <strong>项目经验</strong>
                <button class="btn btn-sm btn-outline-primary" onclick="addItem('projects')">+ 添加</button>
            </div>
            <div class="card-body py-2" id="projectsContainer"></div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                <strong>技能</strong>
                <button class="btn btn-sm btn-outline-primary" onclick="addItem('skills')">+ 添加</button>
            </div>
            <div class="card-body py-2" id="skillsContainer"></div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm" style="background:#f8f9fa">
            <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                <strong>实时预览</strong>
                <small class="text-muted">模板: <?= htmlspecialchars($template === 'simple' ? '简洁版' : ($template === 'pro' ? '专业版' : '创意版')) ?></small>
            </div>
            <div class="card-body p-2">
                <iframe id="previewFrame" src="/public/index.php?route=resume-preview&standalone=1&id=<?= $resumeId ?>" style="width:100%;height:calc(100vh - 260px);border:none;background:#fff"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script>
var resumeData = <?= $resumeJson ?>;
var currentResumeId = <?= $resumeId ?: 0 ?>;
var currentTemplate = '<?= htmlspecialchars($template) ?>';

var expFields = ['company','position','start','end','desc'];
var eduFields = ['school','major','degree','start','end','desc'];
var projFields = ['name','url','role','desc'];
var skillFields = ['name','level_percent'];

function getNested(obj, path) {
    return path.split('.').reduce(function(o,k){return (o||{})[k]}, obj);
}
function setNested(obj, path, val) {
    var parts = path.split('.'), last = parts.pop(), cur = obj;
    parts.forEach(function(k){if(!cur[k])cur[k]={};cur=cur[k]});
    cur[last] = val;
}

function renderForm() {
    document.querySelectorAll('[data-field]').forEach(function(el){
        var val = getNested(resumeData, el.dataset.field);
        if (el.tagName === 'TEXTAREA') el.value = val || '';
        else el.value = val || '';
        el.oninput = function(){
            setNested(resumeData, this.dataset.field, this.value);
            autoSave();
            if (this.dataset.field === 'basic.name') {
                var label = document.querySelector('.basic-name-label');
                if (label) label.textContent = this.value || '未填写';
            }
        };
    });
    ['experience','education','projects','skills'].forEach(function(section){
        renderSection(section);
    });
}

var sectionFields = {experience:expFields, education:eduFields, projects:projFields, skills:skillFields};
var sectionLabels = {company:'公司/组织', position:'职位', start:'开始', end:'结束', desc:'描述（每行一条）',
    school:'学校', major:'专业', degree:'学历', name:'项目名称', url:'项目链接', role:'角色',
    level_percent:'熟练度%'};

function renderSection(section) {
    var container = document.getElementById(section+'Container');
    if (!container) return;
    var items = resumeData[section] || [];
    var fields = sectionFields[section];
    var hasTextarea = section === 'experience' || section === 'projects' || section === 'education';
    var inputFields = hasTextarea ? fields.filter(function(f){return f !== 'desc'}) : fields;
    var canCollapse = section === 'experience' || section === 'projects' || section === 'education' || section === 'skills';
    var html = '';
    items.forEach(function(item, idx){
        var titleKey = section === 'experience' ? 'company' : (section === 'education' ? 'school' : 'name');
        var titleVal = (item[titleKey] || '未命名');
        html += '<div class="border rounded mb-2" style="background:#fafbfc">';
        html += '<div class="d-flex align-items-center px-2 py-1" style="cursor:pointer;background:#f1f5f9;border-radius:4px 4px 0 0" onclick="toggleItem(this)">';
        html += '<span class="flex-grow-1 fw-bold small">' + titleVal + '</span>';
        html += '<span class="toggle-arrow me-2" style="font-size:12px;color:#94a3b8;transition:transform 0.2s">▸</span>';
        html += '<button class="btn btn-sm btn-link text-danger p-0" style="font-size:12px;line-height:1" onclick="event.stopPropagation();removeItem(\''+section+'\','+idx+')">✕</button>';
        html += '</div>';
        html += '<div class="collapse-body p-2" style="display:none">';
        inputFields.forEach(function(f){
            html += '<label class="form-label small mb-0 mt-1">'+ (sectionLabels[f]||f) +'</label>';
            html += '<input class="form-control form-control-sm section-input" data-section="'+section+'" data-idx="'+idx+'" data-field="'+f+'" value="' + (item[f]||'') + '">';
        });
        if (hasTextarea) {
            html += '<label class="form-label small mb-0 mt-1">描述</label><textarea class="form-control form-control-sm section-input" data-section="'+section+'" data-idx="'+idx+'" data-field="desc" rows="3">' + (item['desc']||'') + '</textarea>';
        }
        html += '</div></div>';
    });
    if (!canCollapse) {
        html = '';
        items.forEach(function(item, idx){
            html += '<div class="border rounded p-2 mb-2 position-relative" style="background:#fafbfc">';
            html += '<button class="btn btn-sm btn-link text-danger p-0 position-absolute" style="top:4px;right:8px;font-size:12px" onclick="removeItem(\''+section+'\','+idx+')">✕</button>';
            inputFields.forEach(function(f){
                html += '<label class="form-label small mb-0 mt-1">'+ (sectionLabels[f]||f) +'</label>';
                html += '<input class="form-control form-control-sm section-input" data-section="'+section+'" data-idx="'+idx+'" data-field="'+f+'" value="' + (item[f]||'') + '">';
            });
            if (hasTextarea) {
                html += '<label class="form-label small mb-0 mt-1">描述</label><textarea class="form-control form-control-sm section-input" data-section="'+section+'" data-idx="'+idx+'" data-field="desc" rows="3">' + (item['desc']||'') + '</textarea>';
            }
            html += '</div>';
        });
    }
    container.innerHTML = html || '<div class="text-muted small py-2">暂无，点击上方"添加"按钮</div>';
    container.querySelectorAll('.section-input').forEach(function(el){
        el.oninput = function(){
            var s = this.dataset.section, i = parseInt(this.dataset.idx), f = this.dataset.field;
            if (!resumeData[s]) resumeData[s] = [];
            if (!resumeData[s][i]) resumeData[s][i] = {};
            resumeData[s][i][f] = this.value;
            autoSave();
            var titleKey = (s === 'experience') ? 'company' : ((s === 'education') ? 'school' : 'name');
            if (f === titleKey) {
                var body = this.closest('.collapse-body');
                if (body) {
                    var header = body.previousElementSibling;
                    if (header) header.querySelector('.fw-bold').textContent = this.value || '未命名';
                }
            }
        };
    });
}

window.toggleItem = function(header) {
    var body = header.nextElementSibling;
    var arrow = header.querySelector('.toggle-arrow');
    if (!body) return;
    if (body.style.display === 'none') {
        body.style.display = 'block';
        if (arrow) { arrow.textContent = '▾'; arrow.style.transform = 'rotate(0deg)'; }
    } else {
        body.style.display = 'none';
        if (arrow) { arrow.textContent = '▸'; arrow.style.transform = 'rotate(0deg)'; }
    }
    var section = header.querySelector('.section-input');
    if (section) {
        var titleKey = section.dataset.section === 'experience' ? 'company' : (section.dataset.section === 'education' ? 'school' : 'name');
        var s = section.dataset.section, i = parseInt(section.dataset.idx);
        var item = (resumeData[s]||[])[i] || {};
        header.querySelector('.fw-bold').textContent = item[titleKey] || '未命名';
    }
};

window.addItem = function(section) {
    if (!resumeData[section]) resumeData[section] = [];
    resumeData[section].push({});
    renderSection(section);
    var container = document.getElementById(section+'Container');
    if (container) {
        var items = container.querySelectorAll('.border.rounded.mb-2');
        var last = items[items.length - 1];
        if (last) {
            var header = last.querySelector('[onclick^="toggleItem"]') || last.firstElementChild;
            if (header && header.nextElementSibling && header.nextElementSibling.classList.contains('collapse-body')) {
                header.nextElementSibling.style.display = 'block';
                var arrow = header.querySelector('.toggle-arrow');
                if (arrow) arrow.textContent = '▾';
            }
            last.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    }
    autoSave();
};

window.removeItem = function(section, idx) {
    resumeData[section].splice(idx, 1);
    renderSection(section);
    autoSave();
};

function updatePreview() {
    var frame = document.getElementById('previewFrame');
    if (!frame) return;
    var src = '/public/index.php?route=resume-preview&standalone=1&template=' + currentTemplate + '&id=' + currentResumeId;
    if (frame.src.indexOf(src) !== 0) {
        frame.src = src;
    }
}

var saveTimer = null;
function autoSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function(){
        var fd = new FormData();
        fd.append('action', 'save');
        fd.append('id', currentResumeId || '');
        fd.append('template', document.getElementById('templateSelect').value);
        fd.append('data', JSON.stringify(resumeData));
        fd.append('name', resumeData.basic && resumeData.basic.name ? resumeData.basic.name : '未命名简历');
        fetch('/public/index.php?route=resume-api', {method:'POST', body: fd})
            .then(function(r){return r.json()})
            .then(function(d){
                if (d.ok && d.id && !currentResumeId) {
                    currentResumeId = d.id;
                    updateResumeSelect();
                }
                updatePreview();
            });
    }, 500);
}

window.saveResume = function() {
    autoSave();
    alert('已保存');
};

function updateResumeSelect() {
    var fd = new FormData();
    fd.append('action', 'list');
    fetch('/public/index.php?route=resume-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok && d.resumes) {
                var sel = document.getElementById('resumeSelect');
                sel.innerHTML = '';
                d.resumes.forEach(function(r){
                    var opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name;
                    if (r.id === currentResumeId) opt.selected = true;
                    sel.appendChild(opt);
                });
            }
        });
}

document.getElementById('resumeSelect').onchange = function() {
    var id = parseInt(this.value);
    if (id === currentResumeId) return;
    if (!confirm('切换简历将丢失当前未保存的修改，确定切换？')) {
        this.value = currentResumeId;
        return;
    }
    window.location.href = '/public/index.php?route=resume-builder&id=' + id;
};

window.newResume = function() {
    var name = prompt('请输入新简历名称：', '新建简历');
    if (!name) return;
    var fd = new FormData();
    fd.append('action', 'new');
    fd.append('name', name);
    fetch('/public/index.php?route=resume-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) {
                window.location.href = '/public/index.php?route=resume-builder&id=' + d.id;
            } else {
                alert('新建失败: ' + (d.error || ''));
            }
        });
};

window.copyResume = function() {
    if (!currentResumeId) { alert('请先保存当前简历'); return; }
    if (!confirm('确认复制当前简历？')) return;
    var fd = new FormData();
    fd.append('action', 'copy');
    fd.append('id', currentResumeId);
    fetch('/public/index.php?route=resume-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) {
                window.location.href = '/public/index.php?route=resume-builder&id=' + d.id;
            } else {
                alert('复制失败: ' + (d.error || ''));
            }
        });
};

window.renameResume = function() {
    if (!currentResumeId) return;
    var sel = document.getElementById('resumeSelect');
    var currentName = sel.options[sel.selectedIndex].textContent;
    var name = prompt('请输入新名称：', currentName);
    if (!name || name === currentName) return;
    var fd = new FormData();
    fd.append('action', 'save');
    fd.append('id', currentResumeId);
    fd.append('template', document.getElementById('templateSelect').value);
    fd.append('data', JSON.stringify(resumeData));
    fd.append('name', name);
    fetch('/public/index.php?route=resume-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) {
                updateResumeSelect();
            }
        });
};
window.deleteResume = function() {
    if (!currentResumeId) { alert('没有可删除的简历'); return; }
    if (!confirm('确认删除当前简历？此操作不可恢复！')) return;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', currentResumeId);
    fetch('/public/index.php?route=resume-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) {
                window.location.href = '/public/index.php?route=resume-builder';
            } else {
                alert('删除失败: ' + (d.error || ''));
            }
        });
};

window.exportPDF = function() {
    autoSave();
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:9999;display:flex;align-items:center;justify-content:center';
    overlay.innerHTML = '<div style="background:#fff;padding:30px 50px;border-radius:12px;text-align:center"><div style="font-size:36px;margin-bottom:12px">⏳</div><div style="font-size:16px;color:#333">正在生成PDF...</div><div class="progress mt-3" style="width:200px;height:8px"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div></div></div>';
    document.body.appendChild(overlay);

    var tpl = document.getElementById('templateSelect').value;
    var url = '/public/index.php?route=resume-preview&standalone=1&template=' + tpl + '&id=' + currentResumeId;
    fetch(url)
        .then(function(r){return r.text()})
        .then(function(html){
            var container = document.createElement('div');
            container.style.cssText = 'position:fixed;left:-9999px;top:0;width:794px;z-index:-1';
            container.innerHTML = html;
            document.body.appendChild(container);
            html2canvas(container, {scale:2,useCORS:true,logging:false}).then(function(canvas){
                document.body.removeChild(container);
                var pageW = 210, pageH = 297;
                var pxPerMm = canvas.width / pageW;
                var pagePxH = pageH * pxPerMm;
                var totalPages = Math.ceil(canvas.height / pagePxH);
                var pdf = new jspdf.jsPDF('p', 'mm', 'a4');
                for (var p = 0; p < totalPages; p++) {
                    if (p > 0) pdf.addPage();
                    var sy = p * pagePxH;
                    var sh = Math.min(pagePxH, canvas.height - sy);
                    var pageCanvas = document.createElement('canvas');
                    pageCanvas.width = canvas.width;
                    pageCanvas.height = sh;
                    var ctx = pageCanvas.getContext('2d');
                    ctx.drawImage(canvas, 0, sy, canvas.width, sh, 0, 0, canvas.width, sh);
                    var imgData = pageCanvas.toDataURL('image/jpeg', 0.95);
                    var hMm = sh / pxPerMm;
                    pdf.addImage(imgData, 'JPEG', 0, 0, pageW, hMm);
                }
                var blob = pdf.output('blob');
                var blobUrl = URL.createObjectURL(blob);
                document.body.removeChild(overlay);
                window.open(blobUrl, '_blank');
            });
        });
};

window.uploadAvatar = function() {
    var fileInput = document.getElementById('avatarFile');
    var file = fileInput.files[0];
    if (!file) { alert('请选择图片'); return; }
    var fd = new FormData();
    fd.append('action', 'upload_avatar');
    fd.append('avatar', file);
    document.getElementById('avatarStatus').textContent = '上传中...';
    fetch('/public/index.php?route=resume-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) {
                document.getElementById('basicAvatar').value = d.url;
                setNested(resumeData, 'basic.avatar', d.url);
                var img = document.getElementById('avatarPreview');
                img.src = d.url; img.style.display = '';
                document.getElementById('avatarStatus').textContent = '上传成功';
                autoSave();
            } else {
                alert('上传失败: ' + (d.error||''));
                document.getElementById('avatarStatus').textContent = '';
            }
        });
};

document.getElementById('templateSelect').onchange = function() {
    currentTemplate = this.value;
    updatePreview();
    autoSave();
};

renderForm();
</script>
