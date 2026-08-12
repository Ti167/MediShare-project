<?php
// NGO — Profile Management
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ngo') {
    header("Location: ../login.php");
    exit;
}

$current_page = 'profile';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$role = 'ngo';
$base_path = '..';
$pending_count = 0;

// Fetch current user data
$user = [];
try {
    $stmt = $pdo->prepare("SELECT full_name, email, phone, contact_number, address, is_verified FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $_SESSION['error'] = "Invalid form submission.";
        header("Location: profile.php");
        exit;
    }

    $new_name = trim($_POST['full_name'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');
    $new_address = trim($_POST['address'] ?? '');

    if (empty($new_name)) {
        $_SESSION['error'] = "Name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE user_id = ?");
            $stmt->execute([$new_name, $new_phone, $new_address, $user_id]);
            $_SESSION['full_name'] = $new_name;
            $user_name = $new_name;
            $user['full_name'] = $new_name;
            $user['phone'] = $new_phone;
            $user['address'] = $new_address;
            $_SESSION['success'] = "Profile updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update profile.";
        }
    }
    header("Location: profile.php");
    exit;
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Medi-Share</title>
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

            <h1 class="text-2xl font-bold text-gray-800 mb-6"><i class="fa-solid fa-user-gear text-blue-600 mr-2"></i>My Profile</h1>
            
            <!-- Verification Status Card -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6 max-w-2xl flex items-center space-x-3">
                <span class="text-sm font-medium text-gray-700">Verification Status:</span>
                <?php echo render_status_badge((int)($user['is_verified'] ?? 0) ? 'verified' : 'unverified'); ?>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
                <form method="POST" action="">
                    <?php csrf_field(); ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Email</label>
                        <input type="email" disabled value="<?php echo sanitize_output($user['email'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                        <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Organization Name</label>
                        <input type="text" name="full_name" required value="<?php echo sanitize_output($user['full_name'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo sanitize_output($user['phone'] ?? $user['contact_number'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Enter phone number">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Address</label>
                        <textarea name="address" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Enter your address (shown to donors on approved requests)"><?php echo sanitize_output($user['address'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">This address will be visible to donors when your request is approved</p>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        <i class="fa-solid fa-save mr-2"></i>Update Profile
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="../common/script.js"></script>
</body>
</html>
