<?php
// Admin — Audit Log with pagination
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$current_admin_page = 'audit';

// Pagination
$per_page = 20;
$total = (int)$pdo->query("SELECT COUNT(*) as cnt FROM audit_log")->fetch()['cnt'];
$total_pages = max(1, ceil($total / $per_page));
$current_pg = max(1, min((int)($_GET['page'] ?? 1), $total_pages));
$offset = ($current_pg - 1) * $per_page;

$logs = $pdo->query("SELECT a.id, a.action, a.details, a.created_at, u.full_name, u.role 
                     FROM audit_log a LEFT JOIN users u ON a.user_id = u.user_id 
                     ORDER BY a.created_at DESC LIMIT $per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Medi-Share Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'admin_sidebar.php'; ?>

        <main class="flex-1 min-h-screen p-4 md:p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fa-solid fa-clipboard-check text-blue-600 mr-2"></i>Audit Log (<?php echo $total; ?> entries)
            </h1>

            <?php if (count($logs) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $action_icons = [
                            'medicine_listed' => 'fa-plus-circle text-emerald-500',
                            'request_submitted' => 'fa-paper-plane text-blue-500',
                            'request_approved' => 'fa-check-circle text-emerald-500',
                            'request_rejected' => 'fa-times-circle text-red-500',
                            'ngo_verified' => 'fa-shield-check text-emerald-500',
                            'ngo_revoked' => 'fa-ban text-amber-500',
                            'medicine_deleted' => 'fa-trash text-red-500',
                            'user_banned' => 'fa-user-slash text-red-500',
                            'expired_cleanup' => 'fa-broom text-amber-500',
                        ];
                        $icon = $action_icons[$log['action']] ?? 'fa-circle-info text-gray-500';
                        ?>
                        <div class="bg-white rounded-xl shadow-sm p-4 flex items-start space-x-4">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0 mt-1">
                                <i class="fa-solid <?php echo $icon; ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                    <div>
                                        <span class="font-semibold text-gray-800"><?php echo sanitize_output($log['full_name'] ?? 'System'); ?></span>
                                        <?php if ($log['role']): ?>
                                            <?php echo render_status_badge($log['role']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs text-gray-500"><i class="fa-regular fa-clock mr-1"></i><?php echo time_ago($log['created_at']); ?></span>
                                </div>
                                <p class="text-sm font-medium text-gray-700 mt-1"><?php echo render_status_badge($log['action']); ?></p>
                                <p class="text-sm text-gray-600 mt-1"><?php echo sanitize_output($log['details']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php render_pagination($current_pg, $total_pages, 'admin_audit.php'); ?>

            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                    <i class="fa-solid fa-clipboard-check text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No audit log entries yet.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
    const mobileSidebar = document.getElementById('mobileSidebar'), overlay = document.getElementById('overlay');
    document.getElementById('menuToggle')?.addEventListener('click', () => { mobileSidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); });
    document.getElementById('closeSidebar')?.addEventListener('click', () => { mobileSidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
    overlay?.addEventListener('click', () => { mobileSidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
    </script>
</body>
</html>
