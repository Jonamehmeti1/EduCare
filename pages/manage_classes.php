<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Lejohet vetëm për profesorët
if ($user_role !== 'teacher') {
    echo "<script>window.location.href='dashboard.php';</script>";
    exit;
}

// Merr ID-në e profesorit
$t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$t_stmt->execute([$user_id]);
$teacher = $t_stmt->fetch();
$teacher_id = $teacher ? $teacher['id'] : 0;

// Merr listën e klasave të këtëj profesori
$stmt = $pdo->prepare("SELECT id, class_name, created_at FROM classes WHERE teacher_id = ? ORDER BY id DESC");
$stmt->execute([$teacher_id]);
$my_classes = $stmt->fetchAll();
?>

<main class="flex-1 p-10 space-y-6 max-h-screen overflow-y-auto">
    <div>
        <h1 class="text-3xl font-bold theme-text">Menaxhimi i Klasave (AJAX CRUD)</h1>
        <p class="theme-text-muted text-sm mt-1">Krijoni, ndryshoni dhe fshini klasat tuaja në mënyrë asinkrone pa refresh.</p>
    </div>

    <div id="class-ajax-alert" class="hidden p-4 rounded-xl text-sm border font-medium transition-all mb-4"></div>

    <div class="theme-card p-6 rounded-2xl shadow-sm">
        <h2 class="text-lg font-bold mb-3 text-orange-500">🏫 Shto një Klasë të Re</h2>
        <form id="ajax-add-class-form" class="flex gap-4 items-end max-w-xl">
            <div class="flex-1">
                <label class="block text-xs uppercase font-semibold theme-text-muted mb-1">Emri i Klasës / Paraleles</label>
                <input type="text" name="class_name" required placeholder="p.sh. Klasa XI-A" class="input-field">
            </div>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-6 h-12 rounded-xl transition shadow">Shto Klasën</button>
        </form>
    </div>

    <div class="theme-card p-6 rounded-2xl shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b theme-border theme-text-muted text-xs uppercase tracking-wider">
                    <th class="py-3 px-4">ID</th>
                    <th class="py-3 px-4">Emri i Klasës</th>
                    <th class="py-3 px-4 text-right">Veprimet (AJAX)</th>
                </tr>
            </thead>
            <tbody id="classes-table-body" class="divide-y divide-zinc-200 dark:divide-zinc-800/30">
                <?php if (count($my_classes) == 0): ?>
                    <tr id="no-classes-row"><td colspan="3" class="theme-text-muted text-sm text-center py-6">Nuk keni asnjë klasë të regjistruar.</td></tr>
                <?php else: ?>
                    <?php foreach($my_classes as $row): ?>
                        <tr id="class-row-<?php echo $row['id']; ?>" class="hover:bg-zinc-500/5 transition">
                            <td class="py-4 px-4 font-mono text-xs theme-text-muted">#<?php echo $row['id']; ?></td>
                            <td class="py-4 px-4 font-bold theme-text">
                                <span id="class-text-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['class_name']); ?></span>
                            </td>
                            <td class="py-4 px-4 text-right space-x-2">
                                <button onclick="inlineEditClass(<?php echo $row['id']; ?>)" id="btn-edit-class-<?php echo $row['id']; ?>" class="text-xs font-bold text-blue-500 hover:underline">Ndrysho</button>
                                <button onclick="deleteClass(<?php echo $row['id']; ?>)" class="text-xs font-bold text-red-500 hover:underline">Fshi</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
const toastBox = document.getElementById('class-ajax-alert');

function showClassToast(message, isSuccess = true) {
    toastBox.innerHTML = message;
    toastBox.className = isSuccess 
        ? "p-4 rounded-xl text-sm border font-medium bg-emerald-500/10 text-emerald-500 border-emerald-500/20 mb-4"
        : "p-4 rounded-xl text-sm border font-medium bg-red-500/10 text-red-500 border-red-500/20 mb-4";
    toastBox.classList.remove('hidden');
    setTimeout(() => { toastBox.classList.add('hidden'); }, 4000);
}

