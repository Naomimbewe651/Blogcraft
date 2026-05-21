<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$conn = db_connect();
$slug = escape($conn, $_GET['slug'] ?? '');

if (!$slug) { header('Location: index.php'); exit; }

$post = $conn->query("
    SELECT p.*, c.name as cat_name, c.slug as cat_slug,
           u.username as author_name, u.role as author_role
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.slug = '$slug' AND p.status = 'published'
    LIMIT 1
")->fetch_assoc();

if (!$post) { header('Location: index.php'); exit; }

// Increment view count
$conn->query("UPDATE posts SET views = views + 1 WHERE id = {$post['id']}");

// Handle comment submission
$comment_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_body'])) {
    $name  = escape($conn, $_POST['comment_name'] ?? '');
    $email = escape($conn, $_POST['comment_email'] ?? '');
    $body  = escape($conn, $_POST['comment_body'] ?? '');
    if ($name && $body) {
        $conn->query("INSERT INTO comments (post_id, author_name, author_email, body) VALUES ({$post['id']}, '$name', '$email', '$body')");
        $comment_msg = 'success';
    } else {
        $comment_msg = 'error';
    }
}

// Load approved comments
$comments = $conn->query("SELECT * FROM comments WHERE post_id={$post['id']} AND status='approved' ORDER BY created_at ASC");

// Related posts
$cat_id = (int)$post['category_id'];
$post_id = (int)$post['id'];
$related = $conn->query("
    SELECT p.title, p.slug, c.name as cat_name
    FROM posts p LEFT JOIN categories c ON p.category_id=c.id
    WHERE p.status='published' AND p.id != $post_id AND p.category_id = $cat_id
    ORDER BY p.created_at DESC LIMIT 3
");

// Reading time
$word_count = str_word_count(strip_tags($post['body']));
$read_time = max(1, round($word_count / 200));

// Author initials
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $post['author_name'] ?? 'A'), 0, 2))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($post['title']) ?> — <?= SITE_NAME ?></title>
<meta name="description" content="<?= htmlspecialchars($post['excerpt']) ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
  <a href="index.php" class="site-logo">blog<span>Craft</span></a>
  <nav class="site-nav">
    <a href="index.php" class="hide-mobile">← All Posts</a>
    <?php if ($post['cat_name']): ?>
      <a href="index.php?cat=<?= $post['cat_slug'] ?>" class="hide-mobile"><?= htmlspecialchars($post['cat_name']) ?></a>
    <?php endif; ?>
    <?php if (is_logged_in()): ?>
      <a href="admin/post-edit.php?id=<?= $post['id'] ?>" class="btn-nav">Edit Post</a>
    <?php else: ?>
      <a href="admin/login.php" class="btn-nav">Sign In</a>
    <?php endif; ?>
  </nav>
</header>

<div class="post-container">
  <header class="post-header">
    <?php if ($post['cat_name']): ?>
      <a href="index.php?cat=<?= $post['cat_slug'] ?>" class="post-cat-badge"><?= htmlspecialchars($post['cat_name']) ?></a>
    <?php endif; ?>
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <?php if ($post['excerpt']): ?>
      <p style="font-size:17px; color:var(--ink-muted); margin-top:-4px; margin-bottom:12px; line-height:1.6;"><?= htmlspecialchars($post['excerpt']) ?></p>
    <?php endif; ?>
    <div class="post-byline">
      <div class="post-author-avatar"><?= $initials ?></div>
      <div class="post-byline-info">
        <div class="name"><?= htmlspecialchars($post['author_name'] ?? 'Anonymous') ?></div>
        <div class="meta">
          <?= date('F j, Y', strtotime($post['created_at'])) ?>
          &nbsp;·&nbsp; <?= $read_time ?> min read
          &nbsp;·&nbsp; <?= number_format($post['views']) ?> views
        </div>
      </div>
    </div>
  </header>

  <?php if ($post['cover_image']): ?>
  <img src="uploads/<?= htmlspecialchars($post['cover_image']) ?>" alt="" style="width:100%; max-height:400px; object-fit:cover; border-radius:6px; margin-bottom:32px;">
  <?php endif; ?>

  <div class="post-content">
    <?= $post['body'] ?>
  </div>

  <!-- Related Posts -->
  <?php if ($related->num_rows > 0): ?>
  <div style="margin-top:50px; padding-top:36px; border-top:1px solid var(--rule);">
    <h3 style="font-family:var(--font-display); font-size:20px; margin-bottom:18px;">More to Read</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:14px;">
      <?php while ($r = $related->fetch_assoc()): ?>
      <a href="post.php?slug=<?= htmlspecialchars($r['slug']) ?>" style="display:block; background:white; border:1px solid var(--rule); border-radius:5px; padding:16px; text-decoration:none; transition:all 0.15s;" onmouseover="this.style.borderColor='#c0392b'" onmouseout="this.style.borderColor='var(--rule)'">
        <div style="font-size:9px; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; color:var(--accent); margin-bottom:6px;"><?= htmlspecialchars($r['cat_name']) ?></div>
        <div style="font-family:var(--font-display); font-size:14px; font-weight:600; color:var(--ink); line-height:1.3;"><?= htmlspecialchars($r['title']) ?></div>
      </a>
      <?php endwhile; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Comments -->
  <div class="comments-section">
    <h3>Responses (<?= $comments->num_rows ?>)</h3>

    <?php if ($comment_msg === 'success'): ?>
      <div class="alert alert-success">Your comment has been submitted and is pending review.</div>
    <?php elseif ($comment_msg === 'error'): ?>
      <div class="alert alert-error">Please fill in your name and comment.</div>
    <?php endif; ?>

    <?php if ($comments->num_rows > 0): ?>
      <?php while ($c = $comments->fetch_assoc()): ?>
      <div class="comment-item">
        <div>
          <span class="comment-author"><?= htmlspecialchars($c['author_name']) ?></span>
          <span class="comment-time"><?= time_ago($c['created_at']) ?></span>
        </div>
        <div class="comment-body"><?= nl2br(htmlspecialchars($c['body'])) ?></div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="color:var(--ink-muted); font-size:14px; padding:16px 0;">No responses yet. Be the first!</p>
    <?php endif; ?>

    <div class="comment-form">
      <h4>Leave a Response</h4>
      <form method="POST">
        <div class="form-row">
          <div class="form-group"><label>Name *</label><input name="comment_name" required></div>
          <div class="form-group"><label>Email (optional)</label><input name="comment_email" type="email"></div>
        </div>
        <div class="form-group"><label>Your comment *</label><textarea name="comment_body" required></textarea></div>
        <button type="submit" class="btn btn-primary">Post Response</button>
      </form>
    </div>
  </div>
</div>

<footer class="site-footer">
  <p><?= SITE_NAME ?> &copy; <?= date('Y') ?> · <a href="index.php">← Back to Blog</a></p>
</footer>

</body>
</html>
