<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role(['admin','editor'], 'login.php');

$conn = db_connect();
$errors = []; $success = '';

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id=$id");
    header('Location: categories.php?msg=deleted'); exit;
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = escape($conn, $_POST['name'] ?? '');
    $desc = escape($conn, $_POST['description'] ?? '');
    $edit_id = (int)($_POST['edit_id'] ?? 0);
    if (!$name) $errors[] = 'Name is required.';
    if (empty($errors)) {
        $slug = escape($conn, slugify($_POST['name']));
        if ($edit_id) {
            $conn->query("UPDATE categories SET name='$name', slug='$slug', description='$desc' WHERE id=$edit_id");
        } else {
            $conn->query("INSERT INTO categories (name, slug, description) VALUES ('$name','$slug','$desc')");
        }
        header('Location: categories.php?msg=saved'); exit;
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $editing = $conn->query("SELECT * FROM categories WHERE id=$eid LIMIT 1")->fetch_assoc();
}

$cats = $conn->query("SELECT c.*, COUNT(p.id) as post_count FROM categories c LEFT JOIN posts p ON p.category_id=c.id GROUP BY c.id ORDER BY c.name");
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Categories — BlogCraft Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="admin-main">
  <div class="admin-topbar"><div class="topbar-title">Categories</div></div>
  <div class="admin-content">
    <?php if ($msg==='saved'): ?><div class="alert alert-success">Category saved.</div><?php endif; ?>
    <?php if ($msg==='deleted'): ?><div class="alert alert-success">Category deleted.</div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">

      <div class="table-card">
        <div class="table-card-header"><div class="table-card-title">All Categories</div></div>
        <table class="data-table">
          <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Description</th><th></th></tr></thead>
          <tbody>
            <?php if ($cats->num_rows === 0): ?>
            <tr><td colspan="5" class="empty-state" style="padding:30px;text-align:center;">No categories yet.</td></tr>
            <?php endif; ?>
            <?php while ($c = $cats->fetch_assoc()): ?>
            <tr>
              <td style="font-weight:500;"><?= htmlspecialchars($c['name']) ?></td>
              <td><code style="font-size:12px;background:var(--paper-warm);padding:2px 6px;border-radius:3px;"><?= htmlspecialchars($c['slug']) ?></code></td>
              <td><?= $c['post_count'] ?></td>
              <td style="color:var(--ink-muted);font-size:12px;"><?= htmlspecialchars(substr($c['description'] ?? '', 0, 60)) ?></td>
              <td>
                <div style="display:flex;gap:5px;">
                  <a href="categories.php?edit=<?= $c['id'] ?>" class="btn btn-sm">Edit</a>
                  <a href="categories.php?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">Del</a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="editor-panel">
        <div class="editor-panel-title"><?= $editing ? 'Edit Category' : 'Add Category' ?></div>
        <form method="POST">
          <?php if ($editing): ?><input type="hidden" name="edit_id" value="<?= $editing['id'] ?>"><?php endif; ?>
          <div class="form-group">
            <label>Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add Category' ?></button>
            <?php if ($editing): ?><a href="categories.php" class="btn">Cancel</a><?php endif; ?>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
</body>
</html>
