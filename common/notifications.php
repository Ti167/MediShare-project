<?php
// Notifications Page — Shared by Donor & NGO
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['donor', 'ngo'])) {
    header("Location: ../login.php");
    exit;
}

$current_page = 'notifications';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$role = $_SESSION['role'];
$base_path = '..';
$pending_count = 0;

// Mark all as read on page visit
try {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);
} catch (PDOException $e) {}

// Fetch all notifications, newest first
$notifications = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../common/header.php'; ?>
    
    <div class="flex">
        <?php include '../common/sidebar.php'; ?>
        
        <main class="flex-1 min-h-screen p-4 md:p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fa-solid fa-bell text-blue-600 mr-2"></i>Notifications
            </h1>
            
            <?php if (count($notifications) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($notifications as $n): ?>
                        <div class="bg-white rounded-xl shadow-sm p-4 flex items-start space-x-4 border-l-4 <?php echo $n['is_read'] ? 'border-gray-200' : 'border-blue-500'; ?>">
                            <div class="w-10 h-10 rounded-full <?php echo $n['is_read'] ? 'bg-gray-100' : 'bg-blue-100'; ?> flex items-center justify-center flex-shrink-0 mt-1">
                                <i class="fa-solid fa-bell <?php echo $n['is_read'] ? 'text-gray-400' : 'text-blue-600'; ?>"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-800 <?php echo $n['is_read'] ? '' : 'font-semibold'; ?>"><?php echo sanitize_output($n['message']); ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fa-regular fa-clock mr-1"></i><?php echo time_ago($n['created_at']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                    <i class="fa-solid fa-bell-slash text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No notifications yet.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../common/script.js"></script>
</body>
</html>
