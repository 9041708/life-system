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
body{font-family:"PingFang SC","Microsoft YaHei",sans-serif;color:#333;background:#f5f5f5;line-height:1.6}
.resume{max-width:860px;margin:0 auto;display:flex;min-height:100vh;background:#fff}
.sidebar{width:240px;background:#1e293b;color:#e2e8f0;padding:30px 20px;flex-shrink:0}
.sidebar .avatar{text-align:center;margin-bottom:20px}
.sidebar .avatar img{width:90px;height:90px;border-radius:50%;object-fit:cover;border:2px solid #38bdf8}
.sidebar h3{font-size:13px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid #334155;padding-bottom:6px;margin:18px 0 10px}
.sidebar .info-item{font-size:13px;margin-bottom:6px;color:#cbd5e1}
.sidebar .info-item a{color:#38bdf8;text-decoration:none}
.sidebar .skill-bar{margin-bottom:8px}
.sidebar .skill-bar .name{font-size:12px;color:#cbd5e1;margin-bottom:2px}
.sidebar .skill-bar .bar{height:4px;background:#334155;border-radius:2px;overflow:hidden}
.sidebar .skill-bar .fill{height:100%;background:#38bdf8;border-radius:2px}
.main{flex:1;padding:30px 35px}
.main h1{font-size:26px;color:#1e293b;margin-bottom:2px}
.main .tagline{font-size:14px;color:#38bdf8;margin-bottom:20px}
.main h2{font-size:16px;color:#1e293b;border-left:3px solid #38bdf8;padding-left:10px;margin-bottom:12px;margin-top:22px}
.exp-item,.edu-item,.proj-item{margin-bottom:14px;page-break-inside:avoid}
.item-hd{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:2px}
.item-hd .name{font-weight:600;font-size:14px;color:#1e293b}
.item-hd .sub{font-size:13px;color:#64748b}
.item-hd .date{font-size:12px;color:#94a3b8;white-space:nowrap}
.desc{font-size:13px;color:#475569;margin-top:3px;padding-left:14px}
.desc li{margin-bottom:1px}
.summary{font-size:14px;color:#475569;line-height:1.7}
.print-hide{display:inline}

@media print{
  @page{margin:8mm 10mm;size:A4}
  body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .resume{max-width:100%;min-height:auto}
  .sidebar{background:#1e293b!important;color:#e2e8f0!important;width:200px;padding:20px 16px}
  .sidebar *{color:#e2e8f0!important}
  .sidebar h3{color:#94a3b8!important}
  .sidebar .info-item{color:#cbd5e1!important;font-size:11px}
  .sidebar .skill-bar .fill{background:#38bdf8!important}
  .main{padding:20px 24px}
  .main h1{font-size:22px}
  .main h2{font-size:14px;margin-top:16px}
  .item-hd .name{font-size:12px}
  .item-hd .sub,.item-hd .date{font-size:11px}
  .desc,.summary{font-size:11px}
  .print-hide{display:none!important}
}
</style>
</head>
<body>
<div class="resume">
  <div class="sidebar">
    <?php if (!empty($b['avatar'])): ?><div class="avatar"><img src="<?= htmlspecialchars($b['avatar']) ?>" alt=""></div><?php endif; ?>
    <h3>联系方式</h3>
    <?php if (!empty($b['phone'])): ?><div class="info-item">📱 <?= htmlspecialchars($b['phone']) ?></div><?php endif; ?>
    <?php if (!empty($b['email'])): ?><div class="info-item">✉️ <?= htmlspecialchars($b['email']) ?></div><?php endif; ?>
    <?php if (!empty($b['birth'])): ?><div class="info-item">🎂 <?= htmlspecialchars($b['birth']) ?></div><?php endif; ?>
    <?php if (!empty($b['location'])): ?><div class="info-item">📍 <?= htmlspecialchars($b['location']) ?></div><?php endif; ?>
    <?php if (!empty($b['website'])): ?><div class="info-item print-hide">🔗 <a href="<?= htmlspecialchars($b['website']) ?>"><?= htmlspecialchars($b['website']) ?></a></div><?php endif; ?>

    <?php if (!empty($skills)): ?>
    <h3>技能</h3>
    <?php foreach ($skills as $s): ?>
    <div class="skill-bar">
      <div class="name"><?= htmlspecialchars($s['name'] ?? '') ?></div>
      <div class="bar"><div class="fill" style="width:<?= (int)($s['level_percent'] ?? 60) ?>%"></div></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="main">
    <h1><?= htmlspecialchars($b['name'] ?: '姓名') ?></h1>
    <?php if (!empty($b['title'])): ?><div class="tagline"><?= htmlspecialchars($b['title']) ?></div><?php endif; ?>

    <?php if (!empty($b['summary'])): ?>
    <h2>个人简介</h2>
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

    <?php if (!empty($edu)): ?>
    <h2>教育背景</h2>
    <?php foreach ($edu as $e): ?>
    <div class="edu-item">
      <div class="item-hd"><span class="name"><?= htmlspecialchars($e['school'] ?? '') ?></span><span class="date"><?= htmlspecialchars(($e['start'] ?? '') . ' - ' . ($e['end'] ?? '')) ?></span></div>
      <div class="item-hd"><span class="sub"><?= htmlspecialchars(($e['major'] ?? '') . ($e['degree'] ? ' | ' . $e['degree'] : '')) ?></span></div>
      <?php if (!empty($e['desc'])): ?><ul class="desc"><?php foreach (explode("\n", trim($e['desc'])) as $li): if (trim($li)): ?><li><?= htmlspecialchars(trim($li)) ?></li><?php endif; endforeach; ?></ul><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($projects)): ?>
    <h2>项目经验</h2>
    <?php foreach ($projects as $p): ?>
    <div class="proj-item">
      <div class="item-hd"><span class="name"><?= htmlspecialchars($p['name'] ?? '') ?><?php if (!empty($p['url'])): ?> <a href="<?= htmlspecialchars($p['url']) ?>" target="_blank" class="print-hide" style="font-size:12px;color:#38bdf8;text-decoration:none">🔗</a><?php endif; ?></span><span class="sub"><?= htmlspecialchars($p['role'] ?? '') ?></span></div>
      <?php if (!empty($p['desc'])): ?><ul class="desc"><?php foreach (explode("\n", trim($p['desc'])) as $li): if (trim($li)): ?><li><?= htmlspecialchars(trim($li)) ?></li><?php endif; endforeach; ?></ul><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
