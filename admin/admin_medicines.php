<?php
// Admin — Medicines with expiry filter, color-coding, pagination, CSRF, audit
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$current_admin_page = 'medicines';
$expiry_filter = $_GET['expiry_filter'] ?? '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $_SESSION['error'] = "Invalid form submission.";
        header("Location: admin_medicines.php");
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'cleanup') {
        try {
            $stmt = $pdo->prepare("DELETE FROM medicines WHERE expiry_date < CURDATE()");
            $stmt->execute();
            $count = $stmt->rowCount();
            audit_log($pdo, $_SESSION['user_id'], 'expired_cleanup', "Admin cleaned up {$count} expired medicine(s).");
            $_SESSION['success'] = "Expired medicines deleted! ({$count} removed)";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_medicine') {
        $medicine_id = (int)($_POST['medicine_id'] ?? 0);
        if ($medicine_id > 0) {
            try {
                $info = $pdo->prepare("SELECT medicine_name FROM medicines WHERE medicine_id = ?");
                $info->execute([$medicine_id]);
                $med = $info->fetch();
                $pdo->prepare("DELETE FROM medicines WHERE medicine_id = ?")->execute([$medicine_id]);
                audit_log($pdo, $_SESSION['user_id'], 'medicine_deleted', "Admin deleted medicine: '{$med['medicine_name']}' (ID: {$medicine_id})");
                $_SESSION['success'] = "Medicine deleted!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        }
    }
    header("Location: admin_medicines.php");
    exit;
}

// Build query with optional expiry filter
$where = "1=1";
if ($expiry_filter === '30days') {
    $where = "m.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

// Pagination
$per_page = 20;
$total = (int)$pdo->query("SELECT COUNT(*) as cnt FROM medicines m WHERE $where")->fetch()['cnt'];
$total_pages = max(1, ceil($total / $per_page));
$current_pg = max(1, min((int)($_GET['page'] ?? 1), $total_pages));
$offset = ($current_pg - 1) * $per_page;

$medicines = $pdo->query("SELECT m.medicine_id, m.medicine_name, m.expiry_date, m.quantity_strips, m.status, m.listed_at, m.image_path, m.category, u.full_name 
    FROM medicines m JOIN users u ON m.donor_id = u.user_id WHERE $where ORDER BY m.listed_at DESC LIMIT $per_page OFFSET $offset")->fetchAll();

$pagination_url = 'admin_medicines.php';
if ($expiry_filter) $pagination_url .= '?expiry_filter=' . urlencode($expiry_filter);

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines - Medi-Share Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'admin_sidebar.php'; ?>

        <main class="flex-1 min-h-screen p-4 md:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-gray-800">All Medicines (<?php echo $total; ?>)</h1>
                <div class="flex flex-wrap gap-2">
                    <a href="admin_medicines.php" class="px-3 py-2 <?php echo empty($expiry_filter) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-lg text-sm font-medium hover:opacity-80">All</a>
                    <a href="admin_medicines.php?expiry_filter=30days" class="px-3 py-2 <?php echo $expiry_filter === '30days' ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-700'; ?> rounded-lg text-sm font-medium hover:opacity-80"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Expiring in 30 days</a>
                    <form method="POST" class="inline"><?php csrf_field(); ?><input type="hidden" name="action" value="cleanup"><button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700" onclick="return confirm('Delete all expired?')"><i class="fa-solid fa-broom mr-1"></i>Clean Expired</button></form>
                </div>
            </div>
            <?php if ($success): ?><div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($error); ?></div><?php endif; ?>

            <!-- Desktop Table -->
            <div class="hidden md:block bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Img</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($medicines) > 0): ?>
                            <?php foreach ($medicines as $m): ?>
                                <?php $expiry_info = get_expiry_coding($m['expiry_date']); ?>
                                <tr class="hover:bg-gray-50 <?php echo $expiry_info['row_class']; ?>">
                                    <td class="px-4 py-4"><?php echo render_medicine_thumbnail($m['image_path'] ?? null, $m['medicine_name'], '..'); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo (int)$m['medicine_id']; ?></td>
                                    <td class="px-4 py-4 text-gray-800 font-medium"><?php echo sanitize_output($m['medicine_name']); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo sanitize_output($m['full_name']); ?></td>
                                    <td class="px-4 py-4 text-gray-600 text-sm"><?php echo sanitize_output($m['category'] ?? 'Other'); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo (int)$m['quantity_strips']; ?></td>
                                    <td class="px-4 py-4 <?php echo $expiry_info['class']; ?>">
                                        <?php echo sanitize_output($m['expiry_date']); ?>
                                        <?php if ($expiry_info['label']): ?><span class="block text-xs <?php echo $expiry_info['badge']; ?> px-1 py-0.5 rounded mt-1 inline-block"><?php echo $expiry_info['label']; ?></span><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4"><?php echo render_status_badge($m['status']); ?></td>
                                    <td class="px-4 py-4">
                                        <form method="POST" class="inline"><?php csrf_field(); ?><input type="hidden" name="action" value="delete_medicine"><input type="hidden" name="medicine_id" value="<?php echo (int)$m['medicine_id']; ?>"><button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash"></i></button></form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?><tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">No medicines.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden space-y-4">
                <?php if (count($medicines) > 0): ?>
                    <?php foreach ($medicines as $m): ?>
                        <?php $expiry_info = get_expiry_coding($m['expiry_date']); ?>
                        <div class="bg-white rounded-xl shadow-sm p-4 <?php echo $expiry_info['row_class']; ?>">
                            <div class="flex justify-between items-start">
                                <div class="flex items-start space-x-3">
                                    <?php echo render_medicine_thumbnail($m['image_path'] ?? null, $m['medicine_name'], '..'); ?>
                                    <div>
                                        <p class="text-xs text-gray-500">ID: <?php echo (int)$m['medicine_id']; ?></p>
                                        <p class="text-lg font-bold text-gray-800"><?php echo sanitize_output($m['medicine_name']); ?></p>
                                        <p class="text-sm text-gray-600"><i class="fa-solid fa-user mr-1"></i><?php echo sanitize_output($m['full_name']); ?></p>
                                        <p class="text-xs text-gray-500">Qty: <?php echo (int)$m['quantity_strips']; ?> | <?php echo sanitize_output($m['category'] ?? 'Other'); ?></p>
                                        <p class="text-xs <?php echo $expiry_info['class']; ?>">Exp: <?php echo sanitize_output($m['expiry_date']); ?> <?php echo $expiry_info['label']; ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <?php echo render_status_badge($m['status']); ?>
                                    <form method="POST" class="mt-2"><?php csrf_field(); ?><input type="hidden" name="action" value="delete_medicine"><input type="hidden" name="medicine_id" value="<?php echo (int)$m['medicine_id']; ?>"><button type="submit" class="text-red-600 text-sm" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash mr-1"></i>Delete</button></form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm p-8 text-center"><p class="text-gray-500">No medicines.</p></div>
                <?php endif; ?>
            </div>

            <?php render_pagination($current_pg, $total_pages, $pagination_url); ?>
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
