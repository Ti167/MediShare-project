<?php
// NGO Wishlist — Post medicine needs, donors can fulfil
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$role = $_SESSION['role'];
$base_path = '..';
$current_page = 'wishlist';
$pending_count = 0;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $_SESSION['error'] = "Invalid form submission.";
        header("Location: wishlist.php"); exit;
    }
    $action = $_POST['action'] ?? '';
    
    // NGO: Add wishlist item
    if ($action === 'add_wish' && $role === 'ngo') {
        $name = trim($_POST['medicine_name'] ?? '');
        $cat = $_POST['category'] ?? 'Other';
        $qty = max(1, (int)($_POST['quantity_needed'] ?? 1));
        $desc = trim($_POST['description'] ?? '');
        if (!empty($name)) {
            try {
                $pdo->prepare("INSERT INTO wishlists (ngo_id, medicine_name, category, quantity_needed, description) VALUES (?,?,?,?,?)")
                    ->execute([$user_id, $name, $cat, $qty, $desc ?: null]);
                add_activity($pdo, 'wishlist', "An NGO needs \"{$name}\"");
                audit_log($pdo, $user_id, 'wishlist_added', "NGO '{$user_name}' posted wishlist: \"{$name}\"");
                
                // TRIGGER 5: NGO adds to wishlist - send email to all donors
                $donors_stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE role = 'donor'");
                $donors_stmt->execute();
                $all_donors = $donors_stmt->fetchAll();
                
                if (!empty($all_donors)) {
                    $email_body = "Hello,\n\n";
                    $email_body .= "An NGO has posted a need for a medicine on Medi-Share:\n\n";
                    $email_body .= "Medicine Needed: {$name}\n";
                    $email_body .= "Category: {$cat}\n";
                    $email_body .= "Quantity Needed: {$qty}\n";
                    if (!empty($desc)) {
                        $email_body .= "Note: {$desc}\n\n";
                    } else {
                        $email_body .= "\n";
                    }
                    $email_body .= "If you have this medicine available, please log in to your account to fulfil this request.\n\n";
                    $email_body .= "Thank you for your support,\nMedi-Share Team";
                    
                    foreach ($all_donors as $donor) {
                        send_email($donor['email'], $donor['full_name'], "Medi-Share: An NGO needs {$name}", $email_body);
                    }
                }
                
                $_SESSION['success'] = "Wishlist item posted!";
            } catch (PDOException $e) { $_SESSION['error'] = "Error posting."; }
        }
    }
    // NGO: Remove own wishlist item
    elseif ($action === 'remove_wish' && $role === 'ngo') {
        $wish_id = (int)($_POST['wish_id'] ?? 0);
        if ($wish_id > 0) {
            try {
                $pdo->prepare("DELETE FROM wishlists WHERE id=? AND ngo_id=?")->execute([$wish_id, $user_id]);
                $_SESSION['success'] = "Wishlist item removed.";
            } catch (PDOException $e) { $_SESSION['error'] = "Error removing."; }
        }
    }
    // Donor: Fulfil a wishlist need
    elseif ($action === 'fulfil_wish' && $role === 'donor') {
        $wish_id = (int)($_POST['wish_id'] ?? 0);
        if ($wish_id > 0) {
            try {
                $wish = $pdo->prepare("SELECT w.*, u.full_name as ngo_name FROM wishlists w JOIN users u ON w.ngo_id = u.user_id WHERE w.id=? AND w.status='open'");
                $wish->execute([$wish_id]);
                $w = $wish->fetch();
                if ($w) {
                    $pdo->prepare("UPDATE wishlists SET status='fulfilled', fulfilled_by=? WHERE id=?")->execute([$user_id, $wish_id]);
                    add_notification($pdo, $w['ngo_id'], "A donor wants to fulfil your wishlist need: \"{$w['medicine_name']}\". Check your notifications for donor contact.");
                    
                    // Get donor contact
                    $donor = $pdo->prepare("SELECT full_name, phone, contact_number, address FROM users WHERE user_id=?");
                    $donor->execute([$user_id]);
                    $d = $donor->fetch();
                    add_notification($pdo, $w['ngo_id'], "Donor Contact for \"{$w['medicine_name']}\": {$d['full_name']}, Phone: " . ($d['phone'] ?: $d['contact_number']) . ", Address: {$d['address']}");
                    
                    add_activity($pdo, 'fulfil', "A donor volunteered to fulfil a wishlist need for \"{$w['medicine_name']}\"");
                    audit_log($pdo, $user_id, 'wishlist_fulfilled', "Donor '{$user_name}' fulfilled wishlist #{$wish_id}: \"{$w['medicine_name']}\" for NGO '{$w['ngo_name']}'");
                    $_SESSION['success'] = "Thank you! The NGO has been notified.";
                }
            } catch (PDOException $e) { $_SESSION['error'] = "Error."; }
        }
    }
    header("Location: wishlist.php"); exit;
}

