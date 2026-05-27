<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document'])) {
    try {
        $target_dir = __DIR__ . "/../assets/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = basename($_FILES["document"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Basic check limitation filter checking
        if ($_FILES["document"]["size"] > 5000000) {
            throw new Exception("Skedari është shumë i madh! (Maksimumi 5MB)");
        }

        if (move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
            $message = "<div class='text-emerald-400 font-semibold text-sm'>Dokumenti u ngarkua me sukses!</div>";
        } else {
            throw new Exception("Ndodhi një gabim gjatë ngarkimit.");
        }
    } catch (Exception $e) { // handling of system exceptions
        $message = "<div class='text-red-400 font-semibold text-sm'>Gabim: " . $e->getMessage() . "</div>";
    }
}
?>
<main class="flex-1 p-10 space-y-6">
    <h1 class="text-3xl font-bold">Upload Management Documents</h1>
    <div class="bg-zinc-950 border border-zinc-800 p-6 rounded-2xl max-w-lg shadow-md">
        <?php echo $message; ?>
        <form action="upload.php" method="POST" enctype="multipart/form-data" class="space-y-4 mt-2">
            <div>
                <label class="block text-xs uppercase tracking-wider text-zinc-500 font-semibold mb-2">Zgjidhni skedarin (PDF, DOCX)</label>
                <input type="file" name="document" required class="w-full text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-zinc-900 file:text-white hover:file:bg-zinc-800 file:cursor-pointer">
            </div>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 font-semibold text-xs px-6 py-3 rounded-xl transition">Ngarko Dokumentin</button>
        </form>
    </div>
</main>
</body>
</html>
