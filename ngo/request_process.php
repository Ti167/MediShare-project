<?php
// NGO — Request Processing with verification gate, request note, notification, audit
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ngo') {
    header("Location: ../login.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['medicine_id'])) {
    header("Location: index.php"); exit;
}

if (!verify_csrf_token()) {
    $_SESSION['error'] = "Invalid form submission.";
    header("Location: index.php"); exit;
}

// Verification gate
$ngo_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE user_id = ?");
    $stmt->execute([$ngo_id]);
    $ngo = $stmt->fetch();
    if (!$ngo || (int)$ngo['is_verified'] !== 1) {
        $_SESSION['error'] = "Your account is pending admin verification.";
        header("Location: index.php"); exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error checking verification.";
    header("Location: index.php"); exit;
}

$medicine_id = (int)$_POST['medicine_id'];
$request_note = trim($_POST['request_note'] ?? '');
// Limit note length
if (strlen($request_note) > 500) $request_note = substr($request_note, 0, 500);

if ($medicine_id <= 0) {
    $_SESSION['error'] = "Invalid medicine!";
    header("Location: index.php"); exit;
}

try {
    $checkStmt = $pdo->prepare("SELECT m.medicine_id, m.medicine_name, m.donor_id FROM medicines m WHERE m.medicine_id = ? AND m.status = 'available' AND m.expiry_date > CURDATE()");
    $checkStmt->execute([$medicine_id]);
    $medicine = $checkStmt->fetch();
    
    if (!$medicine) {
        $_SESSION['error'] = "Medicine not available!";
        header("Location: index.php"); exit;
    }
    
    $pdo->beginTransaction();
    
    $insertStmt = $pdo->prepare("INSERT INTO requests (ngo_id, medicine_id, request_status, request_note) VALUES (?, ?, 'pending', ?)");
    $insertStmt->execute([$ngo_id, $medicine_id, $request_note ?: null]);
    
    $updateStmt = $pdo->prepare("UPDATE medicines SET status = 'requested' WHERE medicine_id = ?");
    $updateStmt->execute([$medicine_id]);
    
    $pdo->commit();
    
    // Notify donor — include note preview if provided
    $notif_msg = "An NGO has requested your medicine: \"{$medicine['medicine_name']}\".";
    if (!empty($request_note)) {
        $notif_msg .= " Note: \"" . substr($request_note, 0, 80) . (strlen($request_note) > 80 ? '...' : '') . "\"";
    }
    add_notification($pdo, $medicine['donor_id'], $notif_msg);
    
    // Activity feed
    add_activity($pdo, 'request', "An NGO requested \"{$medicine['medicine_name']}\"");
    
    audit_log($pdo, $ngo_id, 'request_submitted', "NGO '{$_SESSION['full_name']}' requested medicine: \"{$medicine['medicine_name']}\"");
    
    $_SESSION['success'] = "Request submitted successfully!";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to submit request!";
}

header("Location: index.php");
exit;
