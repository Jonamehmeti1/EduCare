<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Logout Action
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize($_POST['email']);
    $password = trim($_POST['password']);

    // Server-Side Regular Expression Validations
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $email)) {
        $error = "Formati i email-it nuk është valid!"; // Invalid email regex notice
    } else {
        // Phase I: Hardcoded Account Verification fallback checking
        if ($email === "admin@educare.com" && $password === "admin123") {
            $_SESSION['user_id'] = 0;
            $_SESSION['user_name'] = "System Admin";
            $_SESSION['user_role'] = "admin"; // Role differentiation
            header("Location: dashboard.php");
            exit;
        }

        // Phase II: Database query handling using secure Prepared Statements
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Kredencialet e gabuara!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EduCare - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white flex items-center justify-center h-screen">
    <div class="bg-zinc-900 p-8 rounded-2xl w-full max-w-md border border-zinc-800 shadow-xl">
        <h2 class="text-3xl font-bold mb-2 text-center">Mirëseerdhët</h2>
        <p class="text-zinc-500 text-center mb-6 text-sm">Identifikohuni në llogarinë tuaj EduCare</p>
        
        <?php if(!empty($error)): ?>
            <div class="bg-red-500/10 text-red-400 p-3 rounded-xl mb-4 text-sm border border-red-500/20"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs uppercase tracking-wider text-zinc-400 mb-2 font-semibold">Email Adresa</label>
                <input type="email" name="email" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-orange-500 transition">
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider text-zinc-400 mb-2 font-semibold">Fëjalëkalimi</label>
                <input type="password" name="password" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-orange-500 transition">
            </div>
            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 font-semibold py-3 rounded-xl transition shadow-lg shadow-orange-500/20 mt-2">Kyçu</button>
        </form>
    </div>
</body>
</html>
