<?php
require_once __DIR__.'/../../src/auth.php';
require_login(); require_role('teacher');
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attempts.csv"');
$out = fopen('php://output','w');
fputcsv($out, ['attempt_id','user_id','username','unit_id','unit_title','exercise_id','score','max_score','created_at']);
$stmt = $pdo->query('SELECT a.*, u.username AS username, ex.unit_id, unit.title AS unit_title FROM attempts a JOIN users u ON u.id=a.user_id JOIN exercises ex ON ex.id=a.exercise_id JOIN units unit ON unit.id=ex.unit_id ORDER BY a.created_at DESC');
while($row = $stmt->fetch()){
  fputcsv($out, [$row['id'],$row['user_id'],$row['username'],$row['unit_id'],$row['unit_title'],$row['exercise_id'],$row['score'],$row['max_score'],$row['created_at']]);
}
fclose($out);
exit;
