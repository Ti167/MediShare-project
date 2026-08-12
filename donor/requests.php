<?php
// Donor — Manage Requests: approve/reject with reason, request note display, HTML email with image
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: ../login.php"); exit;
}

$current_page = 'requests';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$role = 'donor';
$base_path = '..';
$pending_count = 0;

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        $_SESSION['error'] = "Invalid form submission.";
        header("Location: requests.php"); exit;
    }

    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'];
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    
    if ($request_id > 0 && in_array($action, ['approve', 'reject'])) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT r.medicine_id, r.ngo_id, r.request_note, m.medicine_name, m.image_path, u.full_name as ngo_name, u.email as ngo_email 
                                   FROM requests r 
                                   JOIN medicines m ON r.medicine_id = m.medicine_id 
                                   JOIN users u ON r.ngo_id = u.user_id 
                                   WHERE r.request_id = ?");
            $stmt->execute([$request_id]);
            $req = $stmt->fetch();
            
            if ($req) {
                $new_medicine_status = ($action === 'approve') ? 'donated' : 'available';
                $new_request_status = ($action === 'approve') ? 'approved' : 'rejected';
                
                // Update request with optional rejection reason
                if ($action === 'reject' && !empty($rejection_reason)) {
                    $pdo->prepare("UPDATE requests SET request_status = ?, rejection_reason = ? WHERE request_id = ?")->execute([$new_request_status, $rejection_reason, $request_id]);
                } else {
                    $pdo->prepare("UPDATE requests SET request_status = ? WHERE request_id = ?")->execute([$new_request_status, $request_id]);
                }
                $pdo->prepare("UPDATE medicines SET status = ? WHERE medicine_id = ?")->execute([$new_medicine_status, $req['medicine_id']]);
                
                $pdo->commit();
                
                if ($action === 'approve') {
                    add_notification($pdo, $req['ngo_id'], "Your request for \"{$req['medicine_name']}\" has been approved! Check donor contact details.");
                    
                    $donor_stmt = $pdo->prepare("SELECT full_name, phone, contact_number, address FROM users WHERE user_id = ?");
                    $donor_stmt->execute([$user_id]);
                    $donor_info = $donor_stmt->fetch();
                    $donor_phone = $donor_info['phone'] ?: $donor_info['contact_number'];
                    
                    $email_body = "Dear {$req['ngo_name']},\n\n";
                    $email_body .= "Great news! Your request for \"{$req['medicine_name']}\" has been approved by the donor.\n\n";
                    $email_body .= "Donor Contact Information:\n";
                    $email_body .= "Name: {$donor_info['full_name']}\n";
                    $email_body .= "Phone: {$donor_phone}\n";
                    $email_body .= "Address: {$donor_info['address']}\n\n";
                    $email_body .= "Please coordinate with the donor for pickup/delivery.\n\n";
                    $email_body .= "Thank you,\nMedi-Share Team";
                    
                    send_email($req['ngo_email'], $req['ngo_name'], 'Medi-Share: Your medicine request has been approved!', $email_body, $req['image_path']);
                    add_activity($pdo, 'donation', "A donor approved a request for \"{$req['medicine_name']}\"");
                } else {
                    $reason_text = !empty($rejection_reason) ? " Reason: {$rejection_reason}" : "";
                    add_notification($pdo, $req['ngo_id'], "Your request for \"{$req['medicine_name']}\" was not approved.{$reason_text}");
                    
                    $email_body = "Dear {$req['ngo_name']},\n\n";
                    $email_body .= "We regret to inform you that your request for \"{$req['medicine_name']}\" was not approved by the donor.\n\n";
                    if (!empty($rejection_reason)) {
                        $email_body .= "Reason: {$rejection_reason}\n\n";
                    }
                    $email_body .= "You may browse other available medicines on the platform.\n\n";
                    $email_body .= "Thank you,\nMedi-Share Team";
                    
                    send_email($req['ngo_email'], $req['ngo_name'], 'Medi-Share: Medicine request update', $email_body, $req['image_path']);
                }
                
                audit_log($pdo, $user_id, 'request_' . ($action === 'approve' ? 'approved' : 'rejected'), 
                    "Donor '{$user_name}' {$action}d request #{$request_id} for \"{$req['medicine_name']}\" from NGO '{$req['ngo_name']}'" . 
                    ($action === 'reject' && !empty($rejection_reason) ? " — Reason: {$rejection_reason}" : ""));
                
                $_SESSION['success'] = "Request " . $action . "d!";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error processing request.";
        }
    }
    header("Location: requests.php"); exit;
}

