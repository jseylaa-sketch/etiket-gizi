<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/stiker.php';
if (user()) redirect('index.php');
$err = ''; $isi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isi = trim((string)($_POST['username'] ?? ''));
    if (!csrf_ok()) $err = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    elseif (login($isi, (string)($_POST['password'] ?? ''))) redirect('index.php');
    else { usleep(400000); $err = 'Username atau kata sandi salah.'; }
}
$tok = csrf_token();
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk · <?= e(cfg('app', 'nama')) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='9' fill='%230E7C63'/%3E%3Cpath d='M9 16l5 5 9-10' stroke='%23fff' stroke-width='3' fill='none' stroke-linecap='round'/%3E%3C/svg%3E">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="login-page">
<section class="login-hero">
  <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
  <div class="brand"><div class="brand-mark"><?= icon('qr', 22) ?></div><div><b><?= e(cfg('app', 'nama')) ?></b><small><?= e(cfg('app', 'rs')) ?></small></div></div>
  <h1>Etiket gizi yang <em>tepat</em>, cepat, dan terlacak.</h1>
  <p class="lead">Ambil data pasien dari SIMRS, buat stiker ber-QR Code lewat antrean spooling, lalu pantau distribusi makanan sampai diterima pasien.</p>
  <div class="feat">
    <div><i><?= icon('refresh', 16) ?></i>Data diambil otomatis dari SIMRS, tanpa penyalinan ganda</div>
    <div><i><?= icon('bolt', 16) ?></i>Antrean prioritas untuk perubahan diet mendadak</div>
    <div><i><?= icon('qr', 16) ?></i>QR Code berisi ID pesanan, tanpa data identitas pasien</div>
  </div>
  <div class="float-stk">
    <div class="stk">
      <div class="hd">INSTALASI GIZI | <?= e(strtoupper(cfg('app', 'rs'))) ?></div>
      <dl><dt>Nama</dt><dd>Pasien Contoh 03</dd><dt>No. RM</dt><dd>RM-000123</dd><dt>Kamar</dt><dd>Melati 2A</dd><dt>Diet</dt><dd>Diet Lunak</dd></dl>
      <div class="qr"><?= qr_svg('ETK-20261022-0003', 120) ?><div>ETK-20261022-0003</div></div>
      <div class="badge">DIET MENDADAK</div><div class="ft">Stiker contoh</div>
    </div>
  </div>
</section>
<section class="login-side">
  <form class="login-card" method="post" autocomplete="on">
    <h2>Selamat Datang</h2>
    <p class="sub">Silakan masuk untuk melanjutkan.</p>
    <?php if ($err): ?><div class="alert-err"><?= e($err) ?></div><?php endif; ?>
    <input type="hidden" name="_csrf" value="<?= e($tok) ?>">
    <div class="field"><label for="u">Username</label>
      <div class="inwrap"><?= icon('user', 19) ?><input class="input" id="u" name="username" value="<?= e($isi) ?>" placeholder="Masukkan username" required autofocus></div></div>
    <div class="field"><label for="p">Kata sandi</label>
      <div class="inwrap"><?= icon('lock', 19) ?><input class="input" id="p" type="password" name="password" placeholder="Masukkan kata sandi" required>
        <button class="eye" type="button" id="eye" aria-label="Tampilkan kata sandi"><?= icon('eye', 18) ?></button></div></div>
    <button class="btn btn-primary btn-lg" style="width:100%;margin-top:6px" id="go"><span>Masuk</span><?= icon('arrow', 18) ?></button>
    <div class="demo"><b>Akun uji (data dummy)</b> · kata sandi: <b>gizi12345</b>
      <button type="button" data-u="gizi">gizi <span>Ahli Gizi</span></button>
      <button type="button" data-u="perawat">perawat <span>Dokter/Perawat</span></button>
      <button type="button" data-u="distribusi">distribusi <span>Petugas Distribusi</span></button></div>
  </form>
</section>
<script>
const $=s=>document.querySelector(s);
$('#eye').onclick=()=>{const p=$('#p');p.type=p.type==='password'?'text':'password';};
document.querySelectorAll('.demo button').forEach(b=>b.onclick=()=>{$('#u').value=b.dataset.u;$('#p').value='gizi12345';$('#p').focus();});
$('.login-card').addEventListener('submit',()=>{const g=$('#go');g.disabled=true;g.innerHTML='<span class="spin"></span><span>Memeriksa...</span>';});
</script>
</body>
</html>
