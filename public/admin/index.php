<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('admin');
verify_csrf();

// handle role change or enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['change_role']) && !empty($_POST['user_id'])) {
    $uid = (int)$_POST['user_id']; $new = $_POST['role'];
    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$new,$uid]);
  }
  if (!empty($_POST['enroll_user']) && !empty($_POST['user_id']) && !empty($_POST['module_id'])) {
    $uid = (int)$_POST['user_id']; $mid = (int)$_POST['module_id'];
    $exists = $pdo->prepare('SELECT id FROM user_courses WHERE user_id=? AND module_id=?'); $exists->execute([$uid,$mid]);
    if (!$exists->fetch()) $pdo->prepare('INSERT INTO user_courses (user_id,module_id) VALUES (?,?)')->execute([$uid,$mid]);
  }
}

$users = $pdo->query('SELECT id,username,display_name,role,created_at FROM users ORDER BY created_at DESC')->fetchAll();
$modules = $pdo->query('SELECT id,title FROM modules ORDER BY `order`')->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Administration</title><link rel="stylesheet" href="/assets/css/style.css"></head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <h1>Administration</h1>
  <div class="card" style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
    <div>
      <h3>Administration rapide</h3>
      <p class="muted">Actions courantes pour gérer le contenu et les utilisateurs.</p>
    </div>
    <div style="display:flex;gap:8px">
      <a class="btn" href="/admin/new_module.php">Créer module</a>
      <a class="btn" href="/admin/new_unit.php">Créer unité</a>
      <a class="btn" href="/admin/new_exercise.php">Créer exercice</a>
    </div>
  </div>

  <div class="card">
    <h3>Utilisateurs</h3>
    <table class="table">
      <thead><tr><th>ID</th><th>Pseudo</th><th>Nom</th><th>Rôle</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
        <tr>
          <td><?=htmlspecialchars($u['id'])?></td>
          <td><?=htmlspecialchars($u['username'])?></td>
          <td><?=htmlspecialchars($u['display_name'])?></td>
          <td><?=htmlspecialchars($u['role'])?></td>
          <td>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="role"><option value="student">student</option><option value="teacher">teacher</option><option value="admin">admin</option></select>
              <button class="btn" name="change_role" value="1">Modifier</button>
            </form>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="module_id"><?php foreach($modules as $m): ?><option value="<?= $m['id'] ?>"><?=htmlspecialchars($m['title'])?></option><?php endforeach; ?></select>
              <button class="btn secondary" name="enroll_user" value="1">Inscrire</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  
  <div class="card" style="margin-top:12px">
    <h3>Modules</h3>
    <div class="grid">
    <?php foreach ($modules as $m): ?>
      <div class="lesson-card card">
        <h3><?=htmlspecialchars($m['title'])?></h3>
        <?php
          $stmt = $pdo->prepare('SELECT * FROM media WHERE module_id = ?'); $stmt->execute([$m['id']]); $files = $stmt->fetchAll();
          if ($files) {
            echo '<ul>'; foreach($files as $f) { echo '<li><a href="'.htmlspecialchars($f['path']).'">'.htmlspecialchars($f['filename']).'</a> uploaded at '.htmlspecialchars($f['uploaded_at']).'</li>'; } echo '</ul>';
          }
        ?>
        <div style="margin-top:8px">
          <a class="btn" href="/admin/new_unit.php?module_id=<?= $m['id'] ?>">Ajouter unité</a>
          <a class="btn secondary" href="/admin/new_exercise.php">Ajouter exercice</a>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>
