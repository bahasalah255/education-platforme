<?php
require_once __DIR__.'/config.php';
session_start();

function current_user() {
  if (!empty($_SESSION['user_id'])) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT id,username,display_name,role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
  }
  return null;
}

function login_user($username, $password) {
  global $pdo;
  $stmt = $pdo->prepare('SELECT id,password_hash FROM users WHERE username = ?');
  $stmt->execute([$username]);
  $u = $stmt->fetch();
  if ($u && password_verify($password, $u['password_hash'])) {
    $_SESSION['user_id'] = $u['id'];
    return true;
  }
  return false;
}

function require_login() {
  if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
  }
}

function is_role($role) {
  $u = current_user();
  return $u && isset($u['role']) && $u['role'] === $role;
}

function require_role($role) {
  if (!is_role($role)) {
    http_response_code(403);
    echo 'Forbidden: requires role ' . htmlspecialchars($role);
    exit;
  }
}

function logout_user() {
  session_unset();
  session_destroy();
}
