<?php
// NGO Dashboard — Browse medicines with search, category filter, pagination, expiry coding, verification gate
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ngo') {
    header("Location: ../login.php");
    exit;
}

$current_page = 'dashboard';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$role = 'ngo';
$base_path = '..';
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');

// Check NGO verification status
$is_verified = 0;
try {
    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    $is_verified = $row ? (int)$row['is_verified'] : 0;
} catch (PDOException $e) {}

// Stats
$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
try {
    $stmt = $pdo->prepare("SELECT r.request_status, COUNT(*) as count FROM requests r WHERE r.ngo_id = ? GROUP BY r.request_status");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch()) {
        $stats[$row['request_status']] = $row['count'];
    }
} catch (PDOException $e) {}
$pending_count = $stats['pending'];

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Build query with filters
$where_clauses = ["m.status = 'available'", "m.expiry_date > CURDATE()"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(m.medicine_name LIKE ? OR u.address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter) && in_array($category_filter, ['Antibiotic', 'Painkiller', 'Vitamin', 'Supplement', 'Other'])) {
    $where_clauses[] = "m.category = ?";
    $params[] = $category_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Count total for pagination
$count_sql = "SELECT COUNT(*) as total FROM medicines m JOIN users u ON m.donor_id = u.user_id WHERE $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetch()['total'];

$per_page = 10;
$total_pages = max(1, ceil($total_records / $per_page));
$current_pg = max(1, min((int)($_GET['page'] ?? 1), $total_pages));
$offset = ($current_pg - 1) * $per_page;

// Fetch medicines
$sql = "SELECT m.medicine_id, m.medicine_name, m.expiry_date, m.quantity_strips, m.status, m.listed_at, m.category, m.image_path,
               u.full_name AS donor_name, u.address AS donor_address
        FROM medicines m
        JOIN users u ON m.donor_id = u.user_id
        WHERE $where_sql
        ORDER BY m.listed_at DESC
        LIMIT $per_page OFFSET $offset";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $medicines = $stmt->fetchAll();
} catch (PDOException $e) {
    $medicines = [];
}

