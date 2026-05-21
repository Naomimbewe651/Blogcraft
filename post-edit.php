<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login('login.php');

$conn = db_connect();
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
$post = null;
$errors = [];
$success = '';

// Load existing post
if ($id) {
    $post = $conn->query("SELECT * FROM posts WHERE id=$id LIMIT 1")->fetch_assoc();
    if (!$post) { header('Location: posts.php'); exit; }
    // Authors can only edit their own
    if (has_role('author') && $post['author_id'] != $user['id']) {
        die('<p style="padding:40px;font-family:sans-serif;color:#c0392b;">Access denied.</p>');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = escape($conn, $_POST['title'] ?? '');
    $excerpt  = escape($conn, $_POST['excerpt'] ?? '');
    $body     = escape($conn, $_POST['body'] ?? '');
    $status   = escape($conn, $_POST['status'] ?? 'draft');
    $cat_id   = (int)($_POST['category_id'] ?? 0);
    $tags_raw = escape($conn, $_POST['tags'] ?? '');

    if (!$title) $errors[] = 'Title is required.';
    if (!$body)  $errors[] = 'Post body is required.';

    // Handle cover image upload
    $cover_image = $post['cover_image'] ?? '';
    if (!empty($_FILES['cover_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fname = uniqid('cover_') . '.' . $ext;
            move_uploaded_file($_FILES['cover_image']['tmp_name'], '../uploads/' . $fname);
            $cover_image = $fname;
        } else {
            $errors[] = 'Invalid image type. Use JPG, PNG, GIF, or WebP.';
        }
    }

    if (empty($errors)) {
        // Generate slug
        $base_slug = slugify($title);
        $slug = $base_slug;
        $n = 1;
        while (true) {
            $check = $conn->query("SELECT id FROM posts WHERE slug='$slug' AND id != $id LIMIT 1");
            if ($check->num_rows === 0) break;
            $slug = $base_slug . '-' . $n++;
        }
        $slug = escape($conn, $slug);
        $cat_val = $cat_id ?: 'NULL';
        $cover_val = $cover_image ? "'$cover_image'" : 'NULL';

        if ($id) {
            $conn->query("UPDATE posts SET title='$title', slug='$slug', excerpt='$excerpt', body='$body', status='$status', category_id=$cat_val, cover_image=$cover_val, updated_at=NOW() WHERE id=$id");
        } else {
            $conn->query("INSERT INTO posts (title,slug,excerpt,body,status,category_id,cover_image,author_id) VALUES ('$title','$slug','$excerpt','$body','$status',$cat_val,$cover_val,{$user['id']})");
            $id = $conn->insert_id;
        }

        // Handle tags
        $conn->query("DELETE FROM post_tags WHERE post_id=$id");
        if ($tags_raw) {
            foreach (explode(',', $_POST['tags']) as $tag_name) {
                $tn = escape($conn, trim($tag_name));
                $ts = escape($conn, slugify(trim($tag_name)));
                if (!$tn) continue;
                $conn->query("INSERT IGNORE INTO tags (name,slug) VALUES ('$tn','$ts')");
                $tid = $conn->query("SELECT id FROM tags WHERE slug='$ts' LIMIT 1")->fetch_assoc()['id'];
                $conn->query("INSERT IGNORE INTO post_tags (post_id,tag_id) VALUES ($id,$tid)");
            }
        }

        header('Location: posts.php?msg=saved');
        exit;
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Load post tags
$post_tags = '';
if ($id) {
    $tags = $conn->query("SELECT t.name FROM tags t JOIN post_tags pt ON t.id=pt.tag_id WHERE pt.post_id=$id");
    $tag_names = [];
    while ($t = $tags->fetch_assoc()) $tag_names[] = $t['name'];
    $post_tags = implode(', ', $tag_names);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $id ? 'Edit Post' : 'New Post' ?> — BlogCraft Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="admin-main">
  <div class="admin-topbar">
    <div class="topbar-title"><?= $id ? 'Edit Post' : 'New Post' ?></div>
    <div class="topbar-actions">
      <a href="posts.php" class="btn btn-sm">← All Posts</a>
      <?php if ($id && ($post['status'] ?? '') === 'published'): ?>
      <a href="../post.php?slug=<?= urlencode($post['slug']) ?>" class="btn btn-sm" target="_blank">View Live ↗</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="admin-content">

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="editor-grid">

        <!-- Main Editor -->
        <div>
          <div class="editor-panel" style="margin-bottom:16px;">
            <input class="title-input" type="text" name="title" placeholder="Post title…" value="<?= htmlspecialchars($post['title'] ?? $_POST['title'] ?? '') ?>" required>
            <div class="editor-toolbar">
              <button type="button" class="tb-btn" onclick="fmt('bold')" title="Bold"><b>B</b></button>
              <button type="button" class="tb-btn" onclick="fmt('italic')" title="Italic"><i>I</i></button>
              <button type="button" class="tb-btn" onclick="fmt('underline')" title="Underline" style="text-decoration:underline;">U</button>
              <div class="tb-sep"></div>
              <button type="button" class="tb-btn" onclick="wrapTag('h2')" title="Heading 2">H2</button>
              <button type="button" class="tb-btn" onclick="wrapTag('h3')" title="Heading 3">H3</button>
              <div class="tb-sep"></div>
              <button type="button" class="tb-btn" onclick="insertBlock('blockquote')" title="Blockquote">"</button>
              <button type="button" class="tb-btn" onclick="insertList('ul')" title="Bullet List">•—</button>
              <button type="button" class="tb-btn" onclick="insertList('ol')" title="Numbered List">1.</button>
              <div class="tb-sep"></div>
              <button type="button" class="tb-btn" onclick="insertLink()" title="Link">🔗</button>
              <button type="button" class="tb-btn" onclick="insertCode()" title="Code Block">&lt;/&gt;</button>
              <div class="tb-sep"></div>
              <button type="button" class="tb-btn" onclick="fmt('undo')" title="Undo">↩</button>
              <button type="button" class="tb-btn" onclick="fmt('redo')" title="Redo">↪</button>
            </div>
            <div id="editor-area" contenteditable="true" style="min-height:380px;border:1px solid var(--rule);border-radius:4px;padding:16px;font-family:var(--font-body);font-size:14px;line-height:1.8;color:var(--ink);outline:none;transition:border-color 0.15s;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--rule)'; syncBody();"><?= $post['body'] ?? '' ?></div>
            <textarea id="post_body" name="body" style="display:none;" required><?= htmlspecialchars($post['body'] ?? '') ?></textarea>
            <div class="word-count" id="wc">0 words</div>
          </div>

          <div class="editor-panel">
            <div class="editor-panel-title">Excerpt</div>
            <div class="form-group" style="margin:0;">
              <textarea name="excerpt" rows="3" placeholder="A short description of this post (shown in listings and SEO)…" style="width:100%;padding:10px 12px;border:1px solid var(--rule);border-radius:4px;font-family:var(--font-body);font-size:13px;resize:vertical;outline:none;line-height:1.6;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--rule)'"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div>
          <!-- Publish -->
          <div class="editor-panel" style="margin-bottom:16px;">
            <div class="editor-panel-title">Publish</div>
            <div class="sidebar-field">
              <label class="sidebar-label">Status</label>
              <select name="status" class="sidebar-select">
                <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="review" <?= ($post['status'] ?? '') === 'review' ? 'selected' : '' ?>>Submit for Review</option>
                <?php if (has_role(['admin','editor'])): ?>
                <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                <?php endif; ?>
              </select>
            </div>
            <div style="display:flex;gap:8px;margin-top:4px;">
              <button type="submit" class="btn btn-primary" style="flex:1;">
                <?= $id ? 'Update Post' : 'Save Post' ?>
              </button>
            </div>
          </div>

          <!-- Category -->
          <div class="editor-panel" style="margin-bottom:16px;">
            <div class="editor-panel-title">Category</div>
            <div class="cat-chips" id="cat-chips">
              <?php $categories->data_seek(0); while($c = $categories->fetch_assoc()): ?>
              <div class="cat-chip <?= ($post['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>"
                   data-id="<?= $c['id'] ?>"
                   onclick="selectCat(this, <?= $c['id'] ?>)">
                <?= htmlspecialchars($c['name']) ?>
              </div>
              <?php endwhile; ?>
            </div>
            <input type="hidden" name="category_id" id="cat_id_input" value="<?= (int)($post['category_id'] ?? 0) ?>">
          </div>

          <!-- Tags -->
          <div class="editor-panel" style="margin-bottom:16px;">
            <div class="editor-panel-title">Tags</div>
            <input type="text" name="tags" class="sidebar-input" placeholder="travel, slow-living, mindfulness" value="<?= htmlspecialchars($post_tags) ?>">
            <div style="font-size:11px;color:var(--ink-faint);margin-top:5px;">Comma-separated</div>
          </div>

          <!-- Cover Image -->
          <div class="editor-panel" style="margin-bottom:16px;">
            <div class="editor-panel-title">Cover Image</div>
            <?php if (!empty($post['cover_image'])): ?>
            <img src="../uploads/<?= htmlspecialchars($post['cover_image']) ?>" style="width:100%;border-radius:4px;margin-bottom:10px;" alt="">
            <?php endif; ?>
            <label style="display:block;border:2px dashed var(--rule);border-radius:4px;padding:20px;text-align:center;cursor:pointer;color:var(--ink-faint);font-size:12px;transition:all 0.15s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--rule)'">
              <div style="font-size:28px;margin-bottom:6px;">🖼</div>
              Click to upload image
              <input type="file" name="cover_image" accept="image/*" style="display:none;" onchange="this.parentElement.querySelector('div').textContent = this.files[0]?.name || '🖼'">
            </label>
          </div>

          <!-- SEO Preview -->
          <div class="editor-panel">
            <div class="editor-panel-title">SEO Preview</div>
            <div style="background:var(--paper);border:1px solid var(--rule);border-radius:4px;padding:12px;">
              <div style="font-size:13px;color:#1a0dab;font-weight:500;margin-bottom:3px;" id="seo-title">Your post title</div>
              <div style="font-size:11px;color:#006621;margin-bottom:5px;" id="seo-url"><?= SITE_URL ?>/post/your-slug</div>
              <div style="font-size:12px;color:var(--ink-muted);line-height:1.4;" id="seo-desc">Your excerpt will appear here…</div>
            </div>
          </div>
        </div>

      </div>
    </form>
  </div>
</div>

<script>
// Sync contenteditable to hidden textarea
function syncBody() {
  document.getElementById('post_body').value = document.getElementById('editor-area').innerHTML;
}

// Word count
function updateWC() {
  const text = document.getElementById('editor-area').innerText || '';
  const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
  document.getElementById('wc').textContent = words + ' words · ~' + Math.max(1, Math.round(words/200)) + ' min read';
}

document.getElementById('editor-area').addEventListener('input', function() {
  syncBody();
  updateWC();
  // SEO update
  document.getElementById('seo-title').textContent = document.querySelector('.title-input').value || 'Your post title';
});

document.querySelector('.title-input').addEventListener('input', function() {
  document.getElementById('seo-title').textContent = this.value || 'Your post title';
  document.getElementById('seo-url').textContent = '<?= SITE_URL ?>/post/' + this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
});

// Get excerpt field
document.querySelectorAll('textarea[name=excerpt]')[0]?.addEventListener('input', function() {
  document.getElementById('seo-desc').textContent = this.value || 'Your excerpt will appear here…';
});

// Category chips
function selectCat(el, id) {
  document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('cat_id_input').value = id;
}

// Rich text
function fmt(cmd) { document.execCommand(cmd, false, null); syncBody(); }

function wrapTag(tag) {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  const range = sel.getRangeAt(0);
  const el = document.createElement(tag);
  el.appendChild(range.extractContents());
  range.insertNode(el);
  syncBody();
}

function insertBlock(tag) {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  const range = sel.getRangeAt(0);
  const el = document.createElement(tag);
  el.appendChild(range.extractContents());
  range.insertNode(el);
  syncBody();
}

function insertList(type) {
  const cmd = type === 'ul' ? 'insertUnorderedList' : 'insertOrderedList';
  document.execCommand(cmd, false, null);
  syncBody();
}

function insertLink() {
  const url = prompt('Enter URL:');
  if (url) { document.execCommand('createLink', false, url); syncBody(); }
}

function insertCode() {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  const range = sel.getRangeAt(0);
  const pre = document.createElement('pre');
  const code = document.createElement('code');
  code.appendChild(range.extractContents());
  pre.appendChild(code);
  range.insertNode(pre);
  syncBody();
}

// Init
updateWC();
document.getElementById('seo-title').textContent = document.querySelector('.title-input').value || 'Your post title';
</script>
</body>
</html>
