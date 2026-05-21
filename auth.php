<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function has_role($roles) {
    $user = current_user();
    if (!$user) return false;
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array($user['role'], $roles);
}

function require_login($redirect = '../index.php') {
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_role($roles, $redirect = '../index.php') {
    require_login($redirect);
    if (!has_role($roles)) {
        die('<p style="font-family:sans-serif;padding:40px;color:#c0392b;">Access denied. Insufficient permissions.</p>');
    }
}

function login_user($conn, $username, $password) {
    $u = escape($conn, $username);
    $row = $conn->query("SELECT * FROM users WHERE username='$u' OR email='$u' LIMIT 1")->fetch_assoc();
    if ($row && password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user'] = $row;
        return true;
    }
    return false;
}

function logout_user() {
    session_destroy();
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function time_ago($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d === 0 && $diff->h === 0) return $diff->i . 'm ago';
    if ($diff->d === 0) return $diff->h . 'h ago';
    if ($diff->d < 7) return $diff->d . 'd ago';
    return $ago->format('M j, Y');
}
?>
