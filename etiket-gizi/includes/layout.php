<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

function initials(string $n): string {
    $p = preg_split('/\s+/', trim($n)) ?: [];
    $s = '';
    foreach (array_slice($p, 0, 2) as $w) $s .= mb_strtoupper(mb_substr($w, 0, 1));
    return $s ?: '?';
}

/** Membuka halaman terautentikasi: cek login + izin peran, lalu cetak kerangka (sidebar + topbar). */
function page_start(string $slug, string $judul, string $sub = '', array $o = []): void {
    $u = user();
    if (!$u) redirect('login.php');
    if (!boleh($slug)) { $_SESSION['flash'] = ['err', 'Peran Anda tidak memiliki akses ke halaman tersebut.']; redirect('index.php'); }
    $mend = (int)q("SELECT COUNT(*) FROM pesanan_diet WHERE diet_mendadak=1 AND status<>'selesai'")->fetchColumn();
    $jsvars = ['base' => base_url(), 'csrf' => csrf_token(), 'peran' => $u['peran'], 'workerMode' => cfg('worker_mode'), 'rs' => cfg('app', 'rs')];
    ?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($judul) ?> · <?= e(cfg('app', 'nama')) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='9' fill='%230E7C63'/%3E%3Cpath d='M9 16l5 5 9-10' stroke='%23fff' stroke-width='3' fill='none' stroke-linecap='round'/%3E%3C/svg%3E">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<script>window.APP = <?= json_encode($jsvars) ?>;</script>
</head>
<body>
<aside class="sidebar" id="sidebar">
  <div class="brand"><div class="brand-mark"><?= icon('qr', 22) ?></div><div><b><?= e(cfg('app', 'nama')) ?></b><small>Sistem Etiket Gizi Rawat Inap</small></div></div>
  <nav class="nav">
    <div class="nav-label">Menu</div>
    <?php foreach (menu_untuk($u['peran']) as [$s, $label, $ico, $file]): ?>
      <a href="<?= e(url($file)) ?>" class="<?= $s === $slug ? 'active' : '' ?>"><?= icon($ico, 19) ?><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <div class="side-card"><b><?= e(peran_label($u['peran'])) ?></b><span>Data pada sistem ini adalah data dummy untuk pengujian.</span></div>
    <a class="logout" href="<?= e(url('logout.php')) ?>"><?= icon('logout', 19) ?><span>Keluar</span></a>
  </div>
</aside>
<div class="main">
  <header class="topbar">
    <button class="icon-btn burger" id="burger" aria-label="Menu"><?= icon('menu', 20) ?></button>
    <div class="hospital"><i><?= icon('leaf', 17) ?></i><span><?= e(cfg('app', 'rs')) ?></span></div>
    <div class="spacer"></div>
    <a class="icon-btn" href="<?= e(boleh('pesanan') ? url('pesanan.php?filter=mendadak') : url('index.php')) ?>" title="<?= $mend ?> pesanan diet mendadak belum selesai"><?= icon('bell', 19) ?><?php if ($mend): ?><span class="dot"></span><?php endif; ?></a>
    <div class="user-chip"><span class="avatar"><?= e(initials($u['nama'])) ?></span><div><b><?= e($u['nama']) ?></b><small><?= e(peran_label($u['peran'])) ?></small></div></div>
  </header>
  <main class="content">
    <div class="page-head reveal">
      <div><h1><?= e($judul) ?></h1><?php if ($sub): ?><p><?= e($sub) ?></p><?php endif; ?></div>
      <div class="head-actions"><?= $o['actions'] ?? '' ?></div>
    </div>
<?php
}

function page_end(array $scripts = [], array $vendor = []): void {
    $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
    ?>
  </main>
</div>
<div id="toasts" aria-live="polite"></div>
<?php foreach ($vendor as $v): ?><script src="<?= e(asset('vendor/' . $v)) ?>"></script><?php endforeach; ?>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php foreach ($scripts as $s): ?><script src="<?= e(asset('js/' . $s)) ?>"></script><?php endforeach; ?>
<?php if ($flash): ?><script>toast(<?= json_encode($flash[1], JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($flash[0]) ?>);</script><?php endif; ?>
</body>
</html>
<?php
}
