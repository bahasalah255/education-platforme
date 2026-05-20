<?php
// Gamification helpers: level calculations and level-up detection
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function level_from_xp($xp){
  $xp = max(0, (int)$xp);
  return max(1, (int)floor(pow($xp/100, 0.8)));
}

function total_xp($pdo, $user_id){
  // combine attempts.score + users.points for total XP
  try{
    $st = $pdo->prepare('SELECT COALESCE(SUM(score),0) AS a_sum FROM attempts WHERE user_id = ?');
    $st->execute([$user_id]); $a_sum = (int)$st->fetchColumn();
    $st2 = $pdo->prepare('SELECT COALESCE(points,0) FROM users WHERE id = ?'); $st2->execute([$user_id]); $pts = (int)$st2->fetchColumn();
    return $a_sum + $pts;
  }catch(Exception $e){ return 0; }
}

function check_and_set_levelup($pdo, $user_id, $old_xp){
  try{
    $new_xp = total_xp($pdo, $user_id);
    $old_level = level_from_xp($old_xp);
    $new_level = level_from_xp($new_xp);
    if ($new_level > $old_level){
      $_SESSION['level_up'] = ['level' => $new_level, 'new_xp' => $new_xp];
    }
    return $new_xp;
  }catch(Exception $e){ return null; }
}