// Fetch all open wishlists
$wishlists = [];
try {
    $wishlists = $pdo->query("SELECT w.*, u.full_name as ngo_name FROM wishlists w JOIN users u ON w.ngo_id = u.user_id ORDER BY w.status ASC, w.created_at DESC")->fetchAll();
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
    <title>Medicine Wishlist - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../common/header.php'; ?>
    <div class="flex">
        <?php include '../common/sidebar.php'; ?>
        <main class="flex-1 min-h-screen p-4 md:p-8">
            <?php if ($success): ?><div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($error); ?></div><?php endif; ?>

            <h1 class="text-2xl font-bold text-gray-800 mb-2"><i class="fa-solid fa-heart text-pink-500 mr-2"></i>Medicine Wishlist</h1>
            <p class="text-gray-500 mb-6"><?php echo $role === 'ngo' ? 'Post medicines your organization needs — donors can fulfil them.' : 'See what NGOs need and volunteer to help.'; ?></p>

            <?php if ($role === 'ngo'): ?>
                <!-- NGO: Add Wishlist Item -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h2 class="text-lg font-bold text-gray-800 mb-4"><i class="fa-solid fa-plus text-blue-600 mr-2"></i>Post a Need</h2>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="add_wish">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Medicine Name</label><input type="text" name="medicine_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Category</label><select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg"><option value="Other">Other</option><option value="Antibiotic">Antibiotic</option><option value="Painkiller">Painkiller</option><option value="Vitamin">Vitamin</option><option value="Supplement">Supplement</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Qty Needed</label><input type="number" name="quantity_needed" min="1" value="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none"></div>
                        <div class="flex items-end"><button type="submit" class="w-full bg-pink-500 text-white py-2 px-4 rounded-lg font-semibold hover:bg-pink-600 transition"><i class="fa-solid fa-heart mr-2"></i>Post</button></div>
                        <div class="md:col-span-4"><textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none text-sm" placeholder="Describe why you need this (optional)"></textarea></div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Wishlist Items -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php if (count($wishlists) > 0): ?>
                    <?php foreach ($wishlists as $w): ?>
                        <div class="bg-white rounded-xl shadow-sm p-5 <?php echo $w['status'] === 'fulfilled' ? 'opacity-60' : ''; ?>">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-bold text-gray-800"><?php echo sanitize_output($w['medicine_name']); ?></h3>
                                    <p class="text-xs text-gray-500"><?php echo sanitize_output($w['category']); ?> • Qty: <?php echo (int)$w['quantity_needed']; ?></p>
                                </div>
                                <?php echo render_status_badge($w['status']); ?>
                            </div>
                            <p class="text-sm text-gray-600 mb-2"><i class="fa-solid fa-building-ngo text-purple-500 mr-1"></i><?php echo sanitize_output($w['ngo_name']); ?></p>
                            <?php if (!empty($w['description'])): ?>
                                <p class="text-sm text-gray-500 mb-3 italic">"<?php echo sanitize_output($w['description']); ?>"</p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mb-3"><i class="fa-regular fa-clock mr-1"></i><?php echo time_ago($w['created_at']); ?></p>
                            
                            <?php if ($w['status'] === 'open'): ?>
                                <?php if ($role === 'donor'): ?>
                                    <form method="POST"><?php csrf_field(); ?><input type="hidden" name="action" value="fulfil_wish"><input type="hidden" name="wish_id" value="<?php echo (int)$w['id']; ?>"><button type="submit" class="w-full bg-emerald-500 text-white py-2 rounded-lg text-sm font-semibold hover:bg-emerald-600 transition" onclick="return confirm('Volunteer to fulfil this need?')"><i class="fa-solid fa-hand-holding-heart mr-1"></i>I Can Help</button></form>
                                <?php elseif ($role === 'ngo' && (int)$w['ngo_id'] === $user_id): ?>
                                    <form method="POST"><?php csrf_field(); ?><input type="hidden" name="action" value="remove_wish"><input type="hidden" name="wish_id" value="<?php echo (int)$w['id']; ?>"><button type="submit" class="w-full bg-gray-200 text-gray-700 py-2 rounded-lg text-sm font-semibold hover:bg-gray-300 transition" onclick="return confirm('Remove this item?')"><i class="fa-solid fa-trash mr-1"></i>Remove</button></form>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-center text-emerald-600 text-sm font-medium"><i class="fa-solid fa-check-circle mr-1"></i>Fulfilled by a donor</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="md:col-span-3 bg-white rounded-xl shadow-sm p-8 text-center">
                        <i class="fa-solid fa-heart text-gray-300 text-5xl mb-4"></i>
                        <p class="text-gray-500">No wishlist items yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../common/script.js"></script>
</body>
</html>
