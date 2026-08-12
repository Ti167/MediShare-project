<?php
// Admin — Platform Announcements
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php"); exit;
}

$current_admin_page = 'announcements';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $_SESSION['error'] = "Invalid form submission.";
        header("Location: admin_announcements.php"); exit;
    }
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if (!empty($title) && !empty($message)) {
            try {
                // Deactivate all existing
                $pdo->query("UPDATE announcements SET is_active = 0");
                $pdo->prepare("INSERT INTO announcements (title, message, created_by) VALUES (?,?,?)")->execute([$title, $message, $_SESSION['user_id']]);
                audit_log($pdo, $_SESSION['user_id'], 'announcement_created', "Admin created announcement: \"{$title}\"");
                $_SESSION['success'] = "Announcement published!";
            } catch (PDOException $e) { $_SESSION['error'] = "Error."; }
        }
    } elseif ($action === 'deactivate') {
        $id = (int)($_POST['ann_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE id=?")->execute([$id]);
            $_SESSION['success'] = "Announcement deactivated.";
        }
    } elseif ($action === 'activate') {
        $id = (int)($_POST['ann_id'] ?? 0);
        if ($id > 0) {
            $pdo->query("UPDATE announcements SET is_active = 0");
            $pdo->prepare("UPDATE announcements SET is_active = 1 WHERE id=?")->execute([$id]);
            $_SESSION['success'] = "Announcement activated.";
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['ann_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([$id]);
            $_SESSION['success'] = "Announcement deleted.";
        }
    }
    header("Location: admin_announcements.php"); exit;
}

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Medi-Share Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'admin_sidebar.php'; ?>
        <main class="flex-1 min-h-screen p-4 md:p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6"><i class="fa-solid fa-bullhorn text-blue-600 mr-2"></i>Platform Announcements</h1>
            <?php if ($success): ?><div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($error); ?></div><?php endif; ?>

            <!-- Create New -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h2 class="text-lg font-bold text-gray-800 mb-4">New Announcement</h2>
                <form method="POST" class="space-y-3">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="create">
                    <input type="text" name="title" required placeholder="Announcement title" class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none">
                    <textarea name="message" required rows="3" placeholder="Message content..." class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none"></textarea>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition"><i class="fa-solid fa-bullhorn mr-2"></i>Publish</button>
                </form>
            </div>

            <!-- Existing -->
            <div class="space-y-3">
                <?php foreach ($announcements as $a): ?>
                    <div class="bg-white rounded-xl shadow-sm p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-1">
                                <h3 class="font-bold text-gray-800"><?php echo sanitize_output($a['title']); ?></h3>
                                <?php echo render_status_badge($a['is_active'] ? 'available' : 'donated'); ?>
                            </div>
                            <p class="text-sm text-gray-600"><?php echo sanitize_output($a['message']); ?></p>
                            <p class="text-xs text-gray-400 mt-1"><i class="fa-regular fa-clock mr-1"></i><?php echo time_ago($a['created_at']); ?></p>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($a['is_active']): ?>
                                <form method="POST"><?php csrf_field(); ?><input type="hidden" name="action" value="deactivate"><input type="hidden" name="ann_id" value="<?php echo (int)$a['id']; ?>"><button type="submit" class="px-3 py-1 bg-amber-100 text-amber-700 text-xs rounded hover:bg-amber-200">Deactivate</button></form>
                            <?php else: ?>
                                <form method="POST"><?php csrf_field(); ?><input type="hidden" name="action" value="activate"><input type="hidden" name="ann_id" value="<?php echo (int)$a['id']; ?>"><button type="submit" class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs rounded hover:bg-emerald-200">Activate</button></form>
                            <?php endif; ?>
                            <form method="POST"><?php csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="ann_id" value="<?php echo (int)$a['id']; ?>"><button type="submit" class="px-3 py-1 bg-red-100 text-red-700 text-xs rounded hover:bg-red-200" onclick="return confirm('Delete?')">Delete</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($announcements)): ?>
                    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No announcements yet.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
    const mobileSidebar=document.getElementById('mobileSidebar'),overlay=document.getElementById('overlay');
    document.getElementById('menuToggle')?.addEventListener('click',()=>{mobileSidebar.classList.toggle('-translate-x-full');overlay.classList.toggle('hidden');});
    document.getElementById('closeSidebar')?.addEventListener('click',()=>{mobileSidebar.classList.add('-translate-x-full');overlay.classList.add('hidden');});
    overlay?.addEventListener('click',()=>{mobileSidebar.classList.add('-translate-x-full');overlay.classList.add('hidden');});
    </script>
</body>
</html>
