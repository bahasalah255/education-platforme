<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('teacher');
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
    // handle cover image upload
    $cover_path = null;
    if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
      $updir = __DIR__.'/../../public/uploads/modules';
      if (!is_dir($updir)) mkdir($updir, 0755, true);
      $fname = time().'_'.preg_replace('/[^a-z0-9.\-_]/i','',basename($_FILES['cover_image']['name']));
      $dst = $updir.'/'.$fname;
      if (move_uploaded_file($_FILES['cover_image']['tmp_name'],$dst)) {
        $cover_path = '/uploads/modules/'.$fname;
      }
    }

    $stmt = $pdo->prepare('INSERT INTO modules (title, description, `order`, created_by, cover_image) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$title, $desc, $order, $_SESSION['user_id'], $cover_path]);
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
  <a class="btn ghost" href="/teacher/index.php">Retour</a>
  <h1>Ajouter un module</h1>
  <div style="display:flex;gap:20px;align-items:flex-start">
    <div class="card" style="flex:1;max-width:740px">
      <?php if ($error): ?><p style="color:var(--color-danger)"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <?php if ($message): ?><p style="color:var(--color-success)"><?=htmlspecialchars($message)?></p><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Titre</label>
          <input class="form-control" name="title" required placeholder="Ex: Articles définis">
        </div>

        <div class="form-group">
          <label>Image d'affiche</label>
          <input class="form-control" name="cover_image" type="file" accept="image/*">
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea class="form-control" name="description" rows="4" placeholder="Courte description"></textarea>
        </div>

        <div class="form-group">
          <label>Ordre (facultatif)</label>
          <input class="form-control" name="order" type="number" min="0" value="0">
        </div>

        <fieldset style="margin-top:12px;padding:12px;border-radius:10px;border:1px solid rgba(15,23,42,0.04);">
          <legend style="font-weight:700">Inscrire des élèves</legend>
          <div style="margin-top:8px">
            <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="enroll_all" value="1"> Inscrire tous les élèves</label>
          </div>
          <div style="margin-top:8px">
            <label style="display:block;margin-bottom:6px">Sélectionnez les élèves à inscrire (Ctrl/Cmd pour multi-sélection):</label>
            <select class="form-control" name="enroll_students[]" multiple size="6" style="min-width:320px;">
              <?php foreach($students as $st): ?>
                <option value="<?=htmlspecialchars($st['id'])?>"><?=htmlspecialchars($st['display_name'].' ('.$st['username'].')')?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </fieldset>

        <div style="margin-top:12px;display:flex;gap:8px">
          <button class="btn" type="submit">Créer le module</button>
          <a class="btn secondary" href="/teacher/index.php">Annuler</a>
        </div>
      </form>
    </div>

    <aside style="width:300px">
      <div class="card" style="text-align:center;padding:16px">
        <svg width="120" height="120" viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
          <defs><linearGradient id="g2" x1="0" x2="1"><stop offset="0" stop-color="#ffd166"/><stop offset="1" stop-color="#06b6d4"/></linearGradient></defs>
          <rect x="20" y="36" width="120" height="68" rx="10" fill="url(#g2)" />
          <circle cx="60" cy="70" r="8" fill="#fff" />
          <circle cx="100" cy="70" r="8" fill="#fff" />
        </svg>
        <h4 style="margin-top:10px">Astuce</h4>
        <p class="muted" style="font-size:14px">Ajoutez une image de couverture claire pour rendre votre module attractif.</p>
      </div>
    </aside>
  </div>
</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>
