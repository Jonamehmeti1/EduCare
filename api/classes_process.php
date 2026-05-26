<?php
ob_start();
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
    } else {
        require_once __DIR__ . '/../includes/header.php';
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Lidhja me databazë dështoi: ' . $e->getMessage()]);
    exit;
}

// Siguria: Vetëm profesorët mund të menaxhojnë klasat e tyre
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Qasje e paautorizuar!']);
    exit;
}

$user_id = $_SESSION['user_id'];
$t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$t_stmt->execute([$user_id]);
$teacher = $t_stmt->fetch();
$teacher_id = $teacher ? $teacher['id'] : 0;

$action = $_GET['action'] ?? '';

// 1. AJAX: KRIJO NJË KLASË TË RE (CREATE)
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = htmlspecialchars(strip_tags(trim($_POST['class_name'] ?? '')));

    if (empty($class_name)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Emri i klasës nuk mund të jetë i zbrazët!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO classes (class_name, teacher_id) VALUES (?, ?)");
        $stmt->execute([$class_name, $teacher_id]);
        $new_id = $pdo->lastInsertId();

        ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Klasa u krijua me sukses përmes AJAX!', 
            'data' => ['id' => $new_id, 'class_name' => $class_name]
        ]);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Gabim gjatë ruajtjes: ' . $e->getMessage()]);
    }
    exit;
}

// 2. AJAX: MODIFIKO EMRI E KLASËS (UPDATE)
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = (int)($_POST['class_id'] ?? 0);
    $class_name = htmlspecialchars(strip_tags(trim($_POST['class_name'] ?? '')));

    if ($class_id <= 0 || empty($class_name)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Të dhëna të pasakta për ndryshim!']);
        exit;
    }

    try {
        // Sigurohemi që profesori mund të ndryshojë vetëm klasën që është e tij
        $stmt = $pdo->prepare("UPDATE classes SET class_name = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$class_name, $class_id, $teacher_id]);
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Emri i klasës u përditësua me sukses!']);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Gabim gjatë përditësimit: ' . $e->getMessage()]);
    }
    exit;
}

// 3. AJAX: FSHI KLASËN (DELETE)
if ($action === 'delete') {
    $class_id = (int)($_GET['id'] ?? 0);

    if ($class_id <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Identifikues i pasaktë i klasës!']);
        exit;
    }

    try {
        // Për shkak të marrëdhënieve në databazë (Foreign Keys), fshijmë fillimisht regjistrimet dhe notat që lidhen me këtë klasë
        $pdo->prepare("DELETE FROM class_enrollments WHERE class_id = ?")->execute([$class_id]);
        $pdo->prepare("DELETE FROM grades WHERE class_id = ?")->execute([$class_id]);
        
        // Tani fshijmë klasën e profesorit përkatës
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$class_id, $teacher_id]);
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Klasa dhe të gjitha të dhënat e ndërlidhura u fshinë me sukses!']);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Gabim gjatë fshirjes: ' . $e->getMessage()]);
    }
    exit;
}

ob_clean();
echo json_encode(['success' => false, 'message' => 'Veprim i panjohur.']);
exit;