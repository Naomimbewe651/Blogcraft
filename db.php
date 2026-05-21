<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'blogcraft');
define('SITE_NAME', 'BlogCraft');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) die('DB Error: ' . $conn->connect_error);
    $conn->set_charset('utf8mb4');
    return $conn;
}
function escape($conn, $val) { return $conn->real_escape_string(trim($val)); }
?>