// Build pagination base URL
$pagination_url = 'index.php?';
$url_params = [];
if (!empty($search)) $url_params[] = 'search=' . urlencode($search);
if (!empty($category_filter)) $url_params[] = 'category=' . urlencode($category_filter);
$pagination_url .= implode('&', $url_params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGO Dashboard - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../common/header.php'; ?>
    
    <div class="flex">
        <?php include '../common/sidebar.php'; ?>
        
        <main class="flex-1 min-h-screen p-4 md:p-8">
            <?php if (!$is_verified): ?>
                <div class="bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 text-xl mr-3"></i>
                    <div>
                        <p class="font-semibold">Account Pending Verification</p>
                        <p class="text-sm">Your NGO account is pending admin verification. You can browse medicines but cannot submit requests until verified. You will be notified once approved.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($error); ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center"><i class="fa-solid fa-clock text-yellow-500 text-xl"></i></div>
                        <div class="ml-4"><p class="text-sm text-gray-500">Pending</p><p class="text-2xl font-bold text-gray-800"><?php echo $stats['pending']; ?></p></div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-emerald-500">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center"><i class="fa-solid fa-check-circle text-emerald-500 text-xl"></i></div>
                        <div class="ml-4"><p class="text-sm text-gray-500">Approved</p><p class="text-2xl font-bold text-gray-800"><?php echo $stats['approved']; ?></p></div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center"><i class="fa-solid fa-times-circle text-red-500 text-xl"></i></div>
                        <div class="ml-4"><p class="text-sm text-gray-500">Rejected</p><p class="text-2xl font-bold text-gray-800"><?php echo $stats['rejected']; ?></p></div>
                    </div>
                </div>
            </div>

            <!-- Search & Category Filter -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1 relative">
                        <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="<?php echo sanitize_output($search); ?>" 
                               placeholder="Search medicines by name or location..." 
                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">All Categories</option>
                            <option value="Antibiotic" <?php echo $category_filter === 'Antibiotic' ? 'selected' : ''; ?>>Antibiotic</option>
                            <option value="Painkiller" <?php echo $category_filter === 'Painkiller' ? 'selected' : ''; ?>>Painkiller</option>
                            <option value="Vitamin" <?php echo $category_filter === 'Vitamin' ? 'selected' : ''; ?>>Vitamin</option>
                            <option value="Supplement" <?php echo $category_filter === 'Supplement' ? 'selected' : ''; ?>>Supplement</option>
                            <option value="Other" <?php echo $category_filter === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        <i class="fa-solid fa-search mr-2"></i>Search
                    </button>
                    <?php if (!empty($search) || !empty($category_filter)): ?>
                        <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition text-center">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fa-solid fa-box-open text-emerald-500 mr-2"></i>Available Medicines (<?php echo $total_records; ?>)
            </h2>
            
            <?php if (count($medicines) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($medicines as $medicine): ?>
                        <?php $expiry_info = get_expiry_coding($medicine['expiry_date']); ?>
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition <?php echo $expiry_info['row_class']; ?>">
                            <div class="p-5">
                                <div class="flex items-start space-x-3 mb-3">
                                    <div class="flex-shrink-0">
                                        <?php echo render_medicine_thumbnail($medicine['image_path'] ?? null, $medicine['medicine_name']); ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-gray-800 truncate"><?php echo sanitize_output($medicine['medicine_name']); ?></h3>
                                        <span class="text-xs text-gray-500"><?php echo sanitize_output($medicine['category'] ?? 'Other'); ?></span>
                                    </div>
                                    <?php echo render_status_badge('available'); ?>
                                </div>
                                <div class="space-y-2 text-sm text-gray-600 mb-4">
                                    <p><i class="fa-solid fa-user text-blue-500 w-5"></i> <?php echo sanitize_output($medicine['donor_name']); ?></p>
                                    <p><i class="fa-solid fa-location-dot text-red-500 w-5"></i> <?php echo sanitize_output($medicine['donor_address']); ?></p>
                                    <p><i class="fa-solid fa-calendar text-gray-500 w-5"></i> Expires: 
                                        <span class="<?php echo $expiry_info['class']; ?>"><?php echo sanitize_output($medicine['expiry_date']); ?></span>
                                        <?php if ($expiry_info['label']): ?>
                                            <span class="<?php echo $expiry_info['badge']; ?> text-xs px-1 py-0.5 rounded ml-1"><?php echo $expiry_info['label']; ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <p><i class="fa-solid fa-pills text-gray-500 w-5"></i> <?php echo (int)$medicine['quantity_strips']; ?> strips</p>
                                </div>
                                <?php if ($is_verified): ?>
                                    <form method="POST" action="request_process.php">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="medicine_id" value="<?php echo (int)$medicine['medicine_id']; ?>">
                                        <textarea name="request_note" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2 outline-none" placeholder="Why do you need this? (optional)"></textarea>
                                        <button type="submit" class="w-full bg-emerald-500 text-white py-2 rounded-lg font-semibold hover:bg-emerald-600 transition">
                                            <i class="fa-solid fa-hand-holding-heart mr-2"></i>Request
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button disabled class="w-full bg-gray-300 text-gray-500 py-2 rounded-lg font-semibold cursor-not-allowed" title="Account verification required">
                                        <i class="fa-solid fa-lock mr-2"></i>Verification Required
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php render_pagination($current_pg, $total_pages, $pagination_url); ?>

            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                    <i class="fa-solid fa-search text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500"><?php echo (!empty($search) || !empty($category_filter)) ? "No medicines found matching your filters." : "No medicines available."; ?></p>
                </div>
            <?php endif; ?>

            <!-- Activity Feed -->
            <div class="mt-8">
                <?php include '../common/activity_feed.php'; render_activity_feed($pdo); ?>
            </div>
        </main>
    </div>

    <script src="../common/script.js"></script>
</body>
</html>
