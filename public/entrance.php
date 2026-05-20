<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/csrf.php';
require_login();
verify_csrf();

$module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
if ($module_id <= 0) {
  // pick first module assigned to user if not provided
  $stmt = $pdo->prepare('SELECT module_id FROM user_courses WHERE user_id = ? LIMIT 1'); $stmt->execute([$_SESSION['user_id']]); $r = $stmt->fetch();
  if ($r) $module_id = (int)$r['module_id'];
}
if ($module_id <= 0) { echo 'Module not specified or you are not enrolled.'; exit; }

// items and correct target mapping
// targets: nom, verbe, consonne, voyelle
$items = [
  ['id'=>'i1','text'=>'chat','type'=>'word','correct'=>'nom'],
  ['id'=>'i2','text'=>'sauter','type'=>'word','correct'=>'verbe'],
  ['id'=>'i3','text'=>'les oiseaux','type'=>'word','correct'=>'nom'],
  ['id'=>'i4','text'=>'manger','type'=>'word','correct'=>'verbe'],
  ['id'=>'i5','text'=>'école','type'=>'word','correct'=>'nom'],
  ['id'=>'i6','text'=>'écrire','type'=>'word','correct'=>'verbe'],
  ['id'=>'i7','text'=>'les enfants','type'=>'word','correct'=>'nom'],
  ['id'=>'i8','text'=>'B','type'=>'letter','correct'=>'consonne'],
  ['id'=>'i9','text'=>'A','type'=>'letter','correct'=>'voyelle'],
  ['id'=>'i10','text'=>'C','type'=>'letter','correct'=>'consonne'],
  ['id'=>'i11','text'=>'E','type'=>'letter','correct'=>'voyelle'],
  ['id'=>'i12','text'=>'D','type'=>'letter','correct'=>'consonne'],
  ['id'=>'i13','text'=>'U','type'=>'letter','correct'=>'voyelle'],
  ['id'=>'i14','text'=>'f','type'=>'letter','correct'=>'consonne'],
];

$targets = [
  ['id'=>'t-nom','title'=>'Le nom','key'=>'nom'],
  ['id'=>'t-verbe','title'=>'Le verbe','key'=>'verbe'],
  ['id'=>'t-cons','title'=>'Consonne','key'=>'consonne'],
  ['id'=>'t-voy','title'=>'Voyelle','key'=>'voyelle'],
];

$result = null; $message='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $mapping_raw = $_POST['mapping'] ?? '{}';
  $mapping = json_decode($mapping_raw, true);
  if (!is_array($mapping)) $mapping = [];
  // mapping: targetId -> itemId
  $total = count($items); $score = 0;
  // invert mapping to item->target (support arrays of items per target)
  $itemToTarget = [];
  foreach ($mapping as $tid => $iid) {
    if (is_array($iid)) {
      foreach ($iid as $one) { $itemToTarget[$one] = $tid; }
    } else {
      $itemToTarget[$iid] = $tid;
    }
  }
  foreach ($items as $it) {
    $iid = $it['id']; $correctKey = $it['correct'];
    $assignedTid = $itemToTarget[$iid] ?? null;
    $assignedKey = null;
    if ($assignedTid) {
      foreach ($targets as $t) if ($t['id']==$assignedTid) { $assignedKey = $t['key']; break; }
    }
    if ($assignedKey === $correctKey) $score++;
  }
  $percent = $total>0 ? round(100*$score/$total) : 0;
  // record an 'entrance' exercise + attempt for analytics
  try {
    $firstUnitStmt = $pdo->prepare('SELECT id FROM units WHERE module_id = ? ORDER BY `order` LIMIT 1'); $firstUnitStmt->execute([$module_id]); $unit_ref = $firstUnitStmt->fetchColumn();
    $exercise_id = null;
    if ($unit_ref) {
      $insEx = $pdo->prepare('INSERT INTO exercises (unit_id,type,data,points) VALUES (?,?,?,?)');
      $insEx->execute([$unit_ref,'entrance', json_encode(['module_id'=>$module_id,'items'=>$items,'targets'=>$targets]), $total]);
      $exercise_id = $pdo->lastInsertId();
    }
    if ($exercise_id) {
      $insAt = $pdo->prepare('INSERT INTO attempts (user_id,exercise_id,score,max_score,data) VALUES (?,?,?,?,?)');
      $insAt->execute([$_SESSION['user_id'],$exercise_id,$score,$total,json_encode(['mapping'=>$mapping])]);
    }
  } catch (Exception $e) {
    error_log('Entrance attempt save error: '.$e->getMessage());
  }
  // Decision rules
  if ($percent === 100) {
    // pass: mark all units complete
    $stmt = $pdo->prepare('SELECT id FROM units WHERE module_id = ?'); $stmt->execute([$module_id]); $uids = array_column($stmt->fetchAll(),'id');
    foreach ($uids as $uid) {
      $up = $pdo->prepare('SELECT id FROM progress WHERE user_id = ? AND unit_id = ?'); $up->execute([$_SESSION['user_id'],$uid]); $ex = $up->fetch();
      if ($ex) {
        $pdo->prepare('UPDATE progress SET score=?, max_score=?, needs_remediation=0 WHERE id=?')->execute([1,1,$ex['id']]);
      } else {
        $pdo->prepare('INSERT INTO progress (user_id,unit_id,score,max_score,needs_remediation) VALUES (?,?,?,?,0)')->execute([$_SESSION['user_id'],$uid,1,1]);
      }
    }
    $message = "Succès complet ({$percent}%). Vous suivez le module.";
    $result = 'pass';
  } elseif ($percent >= 50) {
    // partial: send to first unit not mastered
    $stmt = $pdo->prepare('SELECT id FROM units WHERE module_id = ? ORDER BY `order`'); $stmt->execute([$module_id]); $units = $stmt->fetchAll();
    $first = null;
    foreach ($units as $u) {
      $st = $pdo->prepare('SELECT score,max_score FROM progress WHERE user_id=? AND unit_id=?'); $st->execute([$_SESSION['user_id'],$u['id']]); $p = $st->fetch();
      if (!$p || ($p['max_score']>0 && $p['score'] < $p['max_score'])) { $first = $u['id']; break; }
    }
    $message = "Succès partiel ({$percent}%). Vous suivez uniquement les unités non maîtrisées.";
    $result = 'partial';
    if ($first) { header('Location: /lesson.php?unit_id='.$first); exit; }
  } else {
    // fail: generate remediation for first unit and redirect there in remediation mode
    $stmt = $pdo->prepare('SELECT id FROM units WHERE module_id = ? ORDER BY `order` LIMIT 1'); $stmt->execute([$module_id]); $first = $stmt->fetchColumn();
    if ($first) { require_once __DIR__.'/../src/remediation.php'; generate_remediation($pdo,$first); header('Location: /lesson.php?unit_id='.$first.'&remediation=1'); exit; }
    $message = "Échec ({$percent}%). Remédiation requise.";
    $result = 'fail';
  }
}

