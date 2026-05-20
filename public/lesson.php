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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  @file_put_contents(__DIR__.'/../var/logs/lesson.log', date('c')." POST RECEIVED POST=".json_encode($_POST)." COOKIE=".json_encode($_COOKIE)." SESSION=".json_encode($_SESSION)."\n", FILE_APPEND);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['exercise_id'])) {
  require_login();
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
    // Save attempt with level-up detection
    require_once __DIR__.'/../src/gamify.php';
    $user_id = $_SESSION['user_id'] ?? null;
    // compute old xp
    $old_xp = 0; try{ $sxo = $pdo->prepare('SELECT COALESCE(SUM(score),0) FROM attempts WHERE user_id = ?'); $sxo->execute([$user_id]); $old_xp = (int)$sxo->fetchColumn(); }catch(Exception $e){ $old_xp = 0; }
    $ins = $pdo->prepare('INSERT INTO attempts (user_id, exercise_id, score, max_score, data) VALUES (?,?,?,?,?)');
    try{
      $ins->execute([$user_id, $exercise_id, $score, $max, json_encode($_POST)]);
      @file_put_contents(__DIR__.'/../var/logs/lesson.log', date('c')." INSERT OK user=$user_id exercise=$exercise_id score=$score max=$max\n", FILE_APPEND);
    }catch(Exception $e){
      @file_put_contents(__DIR__.'/../var/logs/lesson.log', date('c')." INSERT ERROR user=$user_id exercise=$exercise_id score=$score max=$max error=". $e->getMessage() ." POST=".json_encode($_POST)."\n", FILE_APPEND);
    }
    // check level-up
    check_and_set_levelup($pdo, $user_id, $old_xp);
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
    // If remediation is needed, generate targeted micro-exercises (one-time)
    if ($needs) {
      require_once __DIR__.'/../src/remediation.php';
      try{ generate_remediation($pdo, $unit_id); }catch(Exception $e){ /* ignore */ }
    }
    // Redirect to avoid duplicate form submissions
    $redir = '/lesson.php?unit_id=' . urlencode($unit_id) . '&submitted=1';
    header('Location: ' . $redir);
    exit;
  }
}
?><!doctype html>
<html lang="fr"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($unit['title'])?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script>
</head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">

  <!-- REDESIGN: Lesson hero header -->
  <div class="rd-lesson-hero rd-anim">
    <div class="rd-unit-tag">📚 <?=htmlspecialchars($unit['module_title'])?></div>
    <h1><?=htmlspecialchars($unit['title'])?></h1>
    <p>Lisez la leçon, puis complétez les exercices ci-dessous.</p>
  </div>

  <!-- REDESIGN: Progress strip -->
  <div class="rd-strip"><div class="rd-strip-fill" style="width:<?= min(100, count($exercises) > 0 ? 40 : 0) ?>%"></div></div>

  <?php
    $modFilesStmt = $pdo->prepare('SELECT id,filename,path,mime,uploaded_at FROM media WHERE module_id = ? ORDER BY uploaded_at DESC');
    $modFilesStmt->execute([$unit['module_id']]); $modFiles = $modFilesStmt->fetchAll();
    if ($modFiles):
  ?>
  <div class="card" style="margin-bottom:16px">
    <strong style="font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:var(--rd-muted)">📎 Ressources du module</strong>
    <ul style="margin:8px 0 0;padding-left:18px">
      <?php foreach($modFiles as $mf): ?>
        <li style="margin:6px 0"><a href="<?=htmlspecialchars($mf['path'])?>" target="_blank" rel="noopener"><?=htmlspecialchars($mf['filename'])?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- REDESIGN: Lesson content -->
  <div class="content card explain rd-anim rd-d1"><?= $unit['content_html'] ?></div>

  <?php if ($result_message): ?>
  <div style="padding:14px 18px;border-radius:10px;font-weight:700;font-size:15px;margin-bottom:16px;background:var(--rd-success-light);color:var(--rd-success-dark);border:1px solid rgba(16,185,129,0.25);display:flex;align-items:center;gap:10px">
    ✅ <span><?=htmlspecialchars($result_message)?></span>
  </div>
  <?php endif; ?>

  <!-- REDESIGN: Exercise cards -->
  <?php $exIdx = 0; foreach ($exercises as $ex): ?>
  <?php $edata = json_decode($ex['data'], true); ?>
  <?php foreach ($edata as $idx => $q): ?>
    <?php if (!empty($q['media'])): ?>
      <?php if (is_string($q['media'])): $mpath = $q['media']; else: $mpath = $q['media']['path'] ?? null; endif; ?>
      <?php if ($mpath): ?>
        <?php $mime = @mime_content_type(__DIR__.$mpath) ?: ''; ?>
        <?php if (strpos($mime,'image/')===0): ?>
          <div style="margin-bottom:12px"><img src="<?=htmlspecialchars($mpath)?>" alt="" style="max-width:320px;border-radius:10px;display:block"></div>
        <?php elseif (strpos($mime,'audio/')===0): ?>
          <div style="margin-bottom:12px"><audio controls src="<?=htmlspecialchars($mpath)?>"></audio></div>
        <?php else: ?>
          <div style="margin-bottom:12px"><a href="<?=htmlspecialchars($mpath)?>">📎 Télécharger</a></div>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($ex['type'] === 'mcq'): $exIdx++; ?>
    <div class="exercise card rd-anim">
      <div class="rd-ex-badge">📝 QCM — Exercice <?= $exIdx ?></div>
      <form method="post" data-etype="mcq" aria-label="Exercice QCM">
        <?= csrf_field() ?>
        <h3><?=htmlspecialchars($q['prompt'])?></h3>
        <div class="choices">
        <?php foreach ($q['choices'] as $c): ?>
          <label>
            <input type="radio" name="choice" value="<?=htmlspecialchars($c['text'])?>">
            <?=htmlspecialchars($c['text'])?>
          </label>
        <?php endforeach; ?>
        </div>
        <input type="hidden" name="exercise_id" value="<?= $ex['id'] ?>">
        <button class="btn" type="submit">Valider ma réponse ✓</button>
      </form>
    </div>

    <?php elseif ($ex['type'] === 'dragdrop'): $exIdx++; ?>
    <div class="exercise card rd-anim">
      <div class="rd-ex-badge">🔀 Glisser-Déposer — Exercice <?= $exIdx ?></div>
      <form method="post" data-etype="dragdrop" aria-label="Exercice glisser-déposer">
        <?= csrf_field() ?>
        <h3><?=htmlspecialchars($q['prompt'])?></h3>
        <div class="drag-area" style="display:flex;gap:16px;align-items:flex-start">
          <div style="min-width:180px">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--rd-muted);margin-bottom:10px">Étiquettes</div>
            <div style="border:2px dashed var(--rd-border);border-radius:12px;padding:12px;min-height:120px;background:var(--rd-bg)">
              <?php foreach ($q['items'] as $it): ?>
                <div class="drag-item" draggable="true" data-item-id="<?=htmlspecialchars($it['id'])?>">
                  <?=htmlspecialchars($it['label'])?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div style="flex:1;display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px">
            <?php foreach ($q['targets'] as $t): ?>
              <div class="card" style="padding:12px!important">
                <div style="font-size:13px;font-weight:700;margin-bottom:8px;color:var(--rd-text)"><?=htmlspecialchars($t['label'])?></div>
                <div class="drop-target" data-target-id="<?=htmlspecialchars($t['id'])?>"></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <input type="hidden" name="mapping" value="{}">
        <input type="hidden" name="exercise_id" value="<?= $ex['id'] ?>">
        <button class="btn" type="submit" style="margin-top:16px">Soumettre ✓</button>
      </form>
    </div>

    <?php elseif (in_array($ex['type'], ['pretest','posttest','prereq'])): ?>
      <div class="card rd-anim" style="display:flex;align-items:center;justify-content:space-between;gap:16px">
        <div>
          <strong><?= ucfirst($ex['type']) ?> disponible</strong>
          <p class="muted" style="margin:4px 0 0">Évaluation spéciale pour ce module.</p>
        </div>
        <a class="btn secondary" href="/<?=htmlspecialchars($ex['type'])?>.php?module_id=<?=urlencode($unit['module_id'])?>">Commencer</a>
      </div>

    <?php else: ?>
      <div class="card muted rd-anim">Type d'exercice «<?=htmlspecialchars($ex['type'])?>» non supporté.</div>
    <?php endif; ?>
  <?php endforeach; ?>
  <?php endforeach; ?>

  <div style="margin-top:32px;display:flex;justify-content:space-between;align-items:center">
    <a class="btn secondary" href="/">&larr; Retour aux modules</a>
    <a class="btn" href="/posttest.php?module_id=<?= $unit['module_id'] ?>">Passer le post-test →</a>
  </div>

  <script src="/assets/js/exercises.js" defer></script>
  <?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</main>
</body></html>
