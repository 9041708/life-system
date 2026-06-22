<?php
$b = $resume['basic'] ?? [];
$exp = $resume['experience'] ?? [];
$edu = $resume['education'] ?? [];
$skills = $resume['skills'] ?? [];
$projects = $resume['projects'] ?? [];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($b['name'] ?: '简历') ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:"PingFang SC","Microsoft YaHei",sans-serif;color:#333;background:#fafafa;line-height:1.6}
.resume{max-width:780px;margin:0 auto;padding:36px 44px;background:#fff}
.top{display:flex;align-items:center;gap:20px;padding-bottom:20px;border-bottom:3px solid #f97316;margin-bottom:24px}
.top .avatar img{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #f97316}
.top .info h1{font-size:28px;color:#1e293b;margin-bottom:2px}
.top .info .title{font-size:15px;color:#f97316;margin-bottom:6px}
.top .info .meta{font-size:12px;color:#94a3b8;display:flex;flex-wrap:wrap;gap:2px 16px}
.top .info .meta span{white-space:nowrap}
.grid{display:flex;gap:24px}
.col-left{flex:1;border-right:1px dashed #e2e8f0;padding-right:20px}
.col-right{width:220px;padding-left:4px}
h2{font-size:15px;color:#f97316;margin:18px 0 10px;padding-bottom:4px;border-bottom:1px solid #fef3c7}
.exp-item,.edu-item,.proj-item{margin-bottom:12px;page-break-inside:avoid}
.item-hd{display:flex;justify-content:space-between;align-items:baseline}
.item-hd .name{font-weight:600;font-size:14px;color:#1e293b}
.item-hd .sub{font-size:13px;color:#64748b}
.item-hd .date{font-size:12px;color:#94a3b8}
.desc{font-size:13px;color:#475569;margin-top:2px;padding-left:12px}
.desc li{margin-bottom:1px}
.skill-item{font-size:13px;margin-bottom:6px}
.skill-item .name{color:#475569;margin-bottom:1px}
.skill-dots{display:flex;gap:3px}
.skill-dots span{width:16px;height:6px;border-radius:3px;background:#e2e8f0}
.skill-dots span.on{background:#f97316}
.summary{font-size:13px;color:#64748b;line-height:1.7}
.print-hide{display:inline}

@media print{
  @page{margin:10mm 12mm;size:A4}
  body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .resume{max-width:100%;padding:0;box-shadow:none}
  .top{border-bottom-color:#f97316!important}
  .top .info h1{font-size:24px}
  .top .avatar img{border-color:#f97316!important}
  .grid{gap:16px}
  .col-right{width:180px}
  h2{font-size:13px;color:#f97316!important;border-bottom-color:#fef3c7!important}
  .item-hd .name{font-size:12px}
  .item-hd .sub,.item-hd .date{font-size:11px}
  .desc,.summary{font-size:11px}
  .skill-dots span.on{background:#f97316!important}
  .print-hide{display:none!important}
}
</style>
</head>
<body>
<div class="resume">
  <div class="top">
    <?php if (!empty($b['avatar'])): ?><div class="avatar"><img src="<?= htmlspecialchars($b['avatar']) ?>" alt=""></div><?php endif; ?>
    <div class="info">
      <h1><?= htmlspecialchars($b['name'] ?: '姓名') ?></h1>
      <?php if (!empty($b['title'])): ?><div class="title"><?= htmlspecialchars($b['title']) ?></div><?php endif; ?>
      <div class="meta">
        <?php if (!empty($b['phone'])): ?><span>📱 <?= htmlspecialchars($b['phone']) ?></span><?php endif; ?>
        <?php if (!empty($b['email'])): ?><span>✉️ <?= htmlspecialchars($b['email']) ?></span><?php endif; ?>
        <?php if (!empty($b['location'])): ?><span>📍 <?= htmlspecialchars($b['location']) ?></span><?php endif; ?>
        <?php if (!empty($b['website'])): ?><span class="print-hide">🔗 <?= htmlspecialchars($b['website']) ?></span><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="grid">
    <div class="col-left">
      <?php if (!empty($b['summary'])): ?>
      <h2>关于我</h2>
      <div class="summary"><?= nl2br(htmlspecialchars($b['summary'])) ?></div>
      <?php endif; ?>

      <?php if (!empty($exp)): ?>
      <h2>工作经历</h2>
      <?php foreach ($exp as $e): ?>
      <div class="exp-item">
        <div class="item-hd"><span class="name"><?= htmlspecialchars($e['company'] ?? '') ?></span><span class="date"><?= htmlspecialchars(($e['start'] ?? '') . ' - ' . ($e['end'] ?? '')) ?></span></div>
        <div class="item-hd"><span class="sub"><?= htmlspecialchars($e['position'] ?? '') ?></span></div>
        <?php if (!empty($e['desc'])): ?><ul class="desc"><?php foreach (explode("\n", trim($e['desc'])) as $li): if (trim($li)): ?><li><?= htmlspecialchars(trim($li)) ?></li><?php endif; endforeach; ?></ul><?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if (!empty($projects)): ?>
      <h2>项目经验</h2>
      <?php foreach ($projects as $p): ?>
      <div class="proj-item">
        <div class="item-hd"><span class="name"><?= htmlspecialchars($p['name'] ?? '') ?><?php if (!empty($p['url'])): ?> <a href="<?= htmlspecialchars($p['url']) ?>" target="_blank" class="print-hide" style="font-size:12px;color:#f97316;text-decoration:none">🔗</a><?php endif; ?></span><span class="sub"><?= htmlspecialchars($p['role'] ?? '') ?></span></div>
        <?php if (!empty($p['desc'])): ?><ul class="desc"><?php foreach (explode("\n", trim($p['desc'])) as $li): if (trim($li)): ?><li><?= htmlspecialchars(trim($li)) ?></li><?php endif; endforeach; ?></ul><?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="col-right">
      <?php if (!empty($skills)): ?>
      <h2>技能</h2>
      <?php foreach ($skills as $s): ?>
      <div class="skill-item">
        <div class="name"><?= htmlspecialchars($s['name'] ?? '') ?></div>
        <?php $lv = (int)($s['level_percent'] ?? 60); $dots = max(1, min(5, ceil($lv / 20))); ?>
        <div class="skill-dots"><?php for ($i = 0; $i < 5; $i++): ?><span<?= $i < $dots ? ' class="on"' : '' ?>></span><?php endfor; ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if (!empty($edu)): ?>
      <h2>教育</h2>
      <?php foreach ($edu as $e): ?>
      <div class="edu-item">
        <div class="item-hd"><span class="name" style="font-size:13px"><?= htmlspecialchars($e['school'] ?? '') ?></span></div>
        <div class="item-hd"><span class="sub" style="font-size:12px"><?= htmlspecialchars(($e['major'] ?? '') . ($e['degree'] ? ' · ' . $e['degree'] : '')) ?></span><span class="date" style="font-size:11px"><?= htmlspecialchars(($e['start'] ?? '') . '-' . ($e['end'] ?? '')) ?></span></div>
        <?php if (!empty($e['desc'])): ?><ul class="desc"><?php foreach (explode("\n", trim($e['desc'])) as $li): if (trim($li)): ?><li><?= htmlspecialchars(trim($li)) ?></li><?php endif; endforeach; ?></ul><?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
