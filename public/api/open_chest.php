<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/gamify.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])){ echo json_encode(['error'=>'not_authenticated']); exit; }
$user_id = $_SESSION['user_id'];

// determine old xp
$old_total = total_xp($pdo, $user_id);

// random reward: XP between 50 and 200
$xp = rand(50,200);
try{
  $stmt = $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?');
  $stmt->execute([$xp, $user_id]);
  // chance to unlock a random badge (10%)
  $badge = null;
  if (rand(1,100) <= 10){
    // pick a random badge
    $bq = $pdo->query('SELECT code FROM badges ORDER BY RAND() LIMIT 1'); $b = $bq->fetch();
    if ($b){
      $code = $b['code'];
      $ins = $pdo->prepare('INSERT IGNORE INTO user_badges (user_id,badge_id) SELECT ?, id FROM badges WHERE code = ?');
      $ins->execute([$user_id, $code]);
      $badge = $code;
    }
  }
  // check for level up
  $new_total = check_and_set_levelup($pdo, $user_id, $old_total);
  echo json_encode(['ok'=>true,'xp'=>$xp,'badge'=>$badge,'new_total'=>$new_total]);
}catch(Exception $e){ echo json_encode(['error'=>'db','msg'=>$e->getMessage()]); }
