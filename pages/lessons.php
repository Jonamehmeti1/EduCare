<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$msg_success = "";
$msg_error = "";

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Fetch teacher's context row ID
$teacher_id = 0;
if ($user_role === 'teacher') {
    $t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $t_stmt->execute([$user_id]);
    $teacher = $t_stmt->fetch();
    $teacher_id = $teacher ? $teacher['id'] : 0;
}

// --- CONTROLLER ACTIONS (CREATE, UPDATE, DELETE) ---

// 1. DELETE ACTION
if (isset($_GET['action']) && $_GET['action'] === 'delete' && $user_role === 'teacher') {
    $id_to_delete = (int)$_GET['id'];
    try {
        // Security check: ensure this lesson belongs to a class owned by this teacher
        $check = $pdo->prepare("SELECT l.id FROM lessons l JOIN classes c ON l.class_id = c.id WHERE l.id = ? AND c.teacher_id = ?");
        $check->execute([$id_to_delete, $teacher_id]);
        if ($check->fetch()) {
            $del = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
            $del->execute([$id_to_delete]);
            $msg_success = "Mësimi u fshi nga orari me sukses!";
        } else {
            $msg_error = "Nuk keni autorizim për të fshirë këtë mësim.";
        }
    } catch (Exception $e) {
        $msg_error = "Gabim gjatë fshirjes: " . htmlspecialchars($e->getMessage());
    }
}

// 2. CREATE ACTION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_lesson']) && $user_role === 'teacher') {
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $class_id = (int)$_POST['class_id'];
    $day_of_week = sanitize($_POST['day_of_week']);
    $time_slot = sanitize($_POST['time_slot']);
    
    try {
        if (empty($title) || empty($time_slot)) {
            throw new Exception("Titulli dhe Ora janë fusha të detyrueshme!");
        }
        $insert = $pdo->prepare("INSERT INTO lessons (title, description, teacher_id, class_id, day_of_week, time_slot) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute([$title, $desc, $teacher_id, $class_id, $day_of_week, $time_slot]);
        $msg_success = "Mësimi i ri u planifikua me sukses!";
    } catch (Exception $e) {
        $msg_error = "Gabim gjatë ruajtjes: " . htmlspecialchars($e->getMessage());
    }
}

// 3. UPDATE ACTION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_lesson']) && $user_role === 'teacher') {
    $lesson_id = (int)$_POST['lesson_id'];
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $day_of_week = sanitize($_POST['day_of_week']);
    $time_slot = sanitize($_POST['time_slot']);
    
    try {
        $update = $pdo->prepare("UPDATE lessons SET title = ?, description = ?, day_of_week = ?, time_slot = ? WHERE id = ? AND teacher_id = ?");
        $update->execute([$title, $desc, $day_of_week, $time_slot, $lesson_id, $teacher_id]);
        $msg_success = "Të dhënat e mësimit u përditësuan me sukses!";
    } catch (Exception $e) {
        $msg_error = "Gabim gjatë modifikimit: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch single record for EDIT form pre-population
$edit_lesson = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && $user_role === 'teacher') {
    $edit_stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ? AND teacher_id = ?");
    $edit_stmt->execute([(int)$_GET['id'], $teacher_id]);
    $edit_lesson = $edit_stmt->fetch();
}

// Fetch lists for views
$my_classes = [];
if ($user_role === 'teacher') {
    $class_stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ?");
    $class_stmt->execute([$teacher_id]);
    $my_classes = $class_stmt->fetchAll();
}

