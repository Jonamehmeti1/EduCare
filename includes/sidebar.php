<?php
$current_page = basename($_SERVER['PHP_SELF']);
$saved_theme = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'dark';
$current_user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Kron Pajaziti';
$current_user_role = isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'Student';
?>
<div class="w-64 theme-sidebar p-6 flex flex-col justify-between h-screen sticky top-0">
    <div>
        <h1 class="text-2xl font-bold mb-6 tracking-wide text-[var(--accent-orange)]"><?php echo $GLOBALS['site_name']; ?></h1>
        
        <div class="mb-8 p-3 theme-card rounded-xl flex items-center space-x-3 shadow-sm">
            <div class="w-10 h-10 bg-orange-500 text-white rounded-full flex justify-center items-center font-bold text-lg">
                <?php echo strtoupper(substr($current_user_name, 0, 1)); ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold truncate theme-text"><?php echo htmlspecialchars($current_user_name); ?></p>
                <p class="text-xs text-orange-500 font-semibold tracking-wider"><?php echo $current_user_role; ?></p>
            </div>
        </div>

        <p class="text-xs theme-text-muted uppercase tracking-wider font-semibold mb-4">Main Menu</p>
        <nav class="space-y-1">
            <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition <?php echo $current_page == 'dashboard.php' ? 'bg-orange-500 text-white font-medium shadow-md shadow-orange-500/10' : 'theme-text-muted hover:bg-orange-500/10 hover:text-orange-500'; ?>">
                <span>🏠 Home</span>
            </a>
            <a href="lessons.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition <?php echo $current_page == 'lessons.php' ? 'bg-orange-500 text-white font-medium shadow-md shadow-orange-500/10' : 'theme-text-muted hover:bg-orange-500/10 hover:text-orange-500'; ?>">
                <span>📖 Lessons</span>
            </a>
            <a href="grades.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition <?php echo $current_page == 'grades.php' ? 'bg-orange-500 text-white font-medium shadow-md shadow-orange-500/10' : 'theme-text-muted hover:bg-orange-500/10 hover:text-orange-500'; ?>">
                <span>📊 Grades</span>
            </a>
       <?php if ($_SESSION['user_role'] === 'teacher'): ?>
    <a href="manage_classes.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition <?php echo $current_page == 'manage_classes.php' ? 'bg-orange-500 text-white font-medium shadow-md shadow-orange-500/10' : 'theme-text-muted hover:bg-orange-500/10 hover:text-orange-500'; ?>">
        <span>🏫 Manage Classes</span>
    </a>

<?php endif; ?>
        </nav>
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between px-4 py-2 bg-zinc-500/10 rounded-xl">
            <span class="text-sm theme-text-muted">Dark Mode</span>
            <button onclick="toggleThemeSystem()" class="w-10 h-5 bg-zinc-400 dark:bg-zinc-700 rounded-full p-0.5 transition relative">
                <div id="toggle-ball" class="w-4 h-4 bg-white rounded-full transition-transform <?php echo $saved_theme === 'dark' ? 'translate-x-5' : ''; ?>"></div>
            </button>
        </div>
        <a href="login.php?action=logout" class="flex items-center space-x-3 px-4 py-2 text-red-500 hover:bg-red-500/10 rounded-xl transition text-sm font-medium">
            <span>🚪 Logout</span>
        </a>
    </div>
</div>

<script>
function toggleThemeSystem() {
    const htmlElement = document.documentElement;
    let currentTheme = htmlElement.classList.contains('dark') ? 'dark' : 'light';
    let nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.cookie = "theme_mode=" + nextTheme + "; path=/; max-age=" + (30*24*60*60);
    window.location.reload(); // Synchronizes interface blocks instantly
}
</script>
