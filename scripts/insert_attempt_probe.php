<?php
require_once __DIR__.'/../src/config.php';
$user = 7; // qa_student
$exercise = 1;
$score = 1; $max = 1;
try{
  $ins = $pdo->prepare('INSERT INTO attempts (user_id, exercise_id, score, max_score, data) VALUES (?,?,?,?,?)');
  $ins->execute([$user, $exercise, $score, $max, json_encode(['probe'=>true])]);
  echo "Inserted attempt id: ".$pdo->lastInsertId()."\n";
}catch(Exception $e){ echo "ERR: ".$e->getMessage()."\n"; }
