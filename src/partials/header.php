<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$user = null;
if (!empty($_SESSION['user_id'])){
  $user = current_user();
}
?>
<div class="site-header card" role="banner">
  <div class="site-brand">
    <div class="brand-logo">A</div>
    <div>
      <div class="brand-title">Apprends les Articles</div>
      <div class="muted">Amusant • Simple • Rapide</div>
    </div>
  </div>
  <div class="header-actions">
    <?php if ($user): ?>
      <span class="muted">Bonjour, <?=htmlspecialchars($user['display_name'] ?? $user['username'])?></span>
      <?php if ($user['role'] === 'teacher'): ?><a class="btn secondary" href="/teacher/index.php">Tableau Prof</a><?php endif; ?>
      <?php if ($user['role'] === 'admin'): ?><a class="btn secondary" href="/admin/index.php">Admin</a><?php endif; ?>
      <a class="btn" href="/logout.php">Logout</a>
    <?php else: ?>
      <a class="btn secondary" href="/login.php">Se connecter</a>
    <?php endif; ?>
    <button id="toggleTheme" class="btn ghost" title="Basculer thème">🌗</button>
  </div>
</div>
