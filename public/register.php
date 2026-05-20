<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/csrf.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $username = trim($_POST['username'] ?? '');
  $display = trim($_POST['display_name'] ?? '');
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password2'] ?? '';
  if ($username === '' || $password === '' || $display === '') {
    $error = 'Veuillez remplir tous les champs.';
  } elseif ($password !== $password2) {
    $error = 'Les mots de passe ne correspondent pas.';
  } else {
    // check username exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
      $error = 'Nom d\'utilisateur déjà utilisé.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $ins = $pdo->prepare('INSERT INTO users (username, display_name, password_hash, role, created_at) VALUES (?,?,?,?,NOW())');
      $ins->execute([$username, $display, $hash, 'student']);
      $newId = $pdo->lastInsertId();
      // auto-login
      $_SESSION['user_id'] = $newId;
      header('Location: /student/index.php'); exit;
    }
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>S'inscrire</title>
<link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script>
</head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">
  <section class="hero card login-hero" style="align-items:center;position:relative;overflow:visible;padding:28px;">
    <div style="flex:1;max-width:640px">
      <h1 style="font-size:34px;margin:0 0 6px">Créer un compte étudiant</h1>
      <p class="muted">Inscrivez-vous pour suivre les leçons et progresser.</p>
      <?php if ($error): ?><p style="color:var(--color-danger);"><?=htmlspecialchars($error)?></p><?php endif; ?>

      <div class="card login-card" style="max-width:520px;margin-top:18px;">
        <form method="post" aria-label="Register form">
          <?= csrf_field() ?>
          <div class="form-group">
            <label>Nom d'utilisateur</label>
            <input class="form-control" name="username" required placeholder="ex: student1" value="<?=htmlspecialchars($_POST['username'] ?? '')?>">
          </div>
          <div class="form-group">
            <label>Nom affiché</label>
            <input class="form-control" name="display_name" required placeholder="ex: Jeanne" value="<?=htmlspecialchars($_POST['display_name'] ?? '')?>">
          </div>
          <div class="form-group">
            <label>Mot de passe</label>
            <input class="form-control" name="password" type="password" required placeholder="mot de passe">
          </div>
          <div class="form-group">
            <label>Confirmer mot de passe</label>
            <input class="form-control" name="password2" type="password" required placeholder="confirmer mot de passe">
          </div>

          <div style="display:flex;gap:12px;align-items:center;margin-top:12px;">
            <button class="btn" type="submit">S'inscrire</button>
            <a class="btn secondary" href="/login.php">Se connecter</a>
          </div>
        </form>
      </div>
    </div>

    <div class="illustration login-illustration" aria-hidden="true">
      <div class="robot-wrap">
        <!-- reuse same robot SVG as login -->
        <svg class="robot" width="160" height="160" viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <defs>
            <linearGradient id="g1b" x1="0" x2="1">
              <stop offset="0" stop-color="#ffd166" />
              <stop offset="1" stop-color="#06b6d4" />
            </linearGradient>
          </defs>
          <rect x="16" y="36" width="128" height="84" rx="12" fill="#fff" stroke="#e6e9ef" />
          <g class="book" transform="translate(28,86)">
            <rect x="0" y="0" width="54" height="30" rx="4" fill="#fff8e6" stroke="#ffd166"/>
            <path d="M2 4h50" stroke="#ffd166" stroke-width="2"/>
          </g>
          <g class="head" transform="translate(48,18)">
            <rect x="0" y="0" width="64" height="48" rx="10" fill="url(#g1b)" />
            <circle cx="20" cy="22" r="6" fill="#fff" />
            <circle cx="44" cy="22" r="6" fill="#fff" />
            <rect x="22" y="34" width="20" height="6" rx="3" fill="#fff" />
          </g>
        </svg>
        <div class="robot-caption">Bienvenue à bord !</div>
      </div>
    </div>

    <div class="decor decor-lightbulb" aria-hidden="true"></div>
    <div class="decor decor-star" aria-hidden="true"></div>
  </section>
</main>
<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</body></html>
