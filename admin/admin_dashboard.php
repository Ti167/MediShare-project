<?php
// Admin Dashboard with Chart.js charts and metric cards
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION['full_name'] ?? 'Admin';
$current_admin_page = 'dashboard';

// Handle cleanup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup') {
    if (verify_csrf_token()) {
        try {
            $stmt = $pdo->prepare("DELETE FROM medicines WHERE expiry_date < CURDATE()");
            $stmt->execute();
            $count = $stmt->rowCount();
            audit_log($pdo, $_SESSION['user_id'], 'expired_cleanup', "Admin cleaned up {$count} expired medicine(s).");
            $_SESSION['success'] = "Cleanup completed! Removed {$count} expired medicine(s).";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Cleanup failed.";
        }
    }
    header("Location: admin_dashboard.php");
    exit;
}

// Stats
$stats = ['total_users' => 0, 'total_donors' => 0, 'total_ngos' => 0, 'available_medicines' => 0, 'pending_requests' => 0, 'completed_donations' => 0, 'total_medicines' => 0];
try {
    $stats['total_users'] = (int)$pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'];
    $stats['total_donors'] = (int)$pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'donor'")->fetch()['cnt'];
    $stats['total_ngos'] = (int)$pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'ngo'")->fetch()['cnt'];
    $stats['available_medicines'] = (int)$pdo->query("SELECT COUNT(*) as cnt FROM medicines WHERE status = 'available'")->fetch()['cnt'];
    $stats['pending_requests'] = (int)$pdo->query("SELECT COUNT(*) as cnt FROM medicines WHERE status = 'requested'")->fetch()['cnt'];
    $stats['completed_donations'] = (int)$pdo->query("SELECT COUNT(*) as cnt FROM medicines WHERE status = 'donated'")->fetch()['cnt'];
    $stats['total_medicines'] = (int)$pdo->query("SELECT COUNT(*) as cnt FROM medicines")->fetch()['cnt'];
} catch (PDOException $e) {}

// Monthly donations for bar chart (current year)
$monthly_donations = array_fill(0, 12, 0);
try {
    $stmt = $pdo->query("SELECT MONTH(r.request_date) as month, COUNT(*) as cnt 
                         FROM requests r 
                         WHERE r.request_status = 'approved' AND YEAR(r.request_date) = YEAR(CURDATE()) 
                         GROUP BY MONTH(r.request_date)");
    while ($row = $stmt->fetch()) {
        $monthly_donations[(int)$row['month'] - 1] = (int)$row['cnt'];
    }
} catch (PDOException $e) {}

// Recent medicines
$recent = [];
try {
    $recent = $pdo->query("SELECT m.medicine_id, m.medicine_name, m.expiry_date, m.status, m.listed_at, m.image_path, m.category, u.full_name FROM medicines m JOIN users u ON m.donor_id = u.user_id ORDER BY m.listed_at DESC LIMIT 10")->fetchAll();
} catch (PDOException $e) {}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'admin_sidebar.php'; ?>

        <main class="flex-1 min-h-screen p-4 md:p-8">
            <?php if ($success): ?><div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($error); ?></div><?php endif; ?>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div><h1 class="text-2xl font-bold text-gray-800">Platform Overview</h1><p class="text-gray-600">Welcome, <?php echo sanitize_output($user_name); ?></p></div>
                <form method="POST" class="mt-4 md:mt-0"><?php csrf_field(); ?><input type="hidden" name="action" value="cleanup"><button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="return confirm('Delete expired medicines?')"><i class="fa-solid fa-broom mr-2"></i>Clean Expired</button></form>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-600">
                    <p class="text-sm text-gray-500">Total Donors</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_donors']; ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
                    <p class="text-sm text-gray-500">Total NGOs</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_ngos']; ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-emerald-500">
                    <p class="text-sm text-gray-500">Total Medicines</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_medicines']; ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-amber-500">
                    <p class="text-sm text-gray-500">Pending Requests</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $stats['pending_requests']; ?></p>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fa-solid fa-chart-pie text-blue-600 mr-2"></i>Medicine Status Distribution</h3>
                    <div class="flex justify-center" style="height: 280px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fa-solid fa-chart-bar text-emerald-600 mr-2"></i>Monthly Donations (<?php echo date('Y'); ?>)</h3>
                    <div style="height: 280px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Medicines Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200"><h2 class="text-xl font-bold text-gray-800"><i class="fa-solid fa-clock-rotate-left text-blue-600 mr-2"></i>Recent Medicines</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donor</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th></tr></thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (count($recent) > 0): ?>
                                <?php foreach ($recent as $m): ?>
                                    <?php $expiry_info = get_expiry_coding($m['expiry_date']); ?>
                                    <tr class="hover:bg-gray-50 <?php echo $expiry_info['row_class']; ?>">
                                        <td class="px-4 py-4"><?php echo render_medicine_thumbnail($m['image_path'] ?? null, $m['medicine_name'], '..'); ?></td>
                                        <td class="px-4 py-4 text-gray-800 font-medium"><?php echo sanitize_output($m['medicine_name']); ?></td>
                                        <td class="px-4 py-4 text-gray-600"><?php echo sanitize_output($m['full_name']); ?></td>
                                        <td class="px-4 py-4 text-gray-600 text-sm"><?php echo sanitize_output($m['category'] ?? 'Other'); ?></td>
                                        <td class="px-4 py-4 <?php echo $expiry_info['class']; ?>">
                                            <?php echo sanitize_output($m['expiry_date']); ?>
                                            <?php if ($expiry_info['label']): ?><span class="block text-xs <?php echo $expiry_info['badge']; ?> px-1 py-0.5 rounded mt-1 inline-block"><?php echo $expiry_info['label']; ?></span><?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4"><?php echo render_status_badge($m['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?><tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No medicines yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Donut Chart — Medicine Status Distribution
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Available', 'Requested', 'Donated'],
            datasets: [{
                data: [<?php echo $stats['available_medicines']; ?>, <?php echo $stats['pending_requests']; ?>, <?php echo $stats['completed_donations']; ?>],
                backgroundColor: ['#10b981', '#3b82f6', '#9ca3af'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
            }
        }
    });

    // Bar Chart — Monthly Donations
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Completed Donations',
                data: <?php echo json_encode($monthly_donations); ?>,
                backgroundColor: '#10b981',
                borderRadius: 6,
                barThickness: 'flex'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } }
            }
        }
    });

    // Mobile sidebar toggle
    const mobileSidebar = document.getElementById('mobileSidebar'), overlay = document.getElementById('overlay');
    document.getElementById('menuToggle')?.addEventListener('click', () => { mobileSidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); });
    document.getElementById('closeSidebar')?.addEventListener('click', () => { mobileSidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
    overlay?.addEventListener('click', () => { mobileSidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
    </script>
</body>
</html>
