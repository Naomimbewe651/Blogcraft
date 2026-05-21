<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role(['admin'], 'login.php');

$conn = db_connect();
$errors = []; $success = '';
$editing = null;

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != current_user()['id']) {
        $conn->query("DELETE FROM users WHERE id=$id");
        header('Location: users.php?msg=deleted'); exit;
    }
}

// Edit load
if (isset($_GET['edit'])) {
    $editing = $conn->query("SELECT * FROM users WHERE id=".(int)$_GET['edit']." LIMIT 1")->fetch_assoc();
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($conn, $_POST['username'] ?? '');
    $email    = escape($conn, $_POST['email'] ?? '');
    $role     = escape($conn, $_POST['role'] ?? 'author');
    $edit_id  = (int)($_POST['edit_id'] ?? 0);
    $password = $_POST['password'] ?? '';

    if (!$username) $errors[] = 'Username is required.';
    if (!$email)    $errors[] = 'Email is required.';

    if (empty($errors)) {
        if ($edit_id) {
            $pw_sql = $password ? ", password='".escape($conn, password_hash($password, PASSWORD_DEFAULT))."'" : '';
            $conn->query("UPDATE users SET username='$username', email='$email', role='$role'$pw_sql WHERE id=$edit_id");
        } else {
            if (!$password) { $errors[] = 'Password is required for new users.'; }
            else {
                $hashed = escape($conn, password_hash($password, PASSWORD_DEFAULT));
                $res = $conn->query("INSERT INTO users (username,email,password,role) VALUES ('$username','$email','$hashed','$role')");
                if (!$res) $errors[] = 'Username or email already exists.';
            }
        }
        if (empty($errors)) { header('Location: users.php?msg=saved'); exit; }
    }
}

$users = $conn->query("SELECT u.*, COUNT(p.id) as post_count FROM users u LEFT JOIN posts p ON p.author_id=u.id GROUP BY u.id ORDER BY u.created_at ASC");
$msg = $_GET['msg'] ?? '';
$colors = ['#c0392b','#2471a3','#1e8449','#8e44ad','#d35400','#16a085','#c0392b'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Users — BlogCraft Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="admin-main">
  <div class="admin-topbar">
    <div class="topbar-title">Team Members</div>
  </div>
  <div class="admin-content">
    <?php if ($msg==='saved'): ?><div class="alert alert-success">User saved successfully.</div><?php endif; ?>
    <?php if ($msg==='deleted'): ?><div class="alert alert-success">User removed.</div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">

      <!-- User Cards Grid -->
      <div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;">
          <?php $i=0; $users->data_seek(0); while ($u = $users->fetch_assoc()):
            $col = $colors[$i % count($colors)];
            $ini = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $u['username']), 0, 2))));
          ?>
          <div style="background:white;border:1px solid var(--rule);border-radius:6px;padding:18px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
              <div style="width:44px;height:44px;border-radius:50%;background:<?= $col ?>;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:16px;font-weight:700;color:white;flex-shrink:0;"><?= $ini ?></div>
              <div style="overflow:hidden;">
                <div style="font-size:14px;font-weight:500;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($u['username']) ?></div>
                <div style="font-size:11px;color:var(--ink-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($u['email']) ?></div>
              </div>
            </div>
            <div style="margin-bottom:12px;"><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></div>
            <div style="display:flex;gap:14px;padding-top:10px;border-top:1px solid var(--rule);">
              <div style="text-align:center;">
                <div style="font-family:var(--font-display);font-size:20px;font-weight:700;color:var(--ink);"><?= $u['post_count'] ?></div>
                <div style="font-size:10px;color:var(--ink-faint);letter-spacing:0.5px;">Posts</div>
              </div>
              <div style="text-align:center;">
                <div style="font-family:var(--font-display);font-size:20px;font-weight:700;color:var(--ink);"><?= date('M y', strtotime($u['created_at'])) ?></div>
                <div style="font-size:10px;color:var(--ink-faint);letter-spacing:0.5px;">Joined</div>
              </div>
            </div>
            <div style="display:flex;gap:6px;margin-top:12px;">
              <a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-sm" style="flex:1;justify-content:center;">Edit</a>
              <?php if ($u['id'] != current_user()['id']): ?>
              <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove this user?')">Remove</a>
              <?php endif; ?>
            </div>
          </div>
          <?php $i++; endwhile; ?>

          <!-- Invite Card -->
          <div style="background:white;border:2px dashed var(--rule);border-radius:6px;padding:18px;display:flex;align-items:center;justify-content:center;min-height:200px;cursor:pointer;" onclick="document.getElementById('add-form-section').scrollIntoView({behavior:'smooth'})">
            <div style="text-align:center;color:var(--ink-faint);">
              <div style="font-size:32px;margin-bottom:8px;">+</div>
              <div style="font-size:13px;font-weight:500;">Add Member</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Add / Edit Form -->
      <div id="add-form-section" class="editor-panel">
        <div class="editor-panel-title"><?= $editing ? 'Edit User' : 'Add New User' ?></div>
        <form method="POST">
          <?php if ($editing): ?><input type="hidden" name="edit_id" value="<?= $editing['id'] ?>"><?php endif; ?>
          <div class="form-group">
            <label>Username *</label>
            <input type="text" name="username" value="<?= htmlspecialchars($editing['username'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($editing['email'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Password <?= $editing ? '(leave blank to keep current)' : '*' ?></label>
            <input type="password" name="password" <?= !$editing ? 'required' : '' ?>>
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role">
              <option value="viewer"  <?= ($editing['role']??'')  === 'viewer'  ? 'selected':'' ?>>Viewer — read-only</option>
              <option value="author"  <?= ($editing['role']??'author') === 'author'  ? 'selected':'' ?>>Author — write own posts</option>
              <option value="editor"  <?= ($editing['role']??'')  === 'editor'  ? 'selected':'' ?>>Editor — manage all posts</option>
              <option value="admin"   <?= ($editing['role']??'')  === 'admin'   ? 'selected':'' ?>>Admin — full access</option>
            </select>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Update User' : 'Add User' ?></button>
            <?php if ($editing): ?><a href="users.php" class="btn">Cancel</a><?php endif; ?>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
</body>
</html>
