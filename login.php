<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();
    if (login_user($conn, $_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In — BlogCraft</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500&display=swap');
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DM Sans', sans-serif; background: #1a1714; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
  .card { background: #faf9f7; border-radius: 8px; padding: 44px; width: 400px; max-width: 94vw; }
  .logo { font-family: 'Playfair Display', Georgia, serif; font-size: 26px; font-weight: 700; color: #1a1714; margin-bottom: 4px; }
  .logo span { color: #e74c3c; }
  .sub { font-size: 13px; color: #8a8480; margin-bottom: 30px; }
  label { display: block; font-size: 12px; font-weight: 500; color: #6b6560; margin-bottom: 5px; }
  input { width: 100%; padding: 11px 14px; border: 1px solid #e8e4dc; border-radius: 4px; font-size: 14px; font-family: 'DM Sans', sans-serif; outline: none; margin-bottom: 14px; transition: border-color 0.15s; background: white; }
  input:focus { border-color: #c0392b; }
  .btn { width: 100%; padding: 12px; background: #c0392b; color: white; border: none; border-radius: 4px; font-size: 14px; font-family: 'DM Sans', sans-serif; font-weight: 500; cursor: pointer; margin-top: 4px; transition: background 0.15s; }
  .btn:hover { background: #e74c3c; }
  .error { background: #fdf2f0; border: 1px solid #f5c4b3; border-radius: 4px; padding: 10px 14px; font-size: 13px; color: #c0392b; margin-bottom: 16px; }
  .back { display: block; text-align: center; margin-top: 18px; font-size: 13px; color: #8a8480; text-decoration: none; }
  .back:hover { color: #c0392b; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">blog<span>Craft</span></div>
  <div class="sub">Sign in to your dashboard</div>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <label>Username or Email</label>
    <input name="username" autofocus autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    <label>Password</label>
    <input name="password" type="password" autocomplete="current-password">
    <button type="submit" class="btn">Sign In →</button>
  </form>
  <a href="../index.php" class="back">← Back to blog</a>
</div>
</body>
</html>
