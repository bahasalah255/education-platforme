<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/csrf.php';
require_login();
verify_csrf();

$module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
if ($module_id <= 0) { echo 'Module not specified'; exit; }

// find units of module
$stmt = $pdo->prepare('SELECT id FROM units WHERE module_id = ? ORDER BY `order`');
$stmt->execute([$module_id]);
$unit_ids = array_column($stmt->fetchAll(), 'id');
if (empty($unit_ids)) { echo 'No units for this module'; exit; }

// collect pretest exercises belonging to units of this module
$in = implode(',', array_fill(0,count($unit_ids),'?'));
$sql = "SELECT * FROM exercises WHERE type='pretest' AND unit_id IN ($in) ORDER BY `order`";
$stmt = $pdo->prepare($sql);
$stmt->execute($unit_ids);
$exercises = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['exercise_id'])) {
  // reuse grading logic from lesson.php
  $exercise_id = (int)$_POST['exercise_id'];
  $stmt = $pdo->prepare('SELECT * FROM exercises WHERE id = ?');
  $stmt->execute([$exercise_id]);
  $ex = $stmt->fetch();
  $score = 0; $max = 0;
  if ($ex) {
    $data = json_decode($ex['data'], true);
    foreach ($data as $q) {
      $max += 1;
      $selected = $_POST['choice'] ?? null;
      $correct = null;
      foreach ($q['choices'] as $c) { if (!empty($c['correct'])) $correct = $c['text']; }
      if ($selected !== null && $selected === $correct) $score += 1;
    }
    // Save attempt
    $ins = $pdo->prepare('INSERT INTO attempts (user_id, exercise_id, score, max_score, data) VALUES (?,?,?,?,?)');
    $user_id = $_SESSION['user_id'] ?? null;
    $ins->execute([$user_id, $exercise_id, $score, $max, json_encode($_POST)]);
    // compute percent
    $percent = $max>0 ? round(100*$score/$max) : 0;
    if ($percent >= 80) {
      // mark all units in module as completed for user
      foreach ($unit_ids as $uid) {
        $up = $pdo->prepare('SELECT id FROM progress WHERE user_id=? AND unit_id=?');
        $up->execute([$user_id, $uid]); $exst = $up->fetch();
        if ($exst) {
          $stmt2 = $pdo->prepare('UPDATE progress SET score=?, max_score=?, needs_remediation=0 WHERE id=?');
          $stmt2->execute([$max, $max, $exst['id']]);
        } else {
          $stmt2 = $pdo->prepare('INSERT INTO progress (user_id, unit_id, score, max_score, needs_remediation) VALUES (?,?,?,?,0)');
          $stmt2->execute([$user_id, $uid, $max, $max]);
        }
      }
      // award points
      $points_awarded = ($score * 10);
      $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?')->execute([$points_awarded, $user_id]);
      echo "Pretest passed ({$percent}%). You have been awarded {$points_awarded} points. You may skip the module.";
      exit;
    } else {
      echo "Pretest not passed ({$percent}%). Please follow the module units.";
      header('Location: /lesson.php?unit_id='.$unit_ids[0]); exit;
    }
  }
}

?><!doctype html>
<html><head><meta charset="utf-8"><title>Pretest</title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">
  <h1>Pretest for module <?=htmlspecialchars($module_id)?></h1>
<?php foreach($exercises as $ex): $edata=json_decode($ex['data'],true); foreach($edata as $q): ?>
  <form method="post" class="card exercise">
    <?= csrf_field() ?>
    <h3><?=htmlspecialchars($q['prompt'])?></h3>
    <div class="choices">
    <?php foreach($q['choices'] as $c): ?>
      <label><input type="radio" name="choice" value="<?=htmlspecialchars($c['text'])?>"> <?=htmlspecialchars($c['text'])?></label>
    <?php endforeach; ?>
    </div>
    <input type="hidden" name="exercise_id" value="<?= $ex['id'] ?>">
    <div style="margin-top:8px"><button class="btn">Soumettre</button></div>
  </form>
<?php endforeach; endforeach; ?>
<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</main>
</body></html>
