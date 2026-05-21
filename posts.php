<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login('login.php');

$conn = db_connect();
$user = current_user();

// Handle delete
if (isset($_GET['delete']) && has_role(['admin','editor'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM posts WHERE id=$id");
    header('Location: posts.php?msg=deleted');
    exit;
}

// Filters
$status_filter = escape($conn, $_GET['status'] ?? '');
$search = escape($conn, $_GET['q'] ?? '');
$cat_filter = (int)($_GET['cat'] ?? 0);
$per_page = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where = '1=1';
if ($status_filter) $where .= " AND p.status='$status_filter'";
if ($search) $where .= " AND p.title LIKE '%$search%'";
if ($cat_filter) $where .= " AND p.category_id=$cat_filter";
// Authors can only see their own posts
if (has_role('author')) $where .= " AND p.author_id={$user['id']}";

$total = $conn->query("SELECT COUNT(*) as n FROM posts p WHERE $where")->fetch_assoc()['n'];
$total_pages = ceil($total / $per_page);

$posts = $conn->query("
    SELECT p.*, c.name as cat_name, u.username as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id=c.id
    LEFT JOIN users u ON p.author_id=u.id
    WHERE $where
    ORDER BY p.updated_at DESC
    LIMIT $per_page OFFSET $offset
");

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>All Posts — BlogCraft Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="admin-main">
  <div class="admin-topbar">
    <div class="topbar-title">All Posts</div>
    <div class="topbar-actions">
      <a href="post-edit.php" class="btn btn-primary btn-sm">+ New Post</a>
    </div>
  </div>
  <div class="admin-content">

    <?php if ($msg === 'deleted'): ?><div class="alert alert-success">Post deleted successfully.</div><?php endif; ?>
    <?php if ($msg === 'saved'): ?><div class="alert alert-success">Post saved successfully.</div><?php endif; ?>

    <!-- Filters -->
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px; flex-wrap:wrap;">
      <form method="GET" style="display:flex; gap:8px; flex:1; flex-wrap:wrap;">
        <input class="search-input" name="q" placeholder="Search posts…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        <select name="status" class="search-input" style="width:auto;">
          <option value="">All Statuses</option>
          <option value="published" <?= $status_filter==='published'?'selected':'' ?>>Published</option>
          <option value="draft" <?= $status_filter==='draft'?'selected':'' ?>>Draft</option>
          <option value="review" <?= $status_filter==='review'?'selected':'' ?>>In Review</option>
        </select>
        <select name="cat" class="search-input" style="width:auto;">
          <option value="">All Categories</option>
          <?php $categories->data_seek(0); while($c = $categories->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>" <?= $cat_filter==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-sm">Filter</button>
        <a href="posts.php" class="btn btn-sm">Clear</a>
      </form>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <div class="table-card-title"><?= $total ?> post<?= $total != 1 ? 's' : '' ?></div>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:40%;">Post</th>
            <th>Category</th>
            <th>Status</th>
            <th>Author</th>
            <th>Views</th>
            <th>Updated</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if ($posts->num_rows === 0): ?>
          <tr><td colspan="7" class="empty-state" style="padding:40px; text-align:center;">No posts found. <a href="post-edit.php">Create one →</a></td></tr>
          <?php endif; ?>
          <?php while ($p = $posts->fetch_assoc()):
            $colors = ['#c0392b','#2471a3','#1e8449','#8e44ad','#d35400'];
            $col = $colors[crc32($p['author_name'] ?? '') % count($colors)];
            $ini = strtoupper(substr($p['author_name'] ?? 'A', 0, 2));
          ?>
          <tr>
            <td>
              <div class="post-title-cell">
                <div class="title"><?= htmlspecialchars(substr($p['title'], 0, 60)) ?><?= strlen($p['title'])>60?'…':'' ?></div>
                <?php if ($p['excerpt']): ?>
                <div class="excerpt"><?= htmlspecialchars(substr($p['excerpt'], 0, 70)) ?>…</div>
                <?php endif; ?>
              </div>
            </td>
            <td><?php if ($p['cat_name']): ?><span class="tag"><?= htmlspecialchars($p['cat_name']) ?></span><?php else: ?>—<?php endif; ?></td>
            <td><span class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
            <td>
              <div class="author-cell">
                <div class="author-ava" style="background:<?= $col ?>;"><?= $ini ?></div>
                <?= htmlspecialchars($p['author_name'] ?? '—') ?>
              </div>
            </td>
            <td style="color:var(--ink-muted);"><?= number_format($p['views']) ?></td>
            <td style="font-size:12px;color:var(--ink-muted);white-space:nowrap;"><?= time_ago($p['updated_at']) ?></td>
            <td>
              <div style="display:flex;gap:5px;align-items:center;">
                <a href="post-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm">Edit</a>
                <?php if ($p['status']==='published'): ?>
                <a href="../post.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-sm" target="_blank">View</a>
                <?php endif; ?>
                <?php if (has_role(['admin','editor'])): ?>
                <a href="posts.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this post?')">Del</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div style="display:flex;justify-content:center;gap:6px;margin-top:20px;">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($_GET['q']??'') ?>&status=<?= urlencode($status_filter) ?>&cat=<?= $cat_filter ?>"
           style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;border:1px solid var(--rule);border-radius:4px;font-size:13px;color:<?= $i==$page?'white':'var(--ink-muted)' ?>;background:<?= $i==$page?'var(--accent)':'white' ?>;text-decoration:none;">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
