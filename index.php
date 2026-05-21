<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login('login.php');

$conn = db_connect();
$user = current_user();

// Stats
$total_posts      = $conn->query("SELECT COUNT(*) as n FROM posts")->fetch_assoc()['n'];
$published_posts  = $conn->query("SELECT COUNT(*) as n FROM posts WHERE status='published'")->fetch_assoc()['n'];
$draft_posts      = $conn->query("SELECT COUNT(*) as n FROM posts WHERE status='draft'")->fetch_assoc()['n'];
$total_views      = $conn->query("SELECT SUM(views) as n FROM posts")->fetch_assoc()['n'] ?? 0;
$pending_comments = $conn->query("SELECT COUNT(*) as n FROM comments WHERE status='pending'")->fetch_assoc()['n'];
$total_users      = $conn->query("SELECT COUNT(*) as n FROM users")->fetch_assoc()['n'];

// Recent posts
$recent_posts = $conn->query("
    SELECT p.*, c.name as cat_name, u.username as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id=c.id
    LEFT JOIN users u ON p.author_id=u.id
    ORDER BY p.updated_at DESC LIMIT 8
");

// Trending posts
$trending = $conn->query("
    SELECT p.title, p.slug, p.views, c.name as cat_name
    FROM posts p LEFT JOIN categories c ON p.category_id=c.id
    WHERE p.status='published'
    ORDER BY p.views DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard — BlogCraft Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <div class="admin-topbar">
    <div class="topbar-title">Dashboard</div>
    <div class="topbar-actions">
      <a href="../index.php" class="btn btn-sm" target="_blank">View Blog ↗</a>
      <a href="post-edit.php" class="btn btn-primary btn-sm">+ New Post</a>
    </div>
  </div>

  <div class="admin-content">

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Total Posts</div>
        <div class="stat-value"><?= $total_posts ?></div>
        <div class="stat-sub"><?= $published_posts ?> published · <?= $draft_posts ?> drafts</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Views</div>
        <div class="stat-value"><?= number_format($total_views) ?></div>
        <div class="stat-sub">Across all published posts</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pending Comments</div>
        <div class="stat-value"><?= $pending_comments ?></div>
        <div class="stat-sub"><a href="comments.php">Review now →</a></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Team Members</div>
        <div class="stat-value"><?= $total_users ?></div>
        <div class="stat-sub"><a href="users.php">Manage users →</a></div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start;">

      <div class="table-card">
        <div class="table-card-header">
          <div class="table-card-title">Recent Posts</div>
          <a href="posts.php" class="btn btn-sm">View All</a>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Post</th>
              <th>Status</th>
              <th>Views</th>
              <th>Updated</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recent_posts->num_rows === 0): ?>
            <tr><td colspan="5" class="empty-state">No posts yet. <a href="post-edit.php">Write one →</a></td></tr>
            <?php endif; ?>
            <?php while ($p = $recent_posts->fetch_assoc()): ?>
            <tr>
              <td>
                <div class="post-title-cell">
                  <div class="title"><?= htmlspecialchars(substr($p['title'], 0, 55)) ?><?= strlen($p['title']) > 55 ? '…' : '' ?></div>
                  <?php if ($p['cat_name']): ?><div class="excerpt"><span class="tag"><?= htmlspecialchars($p['cat_name']) ?></span></div><?php endif; ?>
                </div>
              </td>
              <td><span class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
              <td style="color:var(--ink-muted);"><?= number_format($p['views']) ?></td>
              <td style="font-size:12px; color:var(--ink-muted); white-space:nowrap;"><?= time_ago($p['updated_at']) ?></td>
              <td>
                <div style="display:flex; gap:5px;">
                  <a href="post-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm">Edit</a>
                  <?php if ($p['status'] === 'published'): ?>
                  <a href="../post.php?slug=<?= htmlspecialchars($p['slug']) ?>" class="btn btn-sm" target="_blank">View</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="table-card">
        <div class="table-card-header">
          <div class="table-card-title">Trending Posts</div>
        </div>
        <div style="padding: 8px 0;">
          <?php if ($trending->num_rows === 0): ?>
          <div class="empty-state" style="padding:24px;">No published posts yet.</div>
          <?php endif; ?>
          <?php $rank = 1; while ($t = $trending->fetch_assoc()): ?>
          <div style="display:flex; align-items:center; gap:12px; padding:10px 18px; border-bottom:1px solid var(--rule);">
            <div style="font-family:var(--font-display); font-size:20px; font-weight:700; color:var(--rule); width:24px; text-align:center;"><?= str_pad($rank, 2, '0', STR_PAD_LEFT) ?></div>
            <div style="flex:1;">
              <a href="../post.php?slug=<?= htmlspecialchars($t['slug']) ?>" style="font-size:13px; font-weight:500; color:var(--ink); text-decoration:none; line-height:1.3; display:block;" target="_blank"><?= htmlspecialchars(substr($t['title'], 0, 55)) ?></a>
              <?php if ($t['cat_name']): ?><div style="font-size:10px; color:var(--accent); margin-top:2px; font-weight:500;"><?= htmlspecialchars($t['cat_name']) ?></div><?php endif; ?>
            </div>
            <div style="font-size:12px; font-weight:500; color:var(--ink-muted); white-space:nowrap;"><?= number_format($t['views']) ?></div>
          </div>
          <?php $rank++; endwhile; ?>
        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>
