<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('teacher');
verify_csrf();

$error = '';
$units = $pdo->query('SELECT u.id, m.title AS module_title, u.title AS unit_title FROM units u JOIN modules m ON m.id=u.module_id ORDER BY m.`order`, u.`order`')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $unit_id = (int)($_POST['unit_id'] ?? 0);
  $type = $_POST['type'] ?? 'mcq';
  $points = (int)($_POST['points'] ?? 1);
  $order = (int)($_POST['order'] ?? 0);
  $prompt = trim($_POST['prompt'] ?? '');

  if ($unit_id <= 0 || $prompt === '') { $error = 'Unité et question requis.'; }
  else {
    if ($type === 'mcq') {
      $choices = [];
      for ($i=1;$i<=4;$i++){
        $text = trim($_POST["choice_$i"] ?? '');
        if ($text==='') continue;
        $is_correct = (isset($_POST['correct_choice']) && (int)$_POST['correct_choice'] === $i) ? 1 : 0;
        $choices[] = ['text'=>$text,'correct'=>$is_correct];
      }
      $data = ['prompt'=>$prompt,'choices'=>$choices];
    } else {
      $data = ['prompt'=>$prompt];
    }
    $stmt = $pdo->prepare('INSERT INTO exercises (unit_id, type, data, points, `order`) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$unit_id, $type, json_encode($data, JSON_UNESCAPED_UNICODE), $points, $order]);
    header('Location: /teacher/index.php'); exit;
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Ajouter un exercice</title>
<link rel="stylesheet" href="/assets/css/redesign.css">
<link rel="stylesheet" href="/assets/css/style.css">
<script>
function toggleTypeFields(){
  var t = document.getElementById('type').value;
  document.getElementById('mcqFields').style.display = t==='mcq' ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded',function(){ toggleTypeFields(); });
</script>
</head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <a class="btn ghost" href="/teacher/index.php">Retour</a>
  <h1>Ajouter un exercice</h1>

  <div style="display:flex;gap:20px;align-items:flex-start">
    <div class="card" style="flex:1;max-width:780px">
      <?php if ($error): ?><p style="color:var(--rd-danger)"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Unité</label>
          <select class="form-control" name="unit_id" required>
            <option value="">-- choisir --</option>
            <?php foreach($units as $u): ?>
              <option value="<?= $u['id'] ?>"><?=htmlspecialchars($u['module_title'].' — '.$u['unit_title'])?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Type d'exercice</label>
          <select id="type" class="form-control" name="type" onchange="toggleTypeFields()"><option value="mcq">QCM</option><option value="pretest">Pretest</option><option value="posttest">Posttest</option><option value="prereq">Prerequis</option></select>
        </div>

        <div class="form-group">
          <label>Question / Énoncé</label>
          <textarea class="form-control" name="prompt" rows="3" required placeholder="Énoncé de la question"></textarea>
        </div>

        <div id="mcqFields" style="margin-top:8px">
          <h4>Choix (QCM)</h4>
          <?php for($i=1;$i<=4;$i++): ?>
            <div class="form-group">
              <label>Choix <?= $i ?></label>
              <input class="form-control" name="choice_<?= $i ?>" placeholder="Texte du choix <?= $i ?>">
            </div>
          <?php endfor; ?>
          <div class="form-group">
            <label>Indice du choix correct (1-4)</label>
            <input class="form-control" type="number" name="correct_choice" min="1" max="4" value="4">
          </div>
        </div>

        <div class="form-group">
          <label>Points</label>
          <input class="form-control" name="points" type="number" min="0" value="1">
        </div>
        <div class="form-group">
          <label>Ordre</label>
          <input class="form-control" name="order" type="number" min="0" value="0">
        </div>

        <div style="margin-top:12px;display:flex;gap:8px">
          <button class="btn" type="submit">Créer l'exercice</button>
          <a class="btn secondary" href="/teacher/index.php">Annuler</a>
        </div>
      </form>
    </div>

    <aside style="width:300px">
      <div class="card" style="text-align:center;padding:16px">
        <svg width="120" height="120" viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
          <defs><linearGradient id="g-ex" x1="0" x2="1"><stop offset="0" stop-color="#ffd166"/><stop offset="1" stop-color="#06b6d4"/></linearGradient></defs>
          <rect x="20" y="36" width="120" height="68" rx="10" fill="url(#g-ex)" />
          <circle cx="60" cy="70" r="8" fill="#fff" />
          <circle cx="100" cy="70" r="8" fill="#fff" />
        </svg>
        <h4 style="margin-top:10px">Conseil</h4>
        <p class="muted" style="font-size:14px">Pour les QCM, fournissez des choix clairs et un seul choix correct.</p>
      </div>
    </aside>
  </div>
</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>
