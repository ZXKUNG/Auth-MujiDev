<?php
// api.php — Minimal Auth API: register / login (SQLite)
const SQLITE_FILE = __DIR__ . '/auth.db';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function db() {
  static $pdo;
  if (!$pdo) {
    $dsn = 'sqlite:' . SQLITE_FILE;
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  }
  return $pdo;
}
function read_json() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}
function json_out($status, $payload) {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}
function ensure_schema() {
  $pdo = db();
  $pdo->exec('PRAGMA journal_mode = WAL');
  $pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    username TEXT UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL
  )');
}
ensure_schema();

$action = $_GET['action'] ?? (($_SERVER['PATH_INFO'] ?? '') ? ltrim($_SERVER['PATH_INFO'],'/') : '');

switch ($action) {
  case 'register': {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error'=>'Method not allowed']);
    $data = read_json();
    $email    = strtolower(trim($data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');
    $username = trim($data['username'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(422, ['error'=>'Invalid email']);
    if (strlen($password) < 6) json_out(422, ['error'=>'Password must be at least 6 chars']);
    if ($username !== '' && (mb_strlen($username) < 2 || mb_strlen($username) > 32)) {
      json_out(422, ['error'=>'Username length 2-32']);
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR (username IS NOT NULL AND username = :username) LIMIT 1');
    $stmt->execute([':email'=>$email, ':username'=>$username ?: '']);
    if ($stmt->fetch()) json_out(409, ['error'=>'Email or username already in use']);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $created = date('c'); // ISO8601
    $ins = $pdo->prepare('INSERT INTO users (email,username,password_hash,created_at) VALUES (:email,:username,:hash,:created)');
    $ins->execute([':email'=>$email, ':username'=>($username ?: null), ':hash'=>$hash, ':created'=>$created]);

    json_out(201, ['message'=>'Registered successfully', 'user_id'=>$pdo->lastInsertId()]);
  }
  case 'login': {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error'=>'Method not allowed']);
    $data = read_json();
    $email    = strtolower(trim($data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(422, ['error'=>'Invalid email']);
    if ($password === '') json_out(422, ['error'=>'Password required']);

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id,email,username,password_hash,created_at FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email'=>$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
      json_out(401, ['error'=>'Invalid credentials']);
    }
    unset($user['password_hash']);
    json_out(200, ['message'=>'Login success', 'user'=>$user]);
  }
  default:
    json_out(200, ['ok'=>true, 'db'=>basename(SQLITE_FILE), 'routes'=>['POST /register','POST /login']]);
}
