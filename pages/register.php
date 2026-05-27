<?php
require_once __DIR__ . '/../config/db.php';
session_start();
$error = ""; $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = trim($_POST['password']);
    $role = sanitize($_POST['role']);

    if (!preg_match("/^[a-zA-Z\s]{3,50}$/", $name)) {
        $error = "Emri duhet të përmbajë vetëm shkronja (min 3)!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formati i email-it nuk është valid!";
    } elseif (strlen($password) < 6) {
        $error = "Fjalëkalimi duhet të jetë së paku 6 karaktere!";
    } else {
        try {
            $pdo->beginTransaction();
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) throw new Exception("Ky email ekziston aktualisht!");

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $role]);
            $user_id = $pdo->lastInsertId();

            if ($role === 'teacher') {
                $stmt2 = $pdo->prepare("INSERT INTO teachers (user_id) VALUES (?)");
                $stmt2->execute([$user_id]);
            }
            
            $pdo->commit();
            $success = "Llogaria u krijua! Mund të kyçeni tani.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
$theme = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'dark';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $theme === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8"><title>EduCare - Rregjistrimi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="theme-bg theme-text flex items-center justify-center h-screen transition-colors">
    <div class="theme-card p-8 rounded-2xl w-full max-w-md shadow-xl">
        <h2 class="text-3xl font-bold mb-2 text-center">Krijo Llogari</h2>
        <p class="theme-text-muted text-center mb-6 text-sm">Regjistrohuni në platformën EduCare</p>
        
        <?php if(!empty($error)): ?><div class="bg-red-500/10 text-red-500 p-3 rounded-xl mb-4 text-sm border border-red-500/20"><?php echo $error; ?></div><?php endif; ?>
        <?php if(!empty($success)): ?><div class="bg-emerald-500/10 text-emerald-500 p-3 rounded-xl mb-4 text-sm border border-emerald-500/20"><?php echo $success; ?></div><?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs uppercase tracking-wider theme-text-muted mb-2 font-semibold">Emri Plotë</label>
                <input type="text" name="name" required class="input-field">
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider theme-text-muted mb-2 font-semibold">Email Adresa</label>
                <input type="email" name="email" required class="input-field">
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider theme-text-muted mb-2 font-semibold">Fjalëkalimi</label>
                <input type="password" name="password" required class="input-field">
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider theme-text-muted mb-2 font-semibold">Roli juaj</label>
                <select name="role" class="input-field cursor-pointer">
                    <option value="student">Student / Nxënës</option>
                    <option value="teacher">Teacher / Profesor</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition shadow-lg mt-2">Regjistrohu</button>
        </form>
        <p class="text-xs text-center theme-text-muted mt-4">Keni llogari? <a href="login.php" class="text-orange-500 font-medium hover:underline">Kyçu këtu</a></p>
    </div>
</body>
</html>
