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
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Qasje e paautorizuar! Ju nuk jeni profesor.']);
    exit;
}
$user_id = $_SESSION['user_id'];
$t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$t_stmt->execute([$user_id]);
$teacher = $t_stmt->fetch();
$teacher_id = $teacher ? $teacher['id'] : 0;

$action = $_GET['action'] ?? '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);
    $subject = htmlspecialchars(strip_tags(trim($_POST['subject'] ?? '')));
    $score = (int)($_POST['score'] ?? 0);

    if ($student_id <= 0 || $class_id <= 0 || empty($subject) || $score < 0 || $score > 100) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Ju lutem plotësoni të gjitha fushat saktë!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO grades (student_id, class_id, subject, score) VALUES (?, ?, ?, ?)");
        $stmt->execute([$student_id, $class_id, $subject, $score]);
        $new_id = $pdo->lastInsertId();

        $info = $pdo->prepare("
            SELECT g.id, g.subject, g.score, u.name as student_name, c.class_name 
            FROM grades g 
            JOIN users u ON g.student_id = u.id 
            JOIN classes c ON g.class_id = c.id 
            WHERE g.id = ?
        ");
        $info->execute([$new_id]);
        $data = $info->fetch();
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Nota u shtua me sukses përmes AJAX!', 'data' => $data]);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Gabim gjatë insertimit: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade_id = (int)($_POST['grade_id'] ?? 0);
    $new_score = (int)($_POST['score'] ?? 0);

    if ($grade_id <= 0 || $new_score < 0 || $new_score > 100) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Vlerësim i pasaktë! Nota duhet të jetë midis 0 dhe 100.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE grades SET score = ? WHERE id = ?");
        $stmt->execute([$new_score, $grade_id]);
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Nota u ndryshua me sukses në databazë!']);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Gabim gjatë përditësimit: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $grade_id = (int)($_GET['id'] ?? 0);

    if ($grade_id <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Identifikues i pasaktë i notës!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
        $stmt->execute([$grade_id]);

        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Nota u fshi me sukses nga sistemi!']);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Gabim gjatë fshirjes: ' . $e->getMessage()]);
    }
    exit;
}

ob_clean();
echo json_encode(['success' => false, 'message' => 'Veprim i panjohur.']);
exit;