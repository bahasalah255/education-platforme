<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/csrf.php';
// Simple unit+exercise renderer and grader
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 1;
$stmt = $pdo->prepare('SELECT u.*, m.title AS module_title FROM units u JOIN modules m ON m.id=u.module_id WHERE u.id = ?');
$stmt->execute([$unit_id]);
$unit = $stmt->fetch();
if (!$unit) {
  echo 'Unit not found'; exit;
}

// exercises
$stmt = $pdo->prepare('SELECT * FROM exercises WHERE unit_id = ? ORDER BY `order`');
$stmt->execute([$unit_id]);
$exercises = $stmt->fetchAll();

// If remediation mode requested, filter out exercises the user already mastered
$remediation = (!empty($_GET['remediation']) && $_GET['remediation'] == '1');
if ($remediation && !empty($_SESSION['user_id'])) {
  $filtered = [];
  foreach ($exercises as $ex) {
    // get latest attempt for this user/exercise
    $stmt2 = $pdo->prepare('SELECT score, max_score FROM attempts WHERE user_id = ? AND exercise_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt2->execute([$_SESSION['user_id'], $ex['id']]);
    $last = $stmt2->fetch();
    // include exercise if no attempt or last score < max_score
    if (!$last || ((int)$last['score'] < (int)$last['max_score'])) {
      $filtered[] = $ex;
    }
  }
  $exercises = $filtered;
}

$result_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['exercise_id'])) {
  require_once __DIR__.'/../src/csrf.php';
  verify_csrf();
  $exercise_id = (int)$_POST['exercise_id'];
  $stmt = $pdo->prepare('SELECT * FROM exercises WHERE id = ?');
  $stmt->execute([$exercise_id]);
  $ex = $stmt->fetch();
    if ($ex) {
    $data = json_decode($ex['data'], true);
    $score = 0; $max = 0;
    if ($ex['type'] === 'mcq') {
      foreach ($data as $q) {
        $max += 1;
        $selected = $_POST['choice'] ?? null;
        $correct = null;
        foreach ($q['choices'] as $c) { if (!empty($c['correct'])) $correct = $c['text']; }
        if ($selected !== null && $selected === $correct) $score += 1;
      }
    } elseif ($ex['type'] === 'dragdrop') {
      // server-side grading: compare submitted mapping to correct mapping
      $mapping_raw = $_POST['mapping'] ?? '{}';
      $mapping = json_decode($mapping_raw, true);
      if (!is_array($mapping)) $mapping = [];
      // assume single question per exercise for dragdrop
      $q = $data[0] ?? null;
      if ($q) {
        $max = count($q['targets']);
        foreach ($q['targets'] as $t) {
          $tid = (string)$t['id'];
          $correctItem = isset($t['match']) ? (string)$t['match'] : null;
          $submittedItem = isset($mapping[$tid]) ? (string)$mapping[$tid] : null;
          if ($correctItem !== null && $submittedItem !== null && $correctItem === $submittedItem) $score += 1;
        }
      }
    }
    // Save attempt
    $ins = $pdo->prepare('INSERT INTO attempts (user_id, exercise_id, score, max_score, data) VALUES (?,?,?,?,?)');
    $user_id = $_SESSION['user_id'] ?? null;
    $ins->execute([$user_id, $exercise_id, $score, $max, json_encode($_POST)]);
    $result_message = "Score: $score / $max";
    // Update progress table per unit
    $unit_id = $ex['unit_id'];
    // compute percentage
    $percent = $max > 0 ? round(100 * $score / $max) : 0;
    $needs = ($percent < 80) ? 1 : 0;
    $up = $pdo->prepare('SELECT id FROM progress WHERE user_id = ? AND unit_id = ?');
    $up->execute([$user_id, $unit_id]);
    $existing = $up->fetch();
    if ($existing) {
      $stmt2 = $pdo->prepare('UPDATE progress SET score=?, max_score=?, needs_remediation=? WHERE id=?');
      $stmt2->execute([$score, $max, $needs, $existing['id']]);
    } else {
      $stmt2 = $pdo->prepare('INSERT INTO progress (user_id, unit_id, score, max_score, needs_remediation) VALUES (?,?,?,?,?)');
      $stmt2->execute([$user_id, $unit_id, $score, $max, $needs]);
    }
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title><?=htmlspecialchars($unit['title'])?></title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">
  <h1><?=htmlspecialchars($unit['title'])?></h1>
  <div class="content card explain"><?= $unit['content_html'] ?></div>
  <?php if ($result_message): ?><p class="muted"><strong><?=htmlspecialchars($result_message)?></strong></p><?php endif; ?>

  <?php foreach ($exercises as $ex): ?>

<script src="/assets/js/exercises.js"></script>

<?php if ($result_message): ?><p><strong><?=htmlspecialchars($result_message)?></strong></p><?php endif; ?>

<?php foreach ($exercises as $ex): ?>
  <?php $edata = json_decode($ex['data'], true); ?>
  <?php foreach ($edata as $idx => $q): ?>
    <?php if (!empty($q['media'])): ?>
      <?php if (is_string($q['media'])): $mpath = $q['media']; else: $mpath = $q['media']['path'] ?? null; endif; ?>
      <?php if ($mpath): ?>
        <?php $mime = mime_content_type(__DIR__.$mpath) ?? ''; ?>
        <?php if (strpos($mime,'image/')===0): ?>
          <div><img src="<?=htmlspecialchars($mpath)?>" alt="" style="max-width:320px;display:block;margin:8px 0;"></div>
        <?php elseif (strpos($mime,'audio/')===0): ?>
          <div><audio controls src="<?=htmlspecialchars($mpath)?>"></audio></div>
        <?php else: ?>
          <div><a href="<?=htmlspecialchars($mpath)?>">Download media</a></div>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($ex['type'] === 'mcq'): ?>
    <form method="post" data-etype="mcq" aria-label="MCQ exercise">
      <?= csrf_field() ?>
      <h3><?=htmlspecialchars($q['prompt'])?></h3>
      <?php foreach ($q['choices'] as $c): ?>
        <label><input type="radio" name="choice" value="<?=htmlspecialchars($c['text'])?>"> <?=htmlspecialchars($c['text'])?></label><br>
      <?php endforeach; ?>
      <input type="hidden" name="exercise_id" value="<?= $ex['id'] ?>">
      <button type="submit">Soumettre</button>
    </form>
    <?php elseif ($ex['type'] === 'dragdrop'): ?>
    <form method="post" data-etype="dragdrop" aria-label="Drag and drop exercise">
      <?= csrf_field() ?>
      <h3><?=htmlspecialchars($q['prompt'])?></h3>
      <div class="drag-area" style="display:flex;gap:12px;">
        <div class="items" style="min-width:200px;border:1px solid #ddd;padding:8px;">
          <?php foreach ($q['items'] as $it): ?>
            <div class="drag-item" draggable="true" data-item-id="<?=htmlspecialchars($it['id'])?>" style="padding:6px;border:1px solid #ccc;margin:6px;background:#fff;"><?=htmlspecialchars($it['label'])?></div>
          <?php endforeach; ?>
        </div>
        <div class="targets" style="flex:1;">
          <?php foreach ($q['targets'] as $t): ?>
            <div class="drop-target" data-target-id="<?=htmlspecialchars($t['id'])?>" style="min-height:40px;border:1px dashed #bbb;padding:8px;margin:6px;"><?=htmlspecialchars($t['label'])?></div>
          <?php endforeach; ?>
        </div>
      </div>
      <input type="hidden" name="exercise_id" value="<?= $ex['id'] ?>">
      <button type="submit">Soumettre</button>
    </form>
    <?php elseif (in_array($ex['type'], ['pretest','posttest','prereq'])): ?>
      <p>Module-level <?=htmlspecialchars($ex['type'])?> available. <a href="/<?=htmlspecialchars($ex['type'])?>.php?module_id=<?=urlencode($unit['module_id'])?>">Take <?=htmlspecialchars(ucfirst($ex['type']))?></a></p>
    <?php else: ?>
      <p>Exercise type <?=htmlspecialchars($ex['type'])?> not supported yet.</p>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endforeach; ?>
  <?php endforeach; ?>
  <?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</main>
</body></html>
