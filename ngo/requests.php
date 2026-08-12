<?php
// NGO — My Requests with donor contact info on approved requests
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ngo') {
    header("Location: ../login.php");
    exit;
}

$current_page = 'requests';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$role = 'ngo';
$base_path = '..';

$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
try {
    $stmt = $pdo->prepare("SELECT r.request_status, COUNT(*) as count FROM requests r WHERE r.ngo_id = ? GROUP BY r.request_status");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch()) {
        $stats[$row['request_status']] = $row['count'];
    }
} catch (PDOException $e) {}
$pending_count = $stats['pending'];

$requests = [];
try {
    $stmt = $pdo->prepare("SELECT r.request_id, r.request_status, r.request_date, r.rejection_reason, r.request_note,
                           m.medicine_name, m.quantity_strips, m.expiry_date, m.image_path, m.category,
                           u.full_name as donor_name, u.address as donor_address, u.phone as donor_phone, u.contact_number as donor_contact
                           FROM requests r JOIN medicines m ON r.medicine_id = m.medicine_id JOIN users u ON m.donor_id = u.user_id
                           WHERE r.ngo_id = ? ORDER BY r.request_date DESC");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();
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
    <title>My Requests - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../common/header.php'; ?>
    
    <div class="flex">
        <?php include '../common/sidebar.php'; ?>
        
        <main class="flex-1 min-h-screen p-4 md:p-8">
            <?php if ($success): ?>
                <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($error); ?></div>
            <?php endif; ?>
            
            <h1 class="text-2xl font-bold text-gray-800 mb-6">My Requests</h1>
            <?php if (count($requests) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($requests as $req): ?>
                        <?php $expiry_info = get_expiry_coding($req['expiry_date']); ?>
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex flex-col sm:flex-row justify-between items-start">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <?php echo render_medicine_thumbnail($req['image_path'] ?? null, $req['medicine_name']); ?>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800"><?php echo sanitize_output($req['medicine_name']); ?></h3>
                                        <p class="text-xs text-gray-500"><?php echo sanitize_output($req['category'] ?? 'Other'); ?></p>
                                        <p class="text-sm text-gray-600">Qty: <?php echo (int)$req['quantity_strips']; ?> | Exp: 
                                            <span class="<?php echo $expiry_info['class']; ?>"><?php echo sanitize_output($req['expiry_date']); ?></span>
                                        </p>
                                        <p class="text-sm text-gray-600 mt-2"><i class="fa-solid fa-user text-blue-500 mr-1"></i><?php echo sanitize_output($req['donor_name']); ?></p>
                                        
                                        <?php if ($req['request_status'] === 'approved'): ?>
                                            <div class="mt-3 p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                                                <p class="text-sm font-semibold text-emerald-800 mb-1"><i class="fa-solid fa-address-card mr-1"></i>Donor Contact</p>
                                                <p class="text-sm text-emerald-700"><i class="fa-solid fa-phone text-emerald-500 mr-1 w-4"></i><?php echo sanitize_output($req['donor_phone'] ?: $req['donor_contact']); ?></p>
                                                <p class="text-sm text-emerald-700"><i class="fa-solid fa-location-dot text-emerald-500 mr-1 w-4"></i><?php echo sanitize_output($req['donor_address']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($req['request_note'])): ?>
                                            <div class="mt-2 p-2 bg-blue-50/30 border border-blue-200 rounded-lg">
                                                <p class="text-xs font-semibold text-blue-700"><i class="fa-solid fa-message mr-1"></i>Your Note:</p>
                                                <p class="text-sm text-blue-800"><?php echo sanitize_output($req['request_note']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($req['request_status'] === 'rejected' && !empty($req['rejection_reason'])): ?>
                                            <div class="mt-2 p-2 bg-red-50/30 border border-red-200 rounded-lg">
                                                <p class="text-xs font-semibold text-red-700"><i class="fa-solid fa-circle-info mr-1"></i>Rejection Reason:</p>
                                                <p class="text-sm text-red-800"><?php echo sanitize_output($req['rejection_reason']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <p class="text-xs text-gray-400 mt-2"><i class="fa-regular fa-clock mr-1"></i><?php echo time_ago($req['request_date']); ?></p>
                                    </div>
                                </div>
                                <div class="mt-4 sm:mt-0">
                                    <?php echo render_status_badge($req['request_status']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                    <i class="fa-solid fa-clipboard-list text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No requests yet.</p>
                    <a href="index.php" class="inline-block mt-4 text-blue-600 hover:underline">Browse Medicines</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../common/script.js"></script>
</body>
</html>
