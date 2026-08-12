<?php
// Donor — Donation History with thumbnails and status badges
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: ../login.php");
    exit;
}

$current_page = 'history';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$role = 'donor';
$base_path = '..';
$pending_count = 0;

$history = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE donor_id = ? AND status = 'donated' ORDER BY listed_at DESC");
    $stmt->execute([$user_id]);
    $history = $stmt->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../common/header.php'; ?>
    
    <div class="flex">
        <?php include '../common/sidebar.php'; ?>
        
        <main class="flex-1 min-h-screen p-4 md:p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Donation History</h1>
            <?php if (count($history) > 0): ?>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Listed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($history as $item): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4"><?php echo render_medicine_thumbnail($item['image_path'] ?? null, $item['medicine_name']); ?></td>
                                    <td class="px-4 py-4 text-gray-800 font-medium"><?php echo sanitize_output($item['medicine_name']); ?></td>
                                    <td class="px-4 py-4 text-gray-600 text-sm"><?php echo sanitize_output($item['category'] ?? 'Other'); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo (int)$item['quantity_strips']; ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo sanitize_output($item['expiry_date']); ?></td>
                                    <td class="px-4 py-4"><?php echo render_status_badge('donated'); ?></td>
                                    <td class="px-4 py-4 text-gray-500"><?php echo date('Y-m-d', strtotime($item['listed_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                    <i class="fa-solid fa-clock-rotate-left text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No donation history yet.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../common/script.js"></script>
</body>
</html>
