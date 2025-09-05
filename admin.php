<?php
// admin.php — User Admin (SQLite + Tailwind)
// - ไม่มีระบบล็อกอินในหน้านี้ (ตามคำขอ) ให้ผู้ใช้งานไปใส่เองภายหลัง
// - ฟีเจอร์: list/search/create/update/delete/reset password + CSRF
const SQLITE_FILE = __DIR__ . '/auth.db';

session_start();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$CSRF = $_SESSION['csrf'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function valid_email($e){ return filter_var($e, FILTER_VALIDATE_EMAIL); }
function now_iso(){ return date('c'); }

function db() {
  static $pdo;
  if (!$pdo) {
    $pdo = new PDO('sqlite:' . SQLITE_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT NOT NULL UNIQUE,
      username TEXT UNIQUE,
      password_hash TEXT NOT NULL,
      created_at TEXT NOT NULL
    )');
  }
  return $pdo;
}
function csrf_check($t){
  if (!hash_equals($_SESSION['csrf'] ?? '', $t ?? '')) {
    http_response_code(403); echo 'CSRF invalid'; exit;
  }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$notice = null; $error = null;

try {
  $pdo = db();
  if ($method === 'POST') {
    csrf_check($_POST['csrf'] ?? '');

    if ($action === 'create') {
      $email = strtolower(trim($_POST['email'] ?? ''));
      $username = trim($_POST['username'] ?? '');
      $password = (string)($_POST['password'] ?? '');
      if (!valid_email($email)) throw new Exception('Invalid email');
      if ($password === '' || strlen($password) < 6) throw new Exception('Password >= 6 chars');
      if ($username !== '' && (mb_strlen($username) < 2 || mb_strlen($username) > 32)) {
        throw new Exception('Username length 2-32');
      }
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare('INSERT INTO users (email, username, password_hash, created_at) VALUES (:e,:u,:p,:c)');
      $stmt->execute([':e'=>$email, ':u'=>($username ?: null), ':p'=>$hash, ':c'=>now_iso()]);
      $notice = 'Created user id '.$pdo->lastInsertId();
    }

    if ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $email = strtolower(trim($_POST['email'] ?? ''));
      $username = trim($_POST['username'] ?? '');
      if ($id <= 0) throw new Exception('Invalid id');
      if (!valid_email($email)) throw new Exception('Invalid email');
      if ($username !== '' && (mb_strlen($username) < 2 || mb_strlen($username) > 32)) {
        throw new Exception('Username length 2-32');
      }
      $stmt = $pdo->prepare('UPDATE users SET email=:e, username=:u WHERE id=:id');
      $stmt->execute([':e'=>$email, ':u'=>($username ?: null), ':id'=>$id]);
      $notice = 'Updated user #'.$id;
    }

    if ($action === 'resetpw') {
      $id = (int)($_POST['id'] ?? 0);
      $password = (string)($_POST['password'] ?? '');
      if ($id <= 0) throw new Exception('Invalid id');
      if ($password === '' || strlen($password) < 6) throw new Exception('Password >= 6 chars');
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare('UPDATE users SET password_hash=:p WHERE id=:id');
      $stmt->execute([':p'=>$hash, ':id'=>$id]);
      $notice = 'Password reset for user #'.$id;
    }

    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new Exception('Invalid id');
      $stmt = $pdo->prepare('DELETE FROM users WHERE id=:id');
      $stmt->execute([':id'=>$id]);
      $notice = 'Deleted user #'.$id;
    }
  }
} catch (Throwable $ex) {
  $error = $ex->getMessage();
}

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = 'SELECT id, email, username, created_at FROM users';
if ($q !== '') { $sql .= ' WHERE email LIKE :q OR username LIKE :q'; $params[':q'] = '%'.$q.'%'; }
$sql .= ' ORDER BY id DESC LIMIT 300';
$stmt = db()->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>User Admin — SQLite</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="color-scheme" content="dark light">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
  <div class="max-w-6xl mx-auto p-4 md:p-8">
    <header class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between mb-6">
      <h1 class="text-xl md:text-2xl font-semibold tracking-tight">User Admin — SQLite</h1>
      <div class="flex gap-2">
        <a href="index.php" class="px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 ring-1 ring-slate-700 text-sm">กลับหน้า Demo</a>
      </div>
    </header>

    <?php if ($notice): ?>
      <div class="mb-4 rounded-xl border border-emerald-800 bg-emerald-900/40 px-4 py-3 text-emerald-200"><?=h($notice)?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-4 rounded-xl border border-rose-800 bg-rose-900/40 px-4 py-3 text-rose-200"><?=h($error)?></div>
    <?php endif; ?>

    <!-- Panels -->
    <div class="grid md:grid-cols-2 gap-6">
      <!-- Create -->
      <section class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-lg backdrop-blur">
        <div class="p-4 md:p-6">
          <h2 class="text-lg font-medium mb-3">เพิ่มผู้ใช้ใหม่</h2>
          <form method="post" class="grid gap-3">
            <input type="hidden" name="csrf" value="<?=$CSRF?>">
            <input type="hidden" name="action" value="create">
            <div class="grid gap-1">
              <label class="text-sm text-slate-300">Email</label>
              <input name="email" type="email" required placeholder="user@example.com"
                class="rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
            </div>
            <div class="grid gap-1">
              <label class="text-sm text-slate-300">Username (ไม่บังคับ)</label>
              <input name="username" type="text" minlength="2" maxlength="32" placeholder="myuser"
                class="rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
            </div>
            <div class="grid gap-1">
              <label class="text-sm text-slate-300">Password (≥ 6)</label>
              <input name="password" type="password" minlength="6" required placeholder="••••••••"
                class="rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
            </div>
            <button class="mt-2 inline-flex items-center justify-center gap-2 w-full sm:w-auto rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2 font-semibold">
              เพิ่มผู้ใช้
            </button>
            <p class="text-xs text-slate-400">* จะทำการ Hash รหัสผ่านด้วย <code>password_hash()</code> อัตโนมัติ</p>
          </form>
        </div>
      </section>

      <!-- Search -->
      <section class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-lg backdrop-blur">
        <div class="p-4 md:p-6">
          <h2 class="text-lg font-medium mb-3">ค้นหาผู้ใช้</h2>
          <form method="get" class="flex flex-col sm:flex-row gap-2">
            <input type="text" name="q" value="<?=h($q)?>" placeholder="ค้นหา email หรือ username"
              class="flex-1 rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
            <div class="flex gap-2">
              <button class="rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2">ค้นหา</button>
              <?php if ($q !== ''): ?>
                <a href="?" class="rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2">ล้าง</a>
              <?php endif; ?>
            </div>
          </form>
          <p class="text-xs text-slate-400 mt-2">แสดงสูงสุด 300 รายการล่าสุด</p>
        </div>
      </section>
    </div>

    <!-- List -->
    <section class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/60 shadow-lg backdrop-blur overflow-x-auto">
      <div class="p-4 md:p-6">
        <h2 class="text-lg font-medium mb-3">รายการผู้ใช้</h2>
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-slate-300">
              <th class="text-left py-2 pr-4">ID</th>
              <th class="text-left py-2 pr-4">Email / Username</th>
              <th class="text-left py-2 pr-4">Created</th>
              <th class="text-left py-2">Actions</th>
            </tr>
          </thead>
          <tbody class="align-top">
            <?php if (empty($rows)): ?>
              <tr><td colspan="4" class="py-4 text-slate-400">ไม่พบข้อมูล</td></tr>
            <?php else: foreach ($rows as $r): ?>
              <tr class="border-t border-slate-800">
                <td class="py-3 pr-4 font-medium"><?=h($r['id'])?></td>
                <td class="py-3 pr-4">
                  <form method="post" class="grid sm:grid-cols-3 gap-2">
                    <input type="hidden" name="csrf" value="<?=$CSRF?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?=h($r['id'])?>">
                    <input type="email" name="email" value="<?=h($r['email'])?>" required
                      class="sm:col-span-2 rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
                    <input type="text" name="username" value="<?=h($r['username'])?>" placeholder="(null)" minlength="2" maxlength="32"
                      class="rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
                    <button class="sm:col-span-3 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-4 py-2">
                      บันทึกการแก้ไข
                    </button>
                  </form>
                </td>
                <td class="py-3 pr-4 text-slate-300" title="<?=h($r['created_at'])?>"><?=h($r['created_at'])?></td>
                <td class="py-3">
                  <div class="grid sm:grid-cols-2 gap-2">
                    <form method="post" onsubmit="return confirm('รีเซ็ตรหัสผ่านของ ID <?=$r['id']?> ?')">
                      <input type="hidden" name="csrf" value="<?=$CSRF?>">
                      <input type="hidden" name="action" value="resetpw">
                      <input type="hidden" name="id" value="<?=h($r['id'])?>">
                      <div class="flex gap-2">
                        <input type="password" name="password" placeholder="new password ≥ 6" minlength="6" required
                          class="flex-1 rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-600" />
                        <button class="rounded-xl bg-amber-500 hover:bg-amber-400 px-4 py-2 text-slate-950 font-semibold">
                          รีเซ็ต
                        </button>
                      </div>
                    </form>
                    <form method="post" onsubmit="return confirm('ลบผู้ใช้ ID <?=$r['id']?> ถาวร?')">
                      <input type="hidden" name="csrf" value="<?=$CSRF?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?=h($r['id'])?>">
                      <button class="w-full rounded-xl bg-rose-600 hover:bg-rose-500 px-4 py-2 font-semibold">ลบ</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <footer class="text-xs text-slate-500 mt-6">
      * หน้านี้ไม่มีระบบล็อกอินตามคำขอ — โปรดเพิ่มระบบยืนยันตัวตนเองก่อนนำขึ้นโปรดักชัน
    </footer>
  </div>
</body>
</html>