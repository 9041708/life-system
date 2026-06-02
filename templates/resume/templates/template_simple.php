<?php
/** @var array $resume */
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
body{font-family:"PingFang SC","Microsoft YaHei",sans-serif;color:#333;background:#fff;line-height:1.6}
.resume{max-width:800px;margin:0 auto;padding:30px 40px}
.header{border-bottom:2px solid #2563eb;padding-bottom:20px;margin-bottom:24px}
.header h1{font-size:28px;color:#1e293b;margin-bottom:4px}
.header .title{font-size:16px;color:#2563eb;margin-bottom:12px}
.header .info{font-size:13px;color:#64748b;display:flex;flex-wrap:wrap;gap:4px 20px}
.header .info span{white-space:nowrap}
.header .info .print-hide{display:inline}
h2{font-size:16px;color:#2563eb;border-bottom:1px solid #e2e8f0;padding-bottom:6px;margin-bottom:12px;margin-top:20px}
.exp-item,.edu-item,.proj-item{margin-bottom:14px;page-break-inside:avoid}
.item-header{display:flex;justify-content:space-between;margin-bottom:3px}
.item-header .name{font-weight:600;font-size:15px;color:#1e293b}
.item-header .sub{font-size:13px;color:#64748b}
.item-header .date{font-size:13px;color:#94a3b8;white-space:nowrap}
.desc{font-size:13px;color:#475569;margin-top:4px;padding-left:12px}
.desc li{margin-bottom:2px}
.skills-wrap{display:flex;flex-wrap:wrap;gap:6px}
.skill-tag{background:#eff6ff;color:#2563eb;font-size:12px;padding:3px 10px;border-radius:4px}
.summary{font-size:14px;color:#475569;line-height:1.7;margin-bottom:6px}
.avatar-wrap{float:right;margin-left:20px}
.avatar-wrap img{width:72px;height:72px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0}

@media print{
  @page{margin:12mm 14mm;size:A4}
  body{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .resume{max-width:100%;padding:0}
  .header{border-bottom-color:#2563eb!important}
  .header h1{font-size:24px}
  .header .info{font-size:11px}
  h2{font-size:14px;margin-top:16px;border-bottom-color:#e2e8f0!important}
  .item-header .name{font-size:13px}
  .item-header .sub,.item-header .date{font-size:11px}
  .desc,.summary{font-size:11px}
  .skill-tag{font-size:11px;background:#eff6ff!important;color:#2563eb!important}
  .print-hide{display:none!important}
}
</style>
</head>
<body>
<div class="resume">
  <div class="header">
    <?php if (!empty($b['avatar'])): ?><div class="avatar-wrap"><img src="<?= htmlspecialchars($b['avatar']) ?>" alt=""></div><?php endif; ?>
    <h1><?= htmlspecialchars($b['name'] ?: '姓名') ?></h1>
    <?php if (!empty($b['title'])): ?><div class="title"><?= htmlspecialchars($b['title']) ?></div><?php endif; ?>
    <div class="info">
      <?php if (!empty($b['phone'])): ?><span>📱 <?= htmlspecialchars($b['phone']) ?></span><?php endif; ?>
      <?php if (!empty($b['email'])): ?><span>✉️ <?= htmlspecialchars($b['email']) ?></span><?php endif; ?>
      <?php if (!empty($b['birth'])): ?><span>🎂 <?= htmlspecialchars($b['birth']) ?></span><?php endif; ?>
      <?php if (!empty($b['location'])): ?><span>📍 <?= htmlspecialchars($b['location']) ?></span><?php endif; ?>
      <?php if (!empty($b['website'])): ?><span class="print-hide">🔗 <?= htmlspecialchars($b['website']) ?></span><?php endif; ?>
    </div>
  </div>

  <?php if (!empty($b['summary'])): ?>
  <h2>个人简介</h2>
  <div class="summary"><?= nl2br(htmlspecialchars($b['summary'])) ?></div>
  <?php endif; ?>

  <?php if (!empty($exp)): ?>
  <h2>工作经历</h2>
  <?php foreach ($exp as $e): ?>
  <div class="exp-item">
    <div class="item-header">
      <span class="name"><?= htmlspecialchars($e['company'] ?? '') ?></span>
      <span class="date"><?= htmlspecialchars(($e['start'] ?? '') . ' - ' . ($e['end'] ?? '')) ?></span>
    </div>
    <div class="item-header"><span class="sub"><?= htmlspecialchars($e['position'] ?? '') ?></span></div>
    <?php if (!empty($e['desc'])): ?><ul class="desc"><?php foreach (explode("\n", trim($e['desc'])) as $li): if (trim($li)): ?><li><?= htmlspecialchars(trim($li)) ?></li><?php endif; endforeach; ?></ul><?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($edu)): ?>
  <h2>教育背景</h2>
  <?php foreach ($edu as $e): ?>
  <div class="edu-item">
    <div class="item-header">
      <span class="name"><?= htmlspecialchars($e['school'] ?? '') ?></span>
      <span class="date"><?= htmlspecialchars(($e['start'] ?? '') . ' - ' . ($e['end'] ?? '')) ?></span>
    </div>
    <div class="item-header"><span class="sub"><?= htmlspecialchars(($e['major'] ?? '') . ($e['degree'] ? ' | ' . $e['degree'] : '')) ?></span></div>
    <?php if (!empty($e['desc'])): ?><ul class="desc"><?php foreach (explode("\n", trim($e['desc'])) as $li): if (trim($li)): ?><li><?= htmlspecialchars(trim($li)) ?></li><?php endif; endforeach; ?></ul><?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($projects)): ?>
  <h2>项目经验</h2>
  <?php foreach ($projects as $p): ?>
  <div class="proj-item">
    <div class="item-header">
      <span class="name"><?= htmlspecialchars($p['name'] ?? '') ?><?php if (!empty($p['url'])): ?> <a href="<?= htmlspecialchars($p['url']) ?>" target="_blank" class="print-hide" style="font-size:12px;color:#2563eb;text-decoration:none">🔗</a><?php endif; ?></span>
      <span class="sub"><?= htmlspecialchars($p['role'] ?? '') ?></span>
    </div>
    <?php if (!empty($p['desc'])): ?><ul class="desc"><?php foreach (explode("\n", trim($p['desc'])) as $li): if (trim($li)): ?><li><?= htmlspecialchars(trim($li)) ?></li><?php endif; endforeach; ?></ul><?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($skills)): ?>
  <h2>技能</h2>
  <div class="skills-wrap">
    <?php foreach ($skills as $s): ?>
    <span class="skill-tag"><?= htmlspecialchars($s['name'] ?? '') ?><?= !empty($s['level_percent']) ? ' · ' . htmlspecialchars($s['level_percent']) . '%' : '' ?></span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
