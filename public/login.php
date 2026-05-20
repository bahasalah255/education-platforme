<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/csrf.php';
verify_csrf();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = $_POST['username'] ?? '';
  $p = $_POST['password'] ?? '';
  if (login_user($u, $p)) {
    // Redirect to role-specific dashboard
    $cu = current_user();
    if ($cu && isset($cu['role'])) {
      if ($cu['role'] === 'teacher') { header('Location: /teacher/index.php'); exit; }
      if ($cu['role'] === 'admin') { header('Location: /admin/index.php'); exit; }
      // default for students and others
      header('Location: /student/index.php'); exit;
    }
    header('Location: /'); exit;
  } else {
    $error = 'Invalid credentials';
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Se connecter</title>
<link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script>
</head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">
  <section class="hero card" style="align-items:center;">
    <div>
      <h1>Bienvenue</h1>
      <p class="lead">Connectez-vous pour commencer votre apprentissage des articles définis.</p>
      <?php if ($error): ?><p style="color:var(--danger)"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <div class="card" style="max-width:420px;margin-top:12px;">
        <form method="post" aria-label="Login form">
          <?= csrf_field() ?>
          <label>Nom d'utilisateur
            <input name="username" required placeholder="ex: student1">
          </label>
          <label>Mot de passe
            <input name="password" type="password" required placeholder="votre mot de passe">
          </label>
          <div style="display:flex;gap:8px;align-items:center;margin-top:8px;">
            <button class="btn" type="submit">Se connecter</button>
            <a class="btn secondary" href="/">Retour</a>
          </div>
        </form>
      </div>
    </div>
    <div class="illustration">
      <div style="text-align:center">
        <div class="mascot">😊</div>
        <p class="muted">Apprends en t'amusant !</p>
      </div>
    </div>
  </section>
</main>
<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</body></html>
