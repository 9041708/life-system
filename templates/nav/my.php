<?php
/** @var array $groups */
/** @var array $bookmarks */
/** @var string $tab */
?>
<style>
.nv-card {
    border: 1px solid rgba(15,23,42,0.08);
    border-radius: 0.75rem;
    padding: 14px;
    background: rgba(255,255,255,0.5);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: all 0.2s;
    cursor: pointer;
    display: flex; align-items: center; gap: 10px;
    text-decoration: none; color: inherit;
}
.nv-card:hover {
    background: rgba(255,255,255,0.75);
    box-shadow: 0 4px 16px rgba(15,23,42,0.08);
    transform: translateY(-2px);
    color: inherit;
}
body.theme-dark .nv-card {
    background: rgba(30,41,59,0.4);
    border-color: rgba(148,163,184,0.12);
}
body.theme-dark .nv-card:hover {
    background: rgba(30,41,59,0.6);
    box-shadow: 0 4px 16px rgba(0,0,0,0.3);
}
.nv-card .nv-icon {
    width: 40px; height: 40px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    border-radius: 0.5rem; overflow: hidden;
    background: rgba(15,23,42,0.04);
}
body.theme-dark .nv-card .nv-icon { background: rgba(148,163,184,0.08); }
.nv-card .nv-icon img { width: 100%; height: 100%; object-fit: cover; }
.nv-card .nv-name { font-weight: 600; font-size: 0.95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.nv-card .nv-meta { font-size: 0.72rem; color: #6b7280; }
body.theme-dark .nv-card .nv-meta { color: #9ca3af; }
.nv-group-title { font-weight: 600; font-size: 0.88rem; margin: 1.25rem 0 0.75rem; padding-bottom: 6px; border-bottom: 1px solid rgba(15,23,42,0.06); display: flex; align-items: center; gap: 6px; }
body.theme-dark .nv-group-title { border-bottom-color: rgba(148,163,184,0.1); }
.nv-push-badge { font-size: 0.68rem; }
.nv-page-wrap { max-width: 1100px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">🧭 我的导航</h5>
    <a href="/public/index.php?route=nav-config" class="btn btn-sm btn-glass">⚙️ 导航配置</a>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $tab === 'own' ? 'active' : '' ?>" href="?route=nav-my&tab=own">📌 我的标签</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'pushed' ? 'active' : '' ?>" href="?route=nav-my&tab=pushed">📢 系统推送</a></li>
</ul>

<?php if (empty($bookmarks)): ?>
    <div class="text-center text-muted py-5">
        <div style="font-size:48px;margin-bottom:12px">🧭</div>
        <div><?= $tab === 'pushed' ? '暂无系统推送的导航' : '暂无导航标签' ?></div>
        <div class="small mt-1"><?= $tab === 'pushed' ? '' : '点击右上角"导航配置"添加' ?></div>
    </div>
<?php elseif ($tab === 'pushed'): ?>
    <div class="nv-page-wrap">
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 mb-2">
        <?php foreach ($bookmarks as $b): ?>
        <div class="col">
            <a href="/public/index.php?route=nav-detail&id=<?= (int)$b['id'] ?>" class="nv-card" style="min-height:64px;">
                <div class="nv-icon">
                    <?php if (!empty($b['icon_type']) && !empty($b['icon_value'])): ?>
                        <?php if ($b['icon_type'] === 'file'): ?>
                            <img src="/uploads/<?= htmlspecialchars($b['icon_value']) ?>" alt="">
                        <?php elseif ($b['icon_type'] === 'svg'): ?>
                            <img src="data:image/svg+xml;base64,<?= base64_encode($b['icon_value']) ?>" alt="">
                        <?php elseif ($b['icon_type'] === 'url'): ?>
                            <img src="<?= htmlspecialchars($b['icon_value']) ?>" alt="" onerror="this.style.display='none'">
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="font-size:1.2rem;">🌐</span>
                    <?php endif; ?>
                </div>
                <div style="min-width:0;flex:1;">
                    <div class="nv-name"><?= htmlspecialchars($b['name']) ?></div>
                    <div class="nv-meta"><?= htmlspecialchars($b['group_name'] ?? '') ?> · 推送</div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    </div>
<?php else: ?>
    <div class="nv-page-wrap">
    <?php
    $grouped = [];
    foreach ($bookmarks as $b) {
        $grouped[$b['group_id']][] = $b;
    }
    ?>
    <?php foreach ($groups as $g): ?>
        <?php if (empty($grouped[$g['id']])) continue; ?>
        <div class="nv-group-title">
            <?php if (!empty($g['icon_type']) && !empty($g['icon_value'])): ?>
                <?php if ($g['icon_type'] === 'file'): ?>
                    <img src="/uploads/<?= htmlspecialchars($g['icon_value']) ?>" style="width:16px;height:16px;object-fit:cover;border-radius:3px;">
                <?php elseif ($g['icon_type'] === 'svg'): ?>
                    <img src="data:image/svg+xml;base64,<?= base64_encode($g['icon_value']) ?>" style="width:16px;height:16px;" alt="">
                <?php elseif ($g['icon_type'] === 'url'): ?>
                    <img src="<?= htmlspecialchars($g['icon_value']) ?>" style="width:16px;height:16px;object-fit:contain;" alt="" onerror="this.style.display='none'">
                <?php endif; ?>
            <?php endif; ?>
            <?= htmlspecialchars($g['name']) ?>
        </div>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 mb-2">
            <?php foreach ($grouped[$g['id']] as $b): ?>
            <div class="col">
                <a href="/public/index.php?route=nav-detail&id=<?= (int)$b['id'] ?>" class="nv-card" style="min-height:64px;">
                    <div class="nv-icon">
                        <?php if (!empty($b['icon_type']) && !empty($b['icon_value'])): ?>
                            <?php if ($b['icon_type'] === 'file'): ?>
                                <img src="/uploads/<?= htmlspecialchars($b['icon_value']) ?>" alt="">
                            <?php elseif ($b['icon_type'] === 'svg'): ?>
                                <img src="data:image/svg+xml;base64,<?= base64_encode($b['icon_value']) ?>" alt="">
                            <?php elseif ($b['icon_type'] === 'url'): ?>
                                <img src="<?= htmlspecialchars($b['icon_value']) ?>" alt="" onerror="this.style.display='none'">
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="font-size:1.2rem;">🌐</span>
                        <?php endif; ?>
                    </div>
                    <div style="min-width:0;flex:1;">
                        <div class="nv-name"><?= htmlspecialchars($b['name']) ?></div>
                        <?php if (!empty($b['description'])): ?>
                        <div class="nv-meta text-truncate"><?= htmlspecialchars($b['description']) ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
