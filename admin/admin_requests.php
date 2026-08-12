<?php
// Admin — All Requests with pagination
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$current_admin_page = 'requests';

// Pagination
$per_page = 20;
$total = (int)$pdo->query("SELECT COUNT(*) as cnt FROM requests")->fetch()['cnt'];
$total_pages = max(1, ceil($total / $per_page));
$current_pg = max(1, min((int)($_GET['page'] ?? 1), $total_pages));
$offset = ($current_pg - 1) * $per_page;

$requests = $pdo->query("SELECT r.request_id, r.request_status, r.request_date, m.medicine_name, m.quantity_strips, m.image_path,
                         u.full_name as ngo_name, d.full_name as donor_name
                         FROM requests r JOIN medicines m ON r.medicine_id = m.medicine_id JOIN users u ON r.ngo_id = u.user_id JOIN users d ON m.donor_id = d.user_id 
                         ORDER BY r.request_date DESC LIMIT $per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests - Medi-Share Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'admin_sidebar.php'; ?>

        <main class="flex-1 min-h-screen p-4 md:p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">All Requests (<?php echo $total; ?>)</h1>
            
            <!-- Mobile Card View -->
            <div class="md:hidden space-y-4">
                <?php if (count($requests) > 0): ?>
                    <?php foreach ($requests as $r): ?>
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center space-x-3">
                                    <?php echo render_medicine_thumbnail($r['image_path'] ?? null, $r['medicine_name'], '.'); ?>
                                    <div>
                                        <span class="text-xs text-gray-500">#<?php echo (int)$r['request_id']; ?></span>
                                        <h3 class="font-semibold text-gray-800"><?php echo sanitize_output($r['medicine_name']); ?></h3>
                                    </div>
                                </div>
                                <?php echo render_status_badge($r['request_status']); ?>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-gray-500">NGO:</span><span class="text-gray-700"><?php echo sanitize_output($r['ngo_name']); ?></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Donor:</span><span class="text-gray-700"><?php echo sanitize_output($r['donor_name']); ?></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Qty:</span><span class="text-gray-700"><?php echo (int)$r['quantity_strips']; ?></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Date:</span><span class="text-gray-700"><?php echo time_ago($r['request_date']); ?></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No requests.</div>
                <?php endif; ?>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NGO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($requests) > 0): ?>
                            <?php foreach ($requests as $r): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-gray-600"><?php echo (int)$r['request_id']; ?></td>
                                    <td class="px-4 py-4 text-gray-800 font-medium">
                                        <div class="flex items-center space-x-3">
                                    <?php echo render_medicine_thumbnail($r['image_path'] ?? null, $r['medicine_name'], '..'); ?>
                                            <span><?php echo sanitize_output($r['medicine_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo sanitize_output($r['ngo_name']); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo sanitize_output($r['donor_name']); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo (int)$r['quantity_strips']; ?></td>
                                    <td class="px-4 py-4 text-gray-500 text-sm"><?php echo time_ago($r['request_date']); ?></td>
                                    <td class="px-4 py-4"><?php echo render_status_badge($r['request_status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?><tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No requests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php render_pagination($current_pg, $total_pages, 'admin_requests.php'); ?>
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
