<?php
require_once __DIR__.'/../../src/auth.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])){ echo json_encode(['error'=>'not_authenticated']); exit; }
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT b.* , ub.awarded_at FROM badges b LEFT JOIN user_badges ub ON ub.badge_id=b.id AND ub.user_id = ? ORDER BY b.id');
$stmt->execute([$user_id]); $rows = $stmt->fetchAll();
echo json_encode(['ok'=>true,'badges'=>$rows]);
