<?php
/** @var array $bookmark */
?>
<style>
.nv-detail-card {
    border-radius: 1rem;
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,0.7);
}
body.theme-dark .nv-detail-card {
    background: rgba(30,41,59,0.5);
    border-color: rgba(148,163,184,0.18);
}
.nv-detail-screenshot {
    width: 100%; height: 320px; object-fit: cover;
    border-radius: 0.6rem; cursor: zoom-in;
    background: rgba(15,23,42,0.04);
}
body.theme-dark .nv-detail-screenshot { background: rgba(0,0,0,0.3); }
</style>

<div class="mb-3">
    <a href="/public/index.php?route=nav-my" class="btn btn-sm btn-outline-secondary">← 返回导航</a>
    <a href="<?= htmlspecialchars($bookmark['url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-primary ms-2">🔗 访问网站</a>
</div>

<div class="nv-detail-card p-4">
    <div class="row">
        <!-- 左侧：内容 -->
        <div class="col-md-7 col-lg-8">
            <h5 class="mb-3">
                <?php if (!empty($bookmark['icon_type']) && !empty($bookmark['icon_value'])): ?>
                    <?php if ($bookmark['icon_type'] === 'file'): ?>
                        <img src="/uploads/<?= htmlspecialchars($bookmark['icon_value']) ?>" style="width:28px;height:28px;object-fit:cover;border-radius:6px;vertical-align:middle;margin-right:8px;">
                    <?php elseif ($bookmark['icon_type'] === 'svg'): ?>
                        <img src="data:image/svg+xml;base64,<?= base64_encode($bookmark['icon_value']) ?>" style="width:28px;height:28px;vertical-align:middle;margin-right:8px;" alt="">
                    <?php elseif ($bookmark['icon_type'] === 'url'): ?>
                        <img src="<?= htmlspecialchars($bookmark['icon_value']) ?>" style="width:28px;height:28px;object-fit:contain;vertical-align:middle;margin-right:8px;" alt="" onerror="this.style.display='none'">
                    <?php endif; ?>
                <?php endif; ?>
                <?= htmlspecialchars($bookmark['name']) ?>
            </h5>

            <div class="mb-3">
                <div class="small text-muted mb-1">主网址</div>
                <a href="<?= htmlspecialchars($bookmark['url']) ?>" target="_blank" rel="noopener" class="text-break"><?= htmlspecialchars($bookmark['url']) ?></a>
            </div>

            <?php $urls = $bookmark['urls'] ?? []; ?>
            <?php if (!empty($urls)): ?>
                <div class="mb-3">
                    <div class="small text-muted mb-1">备用链接</div>
                    <ul class="list-unstyled small mb-0 ms-3">
                    <?php foreach ($urls as $u): ?>
                        <li class="mb-1">
                            <?php if (!empty($u['label'])): ?><span class="text-muted">[<?= htmlspecialchars($u['label']) ?>]</span> <?php endif; ?>
                            <a href="<?= htmlspecialchars($u['url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($u['url']) ?></a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($bookmark['description'])): ?>
                <div class="mb-3">
                    <div class="small text-muted mb-1">介绍</div>
                    <div style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($bookmark['description'])) ?></div>
                </div>
            <?php endif; ?>

            <hr class="my-3" style="border-color:rgba(15,23,42,0.06);">
            <div class="row small text-muted g-2">
                <div class="col-auto"><span>分组：<?= htmlspecialchars($bookmark['group_name'] ?? '-') ?></span></div>
                <div class="col-auto"><span>创建：<?= htmlspecialchars(substr($bookmark['created_at'] ?? '', 0, 10)) ?></span></div>
                <div class="col-auto"><span>更新：<?= htmlspecialchars(substr($bookmark['updated_at'] ?? '', 0, 16)) ?></span></div>
            </div>
        </div>

        <!-- 右侧：截图 -->
        <div class="col-md-5 col-lg-4 mt-3 mt-md-0">
            <?php if (!empty($bookmark['screenshot'])): ?>
                <img src="/uploads/<?= htmlspecialchars($bookmark['screenshot']) ?>" alt="页面截图" class="nv-detail-screenshot" onclick="this.requestFullscreen?.()||this.webkitRequestFullscreen?.()" title="点击全屏查看">
            <?php else: ?>
                <div class="nv-detail-screenshot d-flex align-items-center justify-content-center text-muted small" style="border:1px dashed rgba(15,23,42,0.1);">
                    🌐 暂无截图
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
