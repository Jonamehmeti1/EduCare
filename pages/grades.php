<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$teacher_id = 0;
if ($user_role === 'teacher') {
    $t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $t_stmt->execute([$user_id]);
    $teacher = $t_stmt->fetch();
    $teacher_id = $teacher ? $teacher['id'] : 0;
}

$my_classes = [];
$students = [];
if ($user_role === 'teacher') {
    $class_stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ?");
    $class_stmt->execute([$teacher_id]);
    $my_classes = $class_stmt->fetchAll();

    $stu_stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'student' ORDER BY name ASC");
    $students = $stu_stmt->fetchAll();
}

if ($user_role === 'student') {
    $stmt = $pdo->prepare("
        SELECT g.*, u.name as student_name, c.class_name 
        FROM grades g 
        JOIN users u ON g.student_id = u.id 
        JOIN classes c ON g.class_id = c.id
        WHERE g.student_id = ? ORDER BY g.graded_at DESC
    ");
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT g.*, u.name as student_name, c.class_name 
        FROM grades g 
        JOIN users u ON g.student_id = u.id 
        JOIN classes c ON g.class_id = c.id
        WHERE c.teacher_id = ? ORDER BY g.graded_at DESC
    ");
    $stmt->execute([$teacher_id]);
}
$grades_list = $stmt->fetchAll();
?>

<main class="flex-1 p-10 space-y-6 max-h-screen overflow-y-auto">
    <div>
        <h1 class="text-3xl font-bold theme-text">Libri i Notave (AJAX CRUD)</h1>
        [cite_start]<p class="theme-text-muted text-sm mt-1">Shtoni, ndryshoni dhe fshini notat në kohë reale pa bërë refresh faqen[cite: 56, 73].</p>
    </div>

    <div id="ajax-alert" class="hidden p-4 rounded-xl text-sm border font-medium transition-all"></div>

    <?php if ($user_role === 'teacher'): ?>
    <div class="theme-card p-6 rounded-2xl shadow-sm">
        <h2 class="text-lg font-bold mb-4 text-orange-500">📝 Vendos një Notë të Re</h2>
        <form id="ajax-add-grade-form" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Klasa</label>
                <select name="class_id" required class="input-field cursor-pointer">
                    <?php foreach($my_classes as $cls): ?>
                        <option value="<?php echo $cls['id']; ?>"><?php echo htmlspecialchars($cls['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Nxënësi / Studenti</label>
                <select name="student_id" required class="input-field cursor-pointer">
                    <?php foreach($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Lënda / Testi</label>
                <input type="text" name="subject" required placeholder="p.sh. Kollokviumi I" class="input-field">
            </div>
            <div class="flex gap-2">
                <div class="w-24">
                    <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Pikët (%)</label>
                    <input type="number" name="score" min="0" max="100" required placeholder="85" class="input-field">
                </div>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-6 h-12 rounded-xl transition shadow flex-1">Ruaj</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="theme-card p-6 rounded-2xl shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b theme-border theme-text-muted text-xs uppercase tracking-wider">
                    <th class="py-3 px-4">Studenti</th>
                    <th class="py-3 px-4">Klasa</th>
                    <th class="py-3 px-4">Lënda</th>
                    <th class="py-3 px-4">Rezultati</th>
                    <?php if ($user_role === 'teacher'): ?><th class="py-3 px-4 text-right">Veprimet (AJAX)</th><?php endif; ?>
                </tr>
            </thead>
            <tbody id="grades-table-body" class="divide-y divide-zinc-200 dark:divide-zinc-800/30">
                <?php if (count($grades_list) == 0): ?>
                    <tr id="no-grades-row"><td colspan="5" class="theme-text-muted text-sm text-center py-6">Nuk ka asnjë notë të regjistruar për t'u shfaqur.</td></tr>
                <?php else: ?>
                    <?php foreach($grades_list as $row): ?>
                        <tr id="grade-row-<?php echo $row['id']; ?>" class="hover:bg-zinc-500/5 transition">
                            <td class="py-4 px-4 font-bold theme-text"><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td class="py-4 px-4 text-xs"><span class="bg-zinc-500/10 px-2 py-1 rounded theme-text-muted"><?php echo htmlspecialchars($row['class_name']); ?></span></td>
                            <td class="py-4 px-4 theme-text-muted"><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td class="py-4 px-4 font-black text-emerald-500">
                                <span id="score-text-<?php echo $row['id']; ?>"><?php echo $row['score']; ?></span>%
                            </td>
                            <?php if ($user_role === 'teacher'): ?>
                            <td class="py-4 px-4 text-right space-x-2">
                                <button onclick="inlineEdit(<?php echo $row['id']; ?>)" id="btn-edit-<?php echo $row['id']; ?>" class="text-xs font-bold text-blue-500 hover:underline">Ndrysho</button>
                                <button onclick="deleteGrade(<?php echo $row['id']; ?>)" class="text-xs font-bold text-red-500 hover:underline">Fshi</button>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
const alertBox = document.getElementById('ajax-alert');

function showToast(message, isSuccess = true) {
    alertBox.innerHTML = message;
    alertBox.className = isSuccess 
        ? "p-4 rounded-xl text-sm border font-medium bg-emerald-500/10 text-emerald-500 border-emerald-500/20 mb-4"
        : "p-4 rounded-xl text-sm border font-medium bg-red-500/10 text-red-500 border-red-500/20 mb-4";
    alertBox.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => { alertBox.classList.add('hidden'); }, 5000);
}

const addForm = document.getElementById('ajax-add-grade-form');
if (addForm) {
    addForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../api/grades_process.php?action=create', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text()) 
        .then(text => {
            try {
                const res = JSON.parse(text);
                if (res.success) {
                    showToast(res.message, true);
                    addForm.reset();
                    
                    const noRow = document.getElementById('no-grades-row');
                    if(noRow) noRow.remove();

                    const tbody = document.getElementById('grades-table-body');
                    const newRow = document.createElement('tr');
                    newRow.id = `grade-row-${res.data.id}`;
                    newRow.className = "hover:bg-zinc-500/5 transition border-t theme-border";
                    newRow.innerHTML = `
                        <td class="py-4 px-4 font-bold theme-text">${res.data.student_name}</td>
                        <td class="py-4 px-4 text-xs"><span class="bg-zinc-500/10 px-2 py-1 rounded theme-text-muted">${res.data.class_name}</span></td>
                        <td class="py-4 px-4 theme-text-muted">${res.data.subject}</td>
                        <td class="py-4 px-4 font-black text-emerald-500"><span id="score-text-${res.data.id}">${res.data.score}</span>%</td>
                        <td class="py-4 px-4 text-right space-x-2">
                            <button onclick="inlineEdit(${res.data.id})" id="btn-edit-${res.data.id}" class="text-xs font-bold text-blue-500 hover:underline">Ndrysho</button>
                            <button onclick="deleteGrade(${res.data.id})" class="text-xs font-bold text-red-500 hover:underline">Fshi</button>
                        </td>
                    `;
                    tbody.insertBefore(newRow, tbody.firstChild);
                } else {
                    showToast(res.message, false);
                }
            } catch(e) {
                console.error("Gabim në JSON-in e kthyer nga serveri:", text);
                showToast("Gabim kritik i rrjetit. Ju lutem kontrolloni Console (F12).", false);
            }
        })
        .catch(err => showToast("Kërkesa dështoi: " + err, false));
    });
}

function inlineEdit(id) {
    const scoreSpan = document.getElementById(`score-text-${id}`);
    const editBtn = document.getElementById(`btn-edit-${id}`);

    if (editBtn.innerText === "Ruaj") {
        const inputField = document.getElementById(`inline-input-${id}`);
        const updatedScore = inputField.value;

        const params = new URLSearchParams();
        params.append('grade_id', id);
        params.append('score', updatedScore);

        fetch('../api/grades_process.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.text())
        .then(text => {
            try {
                const res = JSON.parse(text);
                if (res.success) {
                    showToast(res.message, true);
                    scoreSpan.innerHTML = updatedScore;
                    editBtn.innerText = "Ndrysho";
                    editBtn.className = "text-xs font-bold text-blue-500 hover:underline";
                } else {
                    showToast(res.message, false);
                }
            } catch(e) {
                console.error("Përgjigjja nuk ishte JSON valid:", text);
                showToast("Gabim gjatë modifikimit të notës.", false);
            }
        })
        .catch(err => showToast("Gabim në rrjet: " + err, false));
    } else {
        const currentScore = scoreSpan.innerText.trim();
        scoreSpan.innerHTML = `<input type="number" id="inline-input-${id}" value="${currentScore}" min="0" max="100" class="w-16 px-1.5 py-0.5 rounded border border-orange-500 text-black bg-white dark:bg-zinc-900 dark:text-white font-bold text-center">`;
        editBtn.innerText = "Ruaj";
        editBtn.className = "text-xs font-bold text-emerald-500 hover:underline font-black";
    }
}

function deleteGrade(id) {
    if (!confirm('A jeni plotësisht të sigurt që dëshironi ta fshini këtë notë përmes AJAX?')) return;

    fetch(`../api/grades_process.php?action=delete&id=${id}`)
    .then(res => res.text())
    .then(text => {
        try {
            const res = JSON.parse(text);
            if (res.success) {
                showToast(res.message, true);
                document.getElementById(`grade-row-${id}`).remove();
            } else {
                showToast(res.message, false);
            }
        } catch(e) {
            console.error("Dështoi fshirja. Serveri ktheu:", text);
            showToast("Gabim gjatë ekzekutimit të fshirjes asinkrone.", false);
        }
    })
    .catch(err => showToast("Gabim komunikimi: " + err, false));
}
</script>
</body>
</html>