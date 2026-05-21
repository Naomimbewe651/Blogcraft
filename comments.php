<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role(['admin','editor'], 'login.php');

$conn = db_connect();

// Actions
if (isset($_GET['approve'])) {
    $conn->query("UPDATE comments SET status='approved' WHERE id=".(int)$_GET['approve']);
    header('Location: comments.php?msg=approved'); exit;
}
if (isset($_GET['spam'])) {
    $conn->query("UPDATE comments SET status='spam' WHERE id=".(int)$_GET['spam']);
    header('Location: comments.php?msg=spam'); exit;
}
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM comments WHERE id=".(int)$_GET['delete']);
    header('Location: comments.php?msg=deleted'); exit;
}

$filter = escape($conn, $_GET['filter'] ?? 'pending');
$where = $filter ? "WHERE c.status='$filter'" : '';
$comments = $conn->query("
    SELECT c.*, p.title as post_title, p.slug as post_slug
    FROM comments c
    LEFT JOIN posts p ON c.post_id = p.id
    $where
    ORDER BY c.created_at DESC
");

$counts = [];
foreach (['pending','approved','spam'] as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) as n FROM comments WHERE status='$s'")->fetch_assoc()['n'];
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Comments — BlogCraft Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="admin-main">
  <div class="admin-topbar"><div class="topbar-title">Comments</div></div>
  <div class="admin-content">

    <?php if ($msg): ?><div class="alert alert-success">Action completed.</div><?php endif; ?>

    <div style="display:flex;gap:8px;margin-bottom:18px;">
      <?php foreach (['pending','approved','spam'] as $s): ?>
      <a href="comments.php?filter=<?= $s ?>" class="btn <?= $filter===$s ? 'btn-primary' : '' ?> btn-sm">
        <?= ucfirst($s) ?> <span style="<?= $filter===$s?'color:rgba(255,255,255,0.7)':'color:var(--ink-faint)' ?>">(<?= $counts[$s] ?>)</span>
      </a>
      <?php endforeach; ?>
      <a href="comments.php?filter=" class="btn btn-sm <?= $filter==='' ? 'btn-primary' : '' ?>">All</a>
    </div>

    <div class="table-card">
      <table class="data-table">
        <thead><tr><th>Author</th><th>Comment</th><th>Post</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if ($comments->num_rows === 0): ?>
          <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--ink-muted);">No comments found.</td></tr>
          <?php endif; ?>
          <?php while ($c = $comments->fetch_assoc()): ?>
          <tr>
            <td>
              <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars($c['author_name']) ?></div>
              <?php if ($c['author_email']): ?><div style="font-size:11px;color:var(--ink-faint);"><?= htmlspecialchars($c['author_email']) ?></div><?php endif; ?>
            </td>
            <td style="font-size:13px;color:var(--ink-muted);max-width:280px;"><?= htmlspecialchars(substr($c['body'], 0, 120)) ?><?= strlen($c['body'])>120?'…':'' ?></td>
            <td>
              <?php if ($c['post_title']): ?>
              <a href="../post.php?slug=<?= urlencode($c['post_slug']) ?>" target="_blank" style="font-size:12px;"><?= htmlspecialchars(substr($c['post_title'], 0, 40)) ?></a>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--ink-muted);white-space:nowrap;"><?= time_ago($c['created_at']) ?></td>
            <td>
              <div style="display:flex;gap:5px;flex-wrap:wrap;">
                <?php if ($c['status'] !== 'approved'): ?>
                <a href="comments.php?approve=<?= $c['id'] ?>&filter=<?= $filter ?>" class="btn btn-sm btn-success">Approve</a>
                <?php endif; ?>
                <?php if ($c['status'] !== 'spam'): ?>
                <a href="comments.php?spam=<?= $c['id'] ?>&filter=<?= $filter ?>" class="btn btn-sm">Spam</a>
                <?php endif; ?>
                <a href="comments.php?delete=<?= $c['id'] ?>&filter=<?= $filter ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Del</a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