// READ QUERY
if ($user_role === 'student') {
    $stmt = $pdo->prepare("
        SELECT l.*, u.name as teacher_name, c.class_name 
        FROM lessons l 
        JOIN teachers t ON l.teacher_id = t.id 
        JOIN users u ON t.user_id = u.id 
        JOIN classes c ON l.class_id = c.id
        JOIN class_enrollments ce ON c.id = ce.class_id
        WHERE ce.student_id = ?
        ORDER BY FIELD(l.day_of_week, 'E Hënë', 'E Martë', 'E Mërkurë', 'E Enjte', 'E Premte'), l.time_slot ASC
    ");
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT l.*, u.name as teacher_name, c.class_name 
        FROM lessons l 
        JOIN teachers t ON l.teacher_id = t.id 
        JOIN users u ON t.user_id = u.id 
        JOIN classes c ON l.class_id = c.id
        WHERE t.id = ?
        ORDER BY FIELD(l.day_of_week, 'E Hënë', 'E Martë', 'E Mërkurë', 'E Enjte', 'E Premte'), l.time_slot ASC
    ");
    $stmt->execute([$teacher_id]);
}
$lessons = $stmt->fetchAll();
?>

<main class="flex-1 p-10 space-y-8 max-h-screen overflow-y-auto">
    <div>
        <h1 class="text-3xl font-bold tracking-tight theme-text">Menaxhimi i Mësimeve (CRUD)</h1>
        <p class="theme-text-muted text-sm mt-1">Sistemi i plotë i modifikimit të orareve akademike.</p>
    </div>

    <?php if(!empty($msg_success)): ?><div class="bg-emerald-500/10 text-emerald-500 p-4 rounded-xl text-sm border border-emerald-500/20"><?php echo $msg_success; ?></div><?php endif; ?>
    <?php if(!empty($msg_error)): ?><div class="bg-red-500/10 text-red-500 p-4 rounded-xl text-sm border border-red-500/20"><?php echo $msg_error; ?></div><?php endif; ?>

    <?php if ($edit_lesson): ?>
    <div class="theme-card p-6 border-l-4 border-orange-500 rounded-2xl shadow-md max-w-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-orange-500">✏️ Modifiko Mësimin</h2>
            <a href="lessons.php" class="text-xs theme-text-muted hover:text-orange-500 font-bold">Anulo</a>
        </div>
        <form action="lessons.php" method="POST" class="space-y-4">
            <input type="hidden" name="update_lesson" value="1">
            <input type="hidden" name="lesson_id" value="<?php echo $edit_lesson['id']; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Titulli i Mësimit</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($edit_lesson['title']); ?>" required class="input-field">
                </div>
                <div>
                    <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Ora / Koha</label>
                    <input type="text" name="time_slot" value="<?php echo htmlspecialchars($edit_lesson['time_slot']); ?>" required class="input-field">
                </div>
            </div>
            <div>
                <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Dita e Javës</label>
                <select name="day_of_week" class="input-field cursor-pointer">
                    <?php foreach(['E Hënë', 'E Martë', 'E Mërkurë', 'E Enjte', 'E Premte'] as $day): ?>
                        <option value="<?php echo $day; ?>" <?php echo ($edit_lesson['day_of_week'] == $day) ? 'selected' : ''; ?>><?php echo $day; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Përshkrimi</label>
                <textarea name="description" class="input-field h-20"><?php echo htmlspecialchars($edit_lesson['description']); ?></textarea>
            </div>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold px-6 py-2.5 rounded-xl transition shadow">Ruaj Ndryshimet</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($user_role === 'teacher' && !$edit_lesson): ?>
    <div class="theme-card p-6 max-w-2xl rounded-2xl shadow-sm">
        <h2 class="text-lg font-bold mb-4 text-orange-500">➕ Shto Mësim të Ri</h2>
        <form action="lessons.php" method="POST" class="space-y-4">
            <input type="hidden" name="add_lesson" value="1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Zgjidh Klasën</label>
                    <select name="class_id" required class="input-field cursor-pointer">
                        <?php foreach($my_classes as $cls): ?>
                            <option value="<?php echo $cls['id']; ?>"><?php echo htmlspecialchars($cls['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Titulli i Mësimit</label>
                    <input type="text" name="title" required placeholder="p.sh. Arkitektura e Kompjuterëve" class="input-field">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Dita</label>
                    <select name="day_of_week" required class="input-field cursor-pointer">
                        <option value="E Hënë">E Hënë</option>
                        <option value="E Martë">E Martë</option>
                        <option value="E Mërkurë">E Mërkurë</option>
                        <option value="E Enjte">E Enjte</option>
                        <option value="E Premte">E Premte</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Ora / Koha</label>
                    <input type="text" name="time_slot" required placeholder="p.sh. 13:00 - 14:30" class="input-field">
                </div>
            </div>
            <div>
                <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Përshkrimi</label>
                <textarea name="description" required class="input-field h-20" placeholder="Objektivat kryesore të ligjëratës..."></textarea>
            </div>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold px-6 py-3 rounded-xl transition">Shto në Orar</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if (count($lessons) == 0): ?>
            <div class="theme-card p-6 rounded-2xl col-span-2 text-center theme-text-muted text-sm">Nuk ka asnjë mësim në orar.</div>
        <?php else: ?>
            <?php foreach ($lessons as $lesson): ?>
                <div class="theme-card p-6 rounded-2xl flex flex-col justify-between space-y-4 shadow-sm relative group border border-transparent hover:border-zinc-500/10 transition">
                    <div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-xs text-orange-500 font-bold uppercase tracking-wider bg-orange-500/10 px-2 py-0.5 rounded">Klasa: <?php echo htmlspecialchars($lesson['class_name']); ?></span>
                                <h3 class="text-xl font-bold theme-text mt-2"><?php echo htmlspecialchars($lesson['title']); ?></h3>
                            </div>
                            <span class="text-xs font-mono font-bold theme-text-muted bg-zinc-500/10 px-2 py-1 rounded"><?php echo htmlspecialchars($lesson['day_of_week']); ?> @ <?php echo htmlspecialchars($lesson['time_slot']); ?></span>
                        </div>
                        <p class="text-sm theme-text-muted mt-2"><?php echo htmlspecialchars($lesson['description']); ?></p>
                    </div>
                    
                    <div class="flex justify-between items-center pt-3 border-t theme-border">
                        <span class="text-xs theme-text-muted">Prof: <b class="theme-text"><?php echo htmlspecialchars($lesson['teacher_name']); ?></b></span>
                        
                        <?php if ($user_role === 'teacher'): ?>
                        <div class="flex gap-2">
                            <a href="lessons.php?action=edit&id=<?php echo $lesson['id']; ?>" class="text-xs font-bold px-2.5 py-1 rounded bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition">Ndrysho</a>
                            <a href="lessons.php?action=delete&id=<?php echo $lesson['id']; ?>" onclick="return confirm('A jeni të sigurt që dëshironi ta fshini këtë mësim?')" class="text-xs font-bold px-2.5 py-1 rounded bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition">Fshi</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
