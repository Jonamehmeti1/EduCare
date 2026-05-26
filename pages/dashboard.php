<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
    $_SESSION['user_name'] = "Kron Pajaziti";
    $_SESSION['user_role'] = "student"; 
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$msg_success = "";
$msg_error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['action_create_class']) && $user_role === 'teacher') {
        $class_name = sanitize($_POST['class_name']);
        
        $t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $t_stmt->execute([$user_id]);
        $teacher = $t_stmt->fetch();
        
        if ($teacher) {
            $class_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6)); 
            $insert = $pdo->prepare("INSERT INTO classes (class_name, teacher_id, class_code) VALUES (?, ?, ?)");
            if ($insert->execute([$class_name, $teacher['id'], $class_code])) {
                $msg_success = "Klasa '$class_name' u krijua me sukses! Kodi: <b>$class_code</b>";
            }
        }
    }
    
    if (isset($_POST['action_join_class']) && $user_role === 'student') {
        $input_code = strtoupper(trim($_POST['class_code']));
        
        $c_stmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = ?");
        $c_stmt->execute([$input_code]);
        $target_class = $c_stmt->fetch();
        
        if ($target_class) {
            $check = $pdo->prepare("SELECT * FROM class_enrollments WHERE class_id = ? AND student_id = ?");
            $check->execute([$target_class['id'], $user_id]);
            
            if ($check->fetch()) {
                $msg_error = "Ju jeni anëtar i kësaj klase aktualisht!";
            } else {
                $join = $pdo->prepare("INSERT INTO class_enrollments (class_id, student_id) VALUES (?, ?)");
                $join->execute([$target_class['id'], $user_id]);
                $msg_success = "U bashkuat me klasën me sukses!";
            }
        } else {
            $msg_error = "Kodi i dhënë nuk ekziston! Ju lutem provoni përsëri.";
        }
    }
}
?>