?><!doctype html>
<html lang="fr"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Système d'entrée</title>
<link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/exercises.js" defer></script>
<script src="/assets/js/ui.js" defer></script>
</head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">

  <!-- REDESIGN: Entrance test header -->
  <?php if (!$result): ?>
  <div class="rd-lesson-hero rd-anim" style="background:linear-gradient(135deg,#7c3aed,#2563EB)">
    <div class="rd-unit-tag">🔬 Évaluation diagnostique</div>
    <h1>Système d'entrée</h1>
    <p>Place chaque étiquette dans la bonne colonne. Ceci déterminera ton parcours d'apprentissage.</p>
  </div>
  <div class="rd-strip"><div class="rd-strip-fill" style="width:0%"></div></div>
  <?php endif; ?>

  <!-- REDESIGN: Result message -->
  <?php if ($message): ?>
  <div class="rd-quiz-wrap">
    <div class="rd-result-card rd-anim">
      <div style="font-size:4rem;margin-bottom:16px">
        <?= ($result === 'pass') ? '🏆' : ($result === 'partial' ? '⚡' : '💪') ?>
      </div>
      <h2 style="font-size:1.5rem;margin-bottom:8px">
        <?= ($result === 'pass') ? 'Excellent travail !' : ($result === 'partial' ? 'Bon début !' : 'Continue tes efforts !') ?>
      </h2>
      <p class="muted" style="margin-bottom:24px"><?=htmlspecialchars($message)?></p>
      <a class="btn" href="/">← Retour à l'accueil</a>
    </div>
  </div>
  <?php else: ?>

  <!-- REDESIGN: Drag-drop entrance form -->
  <form method="post" class="rd-anim rd-d1" id="entranceForm" data-etype="dragdrop">
    <?= csrf_field() ?>

    <div style="display:grid;grid-template-columns:200px 1fr;gap:20px;align-items:flex-start">
      <!-- Items bank -->
      <div>
        <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:var(--rd-muted);margin-bottom:10px">📌 Étiquettes</div>
        <div style="border:2px dashed var(--rd-border);border-radius:14px;padding:12px;min-height:280px;background:var(--rd-bg)">
          <?php foreach($items as $it): ?>
            <div class="drag-item" draggable="true" data-item-id="<?= $it['id'] ?>"
              style="margin:4px">
              <?=htmlspecialchars($it['text'])?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Drop targets -->
      <div>
        <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:var(--rd-muted);margin-bottom:10px">🎯 Colonnes de classement</div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
          <?php foreach($targets as $t): ?>
            <div class="card" style="padding:16px!important">
              <div style="font-size:14px;font-weight:800;margin-bottom:10px;color:var(--rd-primary)"><?=htmlspecialchars($t['title'])?></div>
              <div class="drop-target" data-target-id="<?= $t['id'] ?>" style="min-height:140px"></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <input type="hidden" name="mapping" value="{}">
    <div style="margin-top:20px;display:flex;justify-content:flex-end">
      <button class="btn" type="submit" style="padding:13px 28px!important;font-size:15px!important">
        Soumettre mes réponses ✓
      </button>
    </div>
  </form>

  <?php endif; ?>

</main>
<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</body></html>
