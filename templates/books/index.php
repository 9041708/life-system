<?php
/** @var array $books */
/** @var string $tab */
$tab = $tab ?? 'personal';
function formatSize($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">📚 在线阅览</h5>
    <div class="d-flex gap-2">
        <input type="text" id="searchInput" class="form-control form-control-sm" style="width:200px" placeholder="搜索书名..." oninput="filterBooks()">
        <a href="/public/index.php?route=books-config" class="btn btn-sm btn-outline-secondary">⚙️ 管理</a>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'personal' ? 'active' : '' ?>" href="/public/index.php?route=books&tab=personal">自主上传</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'system' ? 'active' : '' ?>" href="/public/index.php?route=books&tab=system">系统书籍</a>
    </li>
</ul>

<div class="row g-4" id="bookShelf">
    <?php if (empty($books)): ?>
    <div class="col-12 text-center text-muted py-5">
        <div style="font-size:64px">📚</div>
        <div class="mt-3">书架空空，去"图书配置"上传你的第一本书吧</div>
        <a href="/public/index.php?route=books-config" class="btn btn-sm btn-primary mt-2">前往上传</a>
    </div>
    <?php else: ?>
    <?php foreach ($books as $b): ?>
    <div class="col-lg-2 col-md-3 col-4 book-card" data-title="<?= htmlspecialchars(mb_strtolower($b['title'])) ?>" data-id="<?= (int)$b['id'] ?>" data-cover="<?= htmlspecialchars($b['cover'] ?? '') ?>" data-author="<?= htmlspecialchars($b['author']) ?>" data-desc="<?= htmlspecialchars($b['description'] ?? '') ?>" data-type="<?= $b['file_type'] ?>" data-size="<?= (int)($b['file_size'] ?? 0) ?>">
        <div class="card border-0 shadow-sm h-100" style="cursor:pointer" onclick="showBookInfo(this.parentElement)">
            <div class="card-body text-center p-2">
                <div style="width:100%;aspect-ratio:3/4;overflow:hidden;border-radius:4px;margin-bottom:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center">
                    <?php if (!empty($b['cover'])): ?>
                    <img src="<?= htmlspecialchars($b['cover']) ?>" style="width:100%;height:100%;object-fit:cover" alt="">
                    <?php else: ?>
                    <div style="font-size:36px">📖</div>
                    <?php endif; ?>
                </div>
                <div class="fw-bold" style="font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($b['title']) ?>"><?= htmlspecialchars($b['title']) ?></div>
                <?php if (!empty($b['author'])): ?>
                <div class="text-muted" style="font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($b['author']) ?></div>
                <?php endif; ?>
                <div style="font-size:10px;color:#94a3b8">
                    <?= strtoupper($b['file_type']) ?> · <?= formatSize((int)($b['file_size'] ?? 0)) ?>
                </div>
                <?php $pg = (int)($b['page_num'] ?? 0); if ($pg > 0): ?>
                <div class="mt-1">
                    <div class="progress" style="height:4px"><div class="progress-bar bg-success" style="width:<?= min(100, max(5, $pg)) ?>%"></div></div>
                    <span class="text-success" style="font-size:10px">第 <?= $pg ?> 页</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- 图书详情弹窗 -->
<div class="modal fade" id="modalBookInfo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title" id="biTitle"></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center">
                <img id="biCover" src="" style="max-width:120px;max-height:160px;border-radius:4px;margin-bottom:12px;display:none" alt="">
                <div style="font-size:48px" id="biIcon"></div>
                <div class="fw-bold" id="biAuthor" style="margin-top:4px"></div>
                <div class="text-muted small" id="biMeta"></div>
                <div class="mt-3 text-start" id="biDesc" style="font-size:13px;line-height:1.6;max-height:150px;overflow-y:auto"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button class="btn btn-sm btn-primary" id="biReadBtn">📖 开始阅读</button>
            </div>
        </div>
    </div>
</div>

<script>
function formatSize(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}
var biModal;
document.addEventListener('DOMContentLoaded', function(){
    biModal = new bootstrap.Modal(document.getElementById('modalBookInfo'));
});
function showBookInfo(el) {
    var id = el.getAttribute('data-id');
    var title = (el.getAttribute('data-title') || '');
    title = title.charAt(0).toUpperCase() + title.slice(1);
    var cover = el.getAttribute('data-cover');
    var author = el.getAttribute('data-author');
    var desc = el.getAttribute('data-desc');
    var type = el.getAttribute('data-type');
    var size = el.getAttribute('data-size');
    document.getElementById('biTitle').textContent = title;
    document.getElementById('biAuthor').textContent = author || '';
    document.getElementById('biMeta').textContent = type.toUpperCase() + ' · ' + formatSize(parseInt(size));
    document.getElementById('biDesc').textContent = desc || '暂无简介';
    if (cover) {
        document.getElementById('biCover').src = cover;
        document.getElementById('biCover').style.display = '';
        document.getElementById('biIcon').style.display = 'none';
    } else {
        document.getElementById('biCover').style.display = 'none';
        document.getElementById('biIcon').style.display = '';
        document.getElementById('biIcon').textContent = '📖';
    }
    document.getElementById('biReadBtn').onclick = function(){
        window.location.href = '/public/index.php?route=books-reader&id=' + id;
    };
    biModal.show();
}
</script>
