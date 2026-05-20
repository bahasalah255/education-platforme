<?php
require_once __DIR__.'/../../src/auth.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])){ echo json_encode(['error'=>'not_authenticated']); exit; }
$user_id = $_SESSION['user_id'];
$code = $_POST['code'] ?? ($_GET['code'] ?? null);
if (!$code){ echo json_encode(['error'=>'no_code']); exit; }
// find badge by code
$stmt = $pdo->prepare('SELECT id,code,title,description,icon FROM badges WHERE code = ?');
$stmt->execute([$code]); $badge = $stmt->fetch();
if (!$badge){ echo json_encode(['error'=>'badge_not_found']); exit; }
// insert if not exists
try{
  $ins = $pdo->prepare('INSERT IGNORE INTO user_badges (user_id,badge_id) VALUES (?,?)');
  $ins->execute([$user_id, $badge['id']]);
  echo json_encode(['ok'=>true,'badge'=>$badge]);
}catch(Exception $e){ echo json_encode(['error'=>'db','msg'=>$e->getMessage()]); }