// Fetch requests
$requests = [];
try {
    $stmt = $pdo->prepare("SELECT r.request_id, r.request_status, r.request_date, r.request_note, r.rejection_reason,
                           m.medicine_name, m.quantity_strips, m.image_path,
                           u.full_name as ngo_name, u.address as ngo_address
                           FROM requests r JOIN medicines m ON r.medicine_id = m.medicine_id JOIN users u ON r.ngo_id = u.user_id
                           WHERE m.donor_id = ? ORDER BY r.request_date DESC");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {}

foreach ($requests as $r) {
    if ($r['request_status'] === 'pending') $pending_count++;
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
    <title>Requests - Medi-Share</title>
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
            
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Medicine Requests</h1>
            <?php if (count($requests) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($requests as $req): ?>
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex flex-col sm:flex-row justify-between items-start">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0"><?php echo render_medicine_thumbnail($req['image_path'] ?? null, $req['medicine_name']); ?></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800"><?php echo sanitize_output($req['medicine_name']); ?></h3>
                                        <p class="text-sm text-gray-600">Quantity: <?php echo (int)$req['quantity_strips']; ?> strips</p>
                                        <p class="text-sm text-gray-600 mt-2"><i class="fa-solid fa-building-ngo text-blue-500 mr-1"></i><?php echo sanitize_output($req['ngo_name']); ?></p>
                                        <p class="text-sm text-gray-600"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i><?php echo sanitize_output($req['ngo_address']); ?></p>
                                        
                                        <?php if (!empty($req['request_note'])): ?>
                                            <div class="mt-2 p-2 bg-blue-50/30 border border-blue-200 rounded-lg">
                                                <p class="text-xs font-semibold text-blue-700"><i class="fa-solid fa-message mr-1"></i>NGO Note:</p>
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
                                <div class="mt-4 sm:mt-0 text-right">
                                    <?php echo render_status_badge($req['request_status']); ?>
                                    <?php if ($req['request_status'] === 'pending'): ?>
                                        <div class="mt-3 space-y-2">
                                            <form method="POST" class="inline"><?php csrf_field(); ?><input type="hidden" name="request_id" value="<?php echo (int)$req['request_id']; ?>"><input type="hidden" name="action" value="approve"><button type="submit" class="px-3 py-1 bg-emerald-500 text-white text-sm rounded hover:bg-emerald-600"><i class="fa-solid fa-check mr-1"></i>Approve</button></form>
                                            <button onclick="openRejectModal(<?php echo (int)$req['request_id']; ?>)" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600"><i class="fa-solid fa-times mr-1"></i>Reject</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                    <i class="fa-solid fa-inbox text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No requests yet.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Reject Modal with Reason -->
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fa-solid fa-times-circle text-red-500 mr-2"></i>Reject Request</h3>
            <form method="POST" id="rejectForm">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" id="reject_request_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                    <textarea name="rejection_reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none" placeholder="Tell the NGO why the request was rejected..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">This will be visible to the NGO</p>
                </div>
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg font-semibold hover:bg-red-700 transition">Reject</button>
                    <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg font-semibold transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../common/script.js"></script>
    <script>
    function openRejectModal(id) {
        document.getElementById('reject_request_id').value = id;
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    </script>
</body>
</html>
