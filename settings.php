<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role(['admin'], 'login.php');

$conn = db_connect();
$user = current_user();
$msg = '';
$errors = [];

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $dbuser  = $conn->query("SELECT * FROM users WHERE id={$user['id']} LIMIT 1")->fetch_assoc();
        if (!password_verify($current, $dbuser['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            $hashed = escape($conn, password_hash($new, PASSWORD_DEFAULT));
            $conn->query("UPDATE users SET password='$hashed' WHERE id={$user['id']}");
            $msg = 'password_changed';
        }
    }
    if ($_POST['action'] === 'profile') {
        $username = escape($conn, $_POST['username'] ?? '');
        $email    = escape($conn, $_POST['email'] ?? '');
        if ($username && $email) {
            $conn->query("UPDATE users SET username='$username', email='$email' WHERE id={$user['id']}");
            $_SESSION['user']['username'] = $_POST['username'];
            $_SESSION['user']['email']    = $_POST['email'];
            $msg = 'profile_saved';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings — BlogCraft Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="admin-main">
  <div class="admin-topbar"><div class="topbar-title">Settings</div></div>
  <div class="admin-content">

    <?php if ($msg === 'password_changed'): ?><div class="alert alert-success">Password updated successfully.</div><?php endif; ?>
    <?php if ($msg === 'profile_saved'): ?><div class="alert alert-success">Profile updated.</div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

      <!-- Profile -->
      <div class="editor-panel">
        <div class="editor-panel-title">My Profile</div>
        <form method="POST">
          <input type="hidden" name="action" value="profile">
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
          </div>
          <div class="form-group">
            <label>Role</label>
            <input type="text" value="<?= ucfirst($user['role']) ?>" readonly style="background:var(--paper-warm);color:var(--ink-muted);">
          </div>
          <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
      </div>

      <!-- Password -->
      <div class="editor-panel">
        <div class="editor-panel-title">Change Password</div>
        <form method="POST">
          <input type="hidden" name="action" value="password">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>
          </div>
          <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
      </div>

      <!-- Site Info -->
      <div class="editor-panel">
        <div class="editor-panel-title">Site Information</div>
        <div style="font-size:13px;">
          <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--rule);">
            <span style="color:var(--ink-muted);">PHP Version</span>
            <strong><?= phpversion() ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--rule);">
            <span style="color:var(--ink-muted);">Database</span>
            <strong>MySQL / MariaDB</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--rule);">
            <span style="color:var(--ink-muted);">Site URL</span>
            <strong><?= SITE_URL ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:10px 0;">
            <span style="color:var(--ink-muted);">CMS Version</span>
            <strong>BlogCraft 1.0</strong>
          </div>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="editor-panel" style="border:1px solid #f5c4b3;">
        <div class="editor-panel-title" style="color:var(--accent);">Danger Zone</div>
        <p style="font-size:13px;color:var(--ink-muted);margin-bottom:16px;line-height:1.6;">These actions are irreversible. Please be certain before proceeding.</p>
        <form method="POST" onsubmit="return confirm('Delete ALL posts? This cannot be undone.')">
          <input type="hidden" name="action" value="delete_posts">
          <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;">Delete All Posts</button>
        </form>
      </div>

    </div>
  </div>
</div>
</body>
</html>
