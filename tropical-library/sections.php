<?php
require_once 'config/database.php';
requireAuth();

$section = $_GET['section'] ?? 'all';

if ($section == 'read') $status = 'read';
elseif ($section == 'reading') $status = 'reading';
elseif ($section == 'want') $status = 'want';
else $status = 'all';

header("Location: index.php?status=$status");
exit;
?>