// 1. ASYNC CREATE (SHTIMI)
const classForm = document.getElementById('ajax-add-class-form');
if (classForm) {
    classForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../api/classes_process.php?action=create', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(text => {
            try {
                const res = JSON.parse(text);
                if (res.success) {
                    showClassToast(res.message, true);
                    classForm.reset();
                    
                    const noRow = document.getElementById('no-classes-row');
                    if(noRow) noRow.remove();

                    const tbody = document.getElementById('classes-table-body');
                    const newRow = document.createElement('tr');
                    newRow.id = `class-row-${res.data.id}`;
                    newRow.className = "hover:bg-zinc-500/5 transition border-t theme-border";
                    newRow.innerHTML = `
                        <td class="py-4 px-4 font-mono text-xs theme-text-muted">#${res.data.id}</td>
                        <td class="py-4 px-4 font-bold theme-text"><span id="class-text-${res.data.id}">${res.data.class_name}</span></td>
                        <td class="py-4 px-4 text-right space-x-2">
                            <button onclick="inlineEditClass(${res.data.id})" id="btn-edit-class-${res.data.id}" class="text-xs font-bold text-blue-500 hover:underline">Ndrysho</button>
                            <button onclick="deleteClass(${res.data.id})" class="text-xs font-bold text-red-500 hover:underline">Fshi</button>
                        </td>
                    `;
                    tbody.insertBefore(newRow, tbody.firstChild);
                } else {
                    showClassToast(res.message, false);
                }
            } catch(e) {
                showClassToast("Gabim gjatë procesimit të të dhënave.", false);
            }
        });
    });
}

// 2. ASYNC UPDATE INLINE (NDRYSHIMI)
function inlineEditClass(id) {
    const textSpan = document.getElementById(`class-text-${id}`);
    const editBtn = document.getElementById(`btn-edit-class-${id}`);

    if (editBtn.innerText === "Ruaj") {
        const inputField = document.getElementById(`inline-class-input-${id}`);
        const updatedName = inputField.value;

        const params = new URLSearchParams();
        params.append('class_id', id);
        params.append('class_name', updatedName);

        fetch('../api/classes_process.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.text())
        .then(text => {
            try {
                const res = JSON.parse(text);
                if (res.success) {
                    showClassToast(res.message, true);
                    textSpan.innerHTML = updatedName;
                    editBtn.innerText = "Ndrysho";
                    editBtn.className = "text-xs font-bold text-blue-500 hover:underline";
                } else {
                    showClassToast(res.message, false);
                }
            } catch(e) {
                showClassToast("Gabim gjatë modifikimit.", false);
            }
        });
    } else {
        const currentName = textSpan.innerText.trim();
        textSpan.innerHTML = `<input type="text" id="inline-class-input-${id}" value="${currentName}" required class="px-2 py-1 rounded border border-orange-500 text-black bg-white dark:bg-zinc-900 dark:text-white font-bold text-sm">`;
        editBtn.innerText = "Ruaj";
        editBtn.className = "text-xs font-bold text-emerald-500 hover:underline font-black";
    }
}

// 3. ASYNC DELETE (FSHIRJA)
function deleteClass(id) {
    if (!confirm('KUJDES: Fshirja e klasës do të fshijë automatikisht të gjitha notat dhe nxënësit e regjistruar në këtë klasë! A jeni të sigurt?')) return;

    fetch(`../api/classes_process.php?action=delete&id=${id}`)
    .then(res => res.text())
    .then(text => {
        try {
            const res = JSON.parse(text);
            if (res.success) {
                showClassToast(res.message, true);
                document.getElementById(`class-row-${id}`).remove();
            } else {
                showClassToast(res.message, false);
            }
        } catch(e) {
            showClassToast("Dështoi fshirja asinkrone.", false);
        }
    });
}
</script>
</body>
</html>
