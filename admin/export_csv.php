<?php
// Admin — CSV Export (medicines, users, donations)
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php"); exit;
}

$type = $_GET['type'] ?? '';

if (!in_array($type, ['medicines', 'users', 'donations'])) {
    $_SESSION['error'] = "Invalid export type.";
    header("Location: admin_dashboard.php"); exit;
}

$filename = "medishare_{$type}_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

try {
    if ($type === 'medicines') {
        fputcsv($output, ['ID', 'Medicine Name', 'Donor', 'Category', 'Expiry Date', 'Quantity', 'Status', 'Listed At']);
        $rows = $pdo->query("SELECT m.medicine_id, m.medicine_name, u.full_name, m.category, m.expiry_date, m.quantity_strips, m.status, m.listed_at 
                            FROM medicines m JOIN users u ON m.donor_id = u.user_id ORDER BY m.listed_at DESC")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [$r['medicine_id'], $r['medicine_name'], $r['full_name'], $r['category'] ?? 'Other', $r['expiry_date'], $r['quantity_strips'], $r['status'], $r['listed_at']]);
        }
    } elseif ($type === 'users') {
        fputcsv($output, ['ID', 'Full Name', 'Email', 'Role', 'Verified', 'Phone', 'Address', 'Registered At']);
        $rows = $pdo->query("SELECT user_id, full_name, email, role, is_verified, phone, contact_number, address, created_at FROM users ORDER BY created_at DESC")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [$r['user_id'], $r['full_name'], $r['email'], $r['role'], $r['is_verified'] ? 'Yes' : 'No', $r['phone'] ?: $r['contact_number'], $r['address'], $r['created_at']]);
        }
    } elseif ($type === 'donations') {
        fputcsv($output, ['Request ID', 'Medicine', 'Donor', 'NGO', 'Status', 'Request Date', 'Note', 'Rejection Reason']);
        $rows = $pdo->query("SELECT r.request_id, m.medicine_name, d.full_name as donor_name, n.full_name as ngo_name, r.request_status, r.request_date, r.request_note, r.rejection_reason
                            FROM requests r JOIN medicines m ON r.medicine_id = m.medicine_id JOIN users d ON m.donor_id = d.user_id JOIN users n ON r.ngo_id = n.user_id 
                            ORDER BY r.request_date DESC")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [$r['request_id'], $r['medicine_name'], $r['donor_name'], $r['ngo_name'], $r['request_status'], $r['request_date'], $r['request_note'] ?? '', $r['rejection_reason'] ?? '']);
        }
    }
} catch (PDOException $e) {
    fputcsv($output, ['Error generating export']);
}

fclose($output);
audit_log($pdo, $_SESSION['user_id'], 'csv_export', "Admin exported CSV: {$type}");
exit;
