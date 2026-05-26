<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
$theme = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'dark';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $theme === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $GLOBALS['site_name']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen flex">