<main class="flex-1 p-10 space-y-8 max-h-screen overflow-y-auto">
    <?php if(!empty($msg_success)): ?>
        <div class="bg-emerald-500/10 text-emerald-500 p-4 rounded-xl text-sm border border-emerald-500/20"><?php echo $msg_success; ?></div>
    <?php endif; ?>
    <?php if(!empty($msg_error)): ?>
        <div class="bg-red-500/10 text-red-500 p-4 rounded-xl text-sm border border-red-500/20"><?php echo $msg_error; ?></div>
    <?php endif; ?>

    <?php if ($user_role === 'teacher'): 
        $t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $t_stmt->execute([$user_id]);
        $teacher = $t_stmt->fetch();
        $teacher_id = $teacher ? $teacher['id'] : 0;

        $classes_query = $pdo->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM class_enrollments WHERE class_id = c.id) as total_students,
                   (SELECT AVG(score) FROM grades WHERE class_id = c.id) as class_average
            FROM classes c 
            WHERE c.teacher_id = ? 
            ORDER BY c.created_at DESC
        ");
        $classes_query->execute([$teacher_id]);
        $my_classes = $classes_query->fetchAll();
    ?>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight theme-text">Paneli i Profesorit</h1>
                <p class="theme-text-muted text-sm mt-1">Menaxhoni klasat, oraret dhe vlerësoni performancën e studentëve.</p>
            </div>
            
            <form action="dashboard.php" method="POST" class="flex gap-2 bg-transparent theme-card p-2 rounded-2xl w-full md:w-auto shadow-sm">
                <input type="hidden" name="action_create_class" value="1">
                <input type="text" name="class_name" required placeholder="Emri i Klasës së Re (p.sh. 12B)" class="input-field py-2 text-xs">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold px-4 rounded-xl text-xs whitespace-nowrap transition">Krijo Klasë</button>
            </form>
        </div>

        <h2 class="text-xl font-bold theme-text mt-8">Klasat Tuaja Aktuale</h2>
        <?php if (count($my_classes) == 0): ?>
            <div class="theme-card p-8 rounded-2xl text-center text-sm theme-text-muted">Nuk keni krijuar asnjë klasë ende. Përdorni formën lart për të filluar.</div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($my_classes as $cls): ?>
                    <div class="theme-card p-6 rounded-2xl flex flex-col justify-between space-y-4 shadow-sm hover:border-orange-500/40 transition-all">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="bg-orange-500/10 text-orange-500 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">Kodi: <?php echo $cls['class_code']; ?></span>
                                <span class="theme-text-muted text-xs"><?php echo $cls['total_students']; ?> Studentë</span>
                            </div>
                            <h3 class="text-2xl font-bold theme-text mb-1"><?php echo htmlspecialchars($cls['class_name']); ?></h3>
                        </div>
                        <div class="pt-4 border-t theme-border flex justify-between items-center">
                            <span class="text-xs theme-text-muted font-medium">Mesatarja e Klasës:</span>
                            <span class="text-lg font-black <?php echo $cls['class_average'] >= 80 ? 'text-emerald-500' : ($cls['class_average'] > 0 ? 'text-yellow-500' : 'theme-text-muted'); ?>">
                                <?php echo $cls['class_average'] ? round($cls['class_average'], 1) . '%' : 'Nuk ka nota'; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


    <?php else: 
        $avg_stmt = $pdo->prepare("SELECT AVG(score) as user_avg FROM grades WHERE student_id = ?");
        $avg_stmt->execute([$user_id]);
        $avg_data = $avg_stmt->fetch();
        $student_average = $avg_data['user_avg'] ? round($avg_data['user_avg'], 1) : null;

        $joined_stmt = $pdo->prepare("
            SELECT c.*, u.name as teacher_name 
            FROM class_enrollments ce 
            JOIN classes c ON ce.class_id = c.id 
            JOIN teachers t ON c.teacher_id = t.id
            JOIN users u ON t.user_id = u.id
            WHERE ce.student_id = ?
        ");
        $joined_stmt->execute([$user_id]);
        $my_joined_classes = $joined_stmt->fetchAll();

        $timetable_stmt = $pdo->prepare("
            SELECT l.*, c.class_name, u.name as teacher_name
            FROM lessons l
            JOIN classes c ON l.class_id = c.id
            JOIN teachers t ON c.teacher_id = t.id
            JOIN users u ON t.user_id = u.id
            JOIN class_enrollments ce ON c.id = ce.class_id
            WHERE ce.student_id = ?
            ORDER BY FIELD(l.day_of_week, 'E Hënë', 'E Martë', 'E Mërkurë', 'E Enjte', 'E Premte'), l.time_slot ASC
        ");
        $timetable_stmt->execute([$user_id]);
        $lessons_timetable = $timetable_stmt->fetchAll();
    ?>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight theme-text">Mirëseerdhe, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
                <p class="theme-text-muted text-sm mt-1">Këtu është pasqyra juaj akademike, orari i mësimeve dhe klasat ku merrni pjesë.</p>
            </div>
            
            <form action="dashboard.php" method="POST" class="flex gap-2 bg-transparent theme-card p-2 rounded-2xl w-full md:w-auto shadow-sm">
                <input type="hidden" name="action_join_class" value="1">
                <input type="text" name="class_code" required placeholder="Shkruaj Kodin e Klasës" class="input-field py-2 text-xs">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold px-5 rounded-xl text-xs whitespace-nowrap transition">Bashkohu</button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="theme-card p-6 rounded-2xl flex items-center space-x-4 shadow-sm">
                <div class="p-4 bg-orange-500/10 text-orange-500 rounded-xl font-black text-2xl">📊</div>
                <div>
                    <p class="text-xs font-bold theme-text-muted uppercase tracking-wider">Nota Mesatare</p>
                    <p class="text-3xl font-black theme-text mt-0.5"><?php echo $student_average ? $student_average . '%' : 'Nuk ka nota'; ?></p>
                </div>
            </div>
            <div class="theme-card p-6 rounded-2xl flex items-center space-x-4 shadow-sm">
                <div class="p-4 bg-blue-500/10 text-blue-500 rounded-xl font-black text-2xl">🏫</div>
                <div>
                    <p class="text-xs font-bold theme-text-muted uppercase tracking-wider">Klasat e Regjistruara</p>
                    <p class="text-3xl font-black theme-text mt-0.5"><?php echo count($my_joined_classes); ?></p>
                </div>
            </div>
            <div class="theme-card p-6 rounded-2xl flex items-center space-x-4 shadow-sm">
                <div class="p-4 bg-emerald-500/10 text-emerald-500 rounded-xl font-black text-2xl">⏰</div>
                <div>
                    <p class="text-xs font-bold theme-text-muted uppercase tracking-wider">Mësime Këtë Javë</p>
                    <p class="text-3xl font-black theme-text mt-0.5"><?php echo count($lessons_timetable); ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-4">
            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-xl font-bold theme-text">Orari i Mësimeve (Timetable)</h2>
                <div class="theme-card rounded-2xl p-6 shadow-sm overflow-hidden">
                    <?php if (count($lessons_timetable) == 0): ?>
                        <p class="theme-text-muted text-sm text-center py-6">Nuk ka asnjë orar të planifikuar. Bashkohuni me një klasë që ka leksione aktuale.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($lessons_timetable as $lesson): ?>
                                <div class="flex items-center justify-between p-3 bg-zinc-500/5 hover:bg-zinc-500/10 rounded-xl border border-transparent hover:border-zinc-500/10 transition">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-24 text-xs font-bold uppercase text-orange-500 bg-orange-500/10 px-2.5 py-1.5 rounded-lg text-center">
                                            <?php echo htmlspecialchars($lesson['day_of_week']); ?>
                                        </div>
                                        <div>
                                            <h4 class="font-bold theme-text text-sm"><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                            <p class="text-xs theme-text-muted">Klasa: <?php echo htmlspecialchars($lesson['class_name']); ?> • Prof: <?php echo htmlspecialchars($lesson['teacher_name']); ?></p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-mono font-bold theme-text-muted bg-zinc-500/10 px-3 py-1 rounded-md"><?php echo htmlspecialchars($lesson['time_slot']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-4">
                <h2 class="text-xl font-bold theme-text">Klasat e Mia</h2>
                <div class="space-y-3">
                    <?php if (count($my_joined_classes) == 0): ?>
                        <div class="theme-card p-6 rounded-2xl text-center text-xs theme-text-muted">Nuk jeni bashkuar me asnjë klasë ende.</div>
                    <?php else: ?>
                        <?php foreach ($my_joined_classes as $jc): ?>
                            <div class="theme-card p-4 rounded-xl shadow-sm flex justify-between items-center">
                                <div>
                                    <h4 class="font-bold theme-text"><?php echo htmlspecialchars($jc['class_name']); ?></h4>
                                    <p class="text-xs theme-text-muted">Prof: <?php echo htmlspecialchars($jc['teacher_name']); ?></p>
                                </div>
                                <span class="text-[10px] bg-zinc-500/10 theme-text-muted font-bold px-2 py-1 rounded-md">Aktive</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>
</body>
</html>