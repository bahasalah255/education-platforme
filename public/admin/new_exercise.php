<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('admin');
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
      // For other types allow raw JSON (fallback) or simple prompt wrapper
      $data = ['prompt'=>$prompt];
    }
    $stmt = $pdo->prepare('INSERT INTO exercises (unit_id, type, data, points, `order`) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$unit_id, $type, json_encode($data, JSON_UNESCAPED_UNICODE), $points, $order]);
    header('Location: /admin/index.php'); exit;
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Ajouter un exercice</title>
<link rel="stylesheet" href="/assets/css/style.css">
<script>
function toggleTypeFields(){
  var t = document.getElementById('type').value;
  document.getElementById('mcqFields').style.display = t==='mcq' ? 'block' : 'none';
}
</script>
</head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <a class="btn ghost" href="/admin/index.php">Retour</a>
  <h1>Ajouter un exercice</h1>
  <div class="card" style="max-width:920px">
    <?php if ($error): ?><p style="color:var(--color-danger)"><?=htmlspecialchars($error)?></p><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Unité
        <select name="unit_id" required>
          <option value="">-- choisir --</option>
          <?php foreach($units as $u): ?>
            <option value="<?= $u['id'] ?>"><?=htmlspecialchars($u['module_title'].' — '.$u['unit_title'])?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Type d'exercice
        <select id="type" name="type" onchange="toggleTypeFields()"><option value="mcq">QCM</option><option value="pretest">Pretest</option><option value="posttest">Posttest</option><option value="prereq">Prerequis</option></select>
      </label>
      <label>Question / Énoncé
        <textarea name="prompt" rows="3" required placeholder="Énoncé de la question"></textarea>
      </label>

      <div id="mcqFields" style="margin-top:8px">
        <h4>Choix (QCM)</h4>
        <?php for($i=1;$i<=4;$i++): ?>
          <label>Choix <?= $i ?> <input name="choice_<?= $i ?>" placeholder="Texte du choix <?= $i ?>"></label>
        <?php endfor; ?>
        <label>Indice du choix correct (1-4)
          <input type="number" name="correct_choice" min="1" max="4" value="4">
        </label>
      </div>

      <label>Points
        <input name="points" type="number" min="0" value="1">
      </label>
      <label>Ordre
        <input name="order" type="number" min="0" value="0">
      </label>
      <div style="margin-top:12px;display:flex;gap:8px">
        <button class="btn" type="submit">Créer l'exercice</button>
        <a class="btn secondary" href="/admin/index.php">Annuler</a>
      </div>
    </form>
  </div>
</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>
