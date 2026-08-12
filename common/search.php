<?php
// Global Search — AJAX endpoint
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['results' => []]);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

header('Content-Type: application/json');
$results = [];
$role = $_SESSION['role'];
$param = "%{$q}%";

try {
    // Search medicines
    $stmt = $pdo->prepare("SELECT m.medicine_id, m.medicine_name, m.status, m.category, 'medicine' as type
                           FROM medicines m WHERE m.medicine_name LIKE ? LIMIT 5");
    $stmt->execute([$param]);
    while ($row = $stmt->fetch()) {
        $results[] = [
            'type' => 'medicine',
            'title' => $row['medicine_name'],
            'subtitle' => $row['category'] . ' — ' . ucfirst($row['status']),
            'icon' => 'fa-pills',
            'color' => 'text-emerald-500',
            'url' => ($role === 'admin') ? 'admin/admin_medicines.php' : (($role === 'ngo') ? 'ngo/index.php?search=' . urlencode($row['medicine_name']) : 'donor/index.php')
        ];
    }
    
    // Admin-only: search users
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT user_id, full_name, email, role FROM users WHERE full_name LIKE ? OR email LIKE ? LIMIT 5");
        $stmt->execute([$param, $param]);
        while ($row = $stmt->fetch()) {
            $results[] = [
                'type' => 'user',
                'title' => $row['full_name'],
                'subtitle' => $row['email'] . ' — ' . ucfirst($row['role']),
                'icon' => 'fa-user',
                'color' => 'text-blue-500',
                'url' => 'admin/admin_users.php'
            ];
        }
    }
} catch (PDOException $e) {}

echo json_encode(['results' => $results]);
