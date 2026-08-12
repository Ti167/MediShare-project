<?php
// Add Medicine Processing — with category, image upload, CSRF, audit log
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// CSRF verification
if (!verify_csrf_token()) {
    $_SESSION['error'] = "Invalid form submission. Please try again.";
    header("Location: index.php");
    exit;
}

$medicine_name = trim($_POST['medicine_name'] ?? '');
$expiry_date = $_POST['expiry_date'] ?? '';
$quantity_strips = (int)($_POST['quantity_strips'] ?? 0);
$category = $_POST['category'] ?? 'Other';

// Validate category
$valid_categories = ['Antibiotic', 'Painkiller', 'Vitamin', 'Supplement', 'Other'];
if (!in_array($category, $valid_categories)) {
    $category = 'Other';
}

if (empty($medicine_name) || empty($expiry_date) || $quantity_strips <= 0) {
    $_SESSION['error'] = "All fields are required!";
    header("Location: index.php");
    exit;
}

// Validate expiry date is in the future
$today = new DateTime('today');
$expiry = new DateTime($expiry_date);

if ($expiry <= $today) {
    $_SESSION['error'] = "Expiry date must be in the future! Expired medicines cannot be listed.";
    header("Location: index.php");
    exit;
}

// Handle image upload
$image_path = null;
if (isset($_FILES['medicine_image']) && $_FILES['medicine_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['medicine_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file['type'], $allowed_types) || !in_array($ext, $allowed_exts)) {
        $_SESSION['error'] = "Invalid image format. Only JPG, PNG, and WebP are allowed.";
        header("Location: index.php");
        exit;
    }
    
    // Generate unique filename
    $new_filename = uniqid('med_', true) . '.' . $ext;
    $upload_dir = dirname(__DIR__) . '/uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
        $image_path = $new_filename;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO medicines (donor_id, medicine_name, expiry_date, quantity_strips, category, image_path, status) VALUES (?, ?, ?, ?, ?, ?, 'available')");
    $stmt->execute([$_SESSION['user_id'], $medicine_name, $expiry_date, $quantity_strips, $category, $image_path]);
    
    // Audit log
    audit_log($pdo, $_SESSION['user_id'], 'medicine_listed', "Donor '{$_SESSION['full_name']}' listed medicine: {$medicine_name} (Category: {$category}, Qty: {$quantity_strips})");
    add_activity($pdo, 'listing', "A donor listed \"{$medicine_name}\"");
    
    // TRIGGER 4: Donor adds new medicine - send email to all verified NGOs
    $ngos_stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE role = 'ngo' AND is_verified = 1");
    $ngos_stmt->execute();
    $verified_ngos = $ngos_stmt->fetchAll();
    
    if (!empty($verified_ngos)) {
        $email_body = "Hello,\n\n";
        $email_body .= "A new medicine has been listed on Medi-Share:\n\n";
        $email_body .= "Medicine: {$medicine_name}\n";
        $email_body .= "Category: {$category}\n";
        $email_body .= "Quantity: {$quantity_strips} strips\n";
        $email_body .= "Expiry Date: {$expiry_date}\n\n";
        $email_body .= "Log in to your account now to browse and request this medicine.\n\n";
        $email_body .= "Thank you,\nMedi-Share Team";
        
        foreach ($verified_ngos as $ngo) {
            send_email($ngo['email'], $ngo['full_name'], "Medi-Share: New medicine available — {$medicine_name}", $email_body, $image_path);
        }
    }
    
    $_SESSION['success'] = "Medicine added successfully!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to add medicine. Please try again.";
}

header("Location: index.php");
exit;
