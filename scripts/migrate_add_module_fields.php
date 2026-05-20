<?php
// Migration: add created_by and cover_image to modules if missing
require_once __DIR__.'/../src/config.php';
try{
  $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'modules' AND column_name = ?");
  $check->execute(['created_by']);
  if (!$check->fetchColumn()) {
    $pdo->exec("ALTER TABLE modules ADD COLUMN created_by INT NULL AFTER `order`");
    echo "Added column created_by\n";
  }
  $check->execute(['cover_image']);
  if (!$check->fetchColumn()) {
    $pdo->exec("ALTER TABLE modules ADD COLUMN cover_image VARCHAR(255) NULL AFTER created_by");
    echo "Added column cover_image\n";
  }
  echo "Migration finished.\n";
}catch(Exception $e){
  echo "Migration error: ".$e->getMessage()."\n";
}
