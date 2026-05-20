<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('admin');
verify_csrf();

$error = '';
$message = '';
// load students for optional enrollment
$students = $pdo->query("SELECT id,username,display_name FROM users WHERE role='student' ORDER BY display_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $order = (int)($_POST['order'] ?? 0);
  $enroll_all = !empty($_POST['enroll_all']);
  $enroll_students = $_POST['enroll_students'] ?? [];
  if ($title === '') { $error = 'Le titre est requis.'; }
  else {
    $stmt = $pdo->prepare('INSERT INTO modules (title, description, `order`) VALUES (?, ?, ?)');
    $stmt->execute([$title, $desc, $order]);
    $module_id = $pdo->lastInsertId();
    // auto-create a Pretest unit for this module
    $u = $pdo->prepare('INSERT INTO units (module_id, title, content_html, `order`) VALUES (?,?,?,?)');
    $u->execute([$module_id, 'Pretest', '<p>Pretest for module</p>', 0]);
    $enrolled = 0;
    if ($enroll_all) {
      $allStudents = $pdo->query("SELECT id FROM users WHERE role='student'")->fetchAll();
      $ins = $pdo->prepare('INSERT INTO user_courses (user_id, module_id) VALUES (?,?)');
      foreach ($allStudents as $st) {
        $chk = $pdo->prepare('SELECT id FROM user_courses WHERE user_id = ? AND module_id = ?');
        $chk->execute([$st['id'], $module_id]);
        if (!$chk->fetch()) { $ins->execute([$st['id'], $module_id]); $enrolled++; }
      }
    } else {
      $ins = $pdo->prepare('INSERT INTO user_courses (user_id, module_id) VALUES (?,?)');
      $chk = $pdo->prepare('SELECT id FROM user_courses WHERE user_id = ? AND module_id = ?');
      foreach ($enroll_students as $sid) {
        $sid = (int)$sid;
        $chk->execute([$sid, $module_id]);
        if (!$chk->fetch()) { $ins->execute([$sid, $module_id]); $enrolled++; }
      }
    }
    $message = 'Module et unité Pretest créés.' . ($enrolled ? " {$enrolled} élève(s) inscrits." : '');
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Ajouter un module</title>
<link rel="stylesheet" href="/assets/css/style.css">
<script>document.addEventListener('DOMContentLoaded',function(){var chk=document.querySelector('input[name=enroll_all]'); if (chk) chk.addEventListener('change',function(){ document.querySelector('select[name="enroll_students[]"]').disabled = this.checked; }); });</script>
</head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <a class="btn ghost" href="/admin/index.php">Retour</a>
  <h1>Ajouter un module</h1>
  <div class="card" style="max-width:820px">
    <?php if ($error): ?><p style="color:var(--color-danger)"><?=htmlspecialchars($error)?></p><?php endif; ?>
    <?php if ($message): ?><p style="color:var(--color-success)"><?=htmlspecialchars($message)?></p><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Titre
        <input name="title" required placeholder="Ex: Articles définis">
      </label>
      <label>Description
        <textarea name="description" rows="4" placeholder="Courte description"></textarea>
      </label>
      <label>Ordre (facultatif)
        <input name="order" type="number" min="0" value="0">
      </label>
      <fieldset style="margin-top:12px">
        <legend>Inscrire des élèves</legend>
        <label><input type="checkbox" name="enroll_all" value="1"> Inscrire tous les élèves</label>
        <label style="display:block;margin-top:8px">Sélectionnez les élèves à inscrire (Ctrl/Cmd pour multi-sélection):</label>
        <select name="enroll_students[]" multiple size="6" style="min-width:320px;">
          <?php foreach($students as $st): ?>
            <option value="<?=htmlspecialchars($st['id'])?>"><?=htmlspecialchars($st['display_name'].' ('.$st['username'].')')?></option>
          <?php endforeach; ?>
        </select>
      </fieldset>
      <div style="margin-top:12px;display:flex;gap:8px">
        <button class="btn" type="submit">Créer le module</button>
        <a class="btn secondary" href="/admin/index.php">Annuler</a>
      </div>
    </form>
  </div>
</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>
