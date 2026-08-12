<?php
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: ../login.php"); exit;
}

$current_page = 'dashboard';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$role = 'donor';
$base_path = '..';

// Handle Edit Medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        $_SESSION['error'] = "Invalid form submission.";
        header("Location: index.php"); exit;
    }
    $action = $_POST['action'];
    $med_id = (int)($_POST['medicine_id'] ?? 0);

    if ($action === 'edit_medicine' && $med_id > 0) {
        $new_name = trim($_POST['medicine_name'] ?? '');
        $new_expiry = $_POST['expiry_date'] ?? '';
        $new_qty = (int)($_POST['quantity_strips'] ?? 0);
        $new_cat = $_POST['category'] ?? 'Other';
        if (!empty($new_name) && !empty($new_expiry) && $new_qty > 0) {
            try {
                // Handle image upload if provided
                $image_path = null;
                if (isset($_FILES['medicine_image']) && $_FILES['medicine_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['medicine_image'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if (in_array($file['type'], $allowed_types) && in_array($ext, $allowed_exts)) {
                        $new_filename = uniqid('med_', true) . '.' . $ext;
                        $upload_dir = dirname(__DIR__) . '/uploads/';
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                            $image_path = $new_filename;
                        }
                    }
                }
                
                // Only allow edit if not donated
                if ($image_path) {
                    $stmt = $pdo->prepare("UPDATE medicines SET medicine_name=?, expiry_date=?, quantity_strips=?, category=?, image_path=? WHERE medicine_id=? AND donor_id=? AND status != 'donated'");
                    $stmt->execute([$new_name, $new_expiry, $new_qty, $new_cat, $image_path, $med_id, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE medicines SET medicine_name=?, expiry_date=?, quantity_strips=?, category=? WHERE medicine_id=? AND donor_id=? AND status != 'donated'");
                    $stmt->execute([$new_name, $new_expiry, $new_qty, $new_cat, $med_id, $user_id]);
                }
                
                if ($stmt->rowCount()) {
                    audit_log($pdo, $user_id, 'medicine_edited', "Donor edited medicine #{$med_id}: {$new_name}");
                    $_SESSION['success'] = "Medicine updated!";
                } else {
                    $_SESSION['error'] = "Cannot edit donated medicine.";
                }
            } catch (PDOException $e) { $_SESSION['error'] = "Error updating."; }
        }
    } elseif ($action === 'withdraw_medicine' && $med_id > 0) {
        try {
            // Only allow withdraw if available or requested (not donated)
            $stmt = $pdo->prepare("SELECT status FROM medicines WHERE medicine_id=? AND donor_id=?");
            $stmt->execute([$med_id, $user_id]);
            $med = $stmt->fetch();
            if ($med && $med['status'] !== 'donated') {
                // If requested, reject any pending requests first
                if ($med['status'] === 'requested') {
                    $pdo->prepare("UPDATE requests SET request_status='rejected', rejection_reason='Medicine withdrawn by donor' WHERE medicine_id=? AND request_status='pending'")->execute([$med_id]);
                }
                $pdo->prepare("DELETE FROM medicines WHERE medicine_id=? AND donor_id=?")->execute([$med_id, $user_id]);
                audit_log($pdo, $user_id, 'medicine_withdrawn', "Donor withdrew medicine #{$med_id}");
                add_activity($pdo, 'withdraw', 'A donor withdrew a medicine listing');
                $_SESSION['success'] = "Medicine withdrawn!";
            } else {
                $_SESSION['error'] = "Cannot withdraw donated medicine.";
            }
        } catch (PDOException $e) { $_SESSION['error'] = "Error withdrawing."; }
    }
    header("Location: index.php"); exit;
}

$medicines = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE donor_id = ? ORDER BY listed_at DESC");
    $stmt->execute([$user_id]);
    $medicines = $stmt->fetchAll();
} catch (PDOException $e) {}

$total_listed = count($medicines);
$successful_donations = 0;
$pending_requests = 0;
$available_count = 0;
foreach ($medicines as $med) {
    if ($med['status'] === 'donated') $successful_donations++;
    elseif ($med['status'] === 'requested') $pending_requests++;
    elseif ($med['status'] === 'available') $available_count++;
}
$pending_count = $pending_requests;

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - Medi-Share</title>
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

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-600">
                    <p class="text-sm text-gray-500">Total Listed</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $total_listed; ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-emerald-500">
                    <p class="text-sm text-gray-500">Donated</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $successful_donations; ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-yellow-500">
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $pending_requests; ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
                    <p class="text-sm text-gray-500">Available</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $available_count; ?></p>
                </div>
            </div>

            <!-- Quick Add -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fa-solid fa-plus-circle text-blue-600 mr-2"></i>Quick Add</h2>
                <form method="POST" action="add_process.php" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <?php csrf_field(); ?>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Name</label><input type="text" name="medicine_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Expiry</label><input type="date" name="expiry_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Qty</label><input type="number" name="quantity_strips" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Category</label><select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg"><option value="Other">Other</option><option value="Antibiotic">Antibiotic</option><option value="Painkiller">Painkiller</option><option value="Vitamin">Vitamin</option><option value="Supplement">Supplement</option></select></div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="w-full bg-emerald-500 text-white py-2 px-4 rounded-lg font-semibold hover:bg-emerald-600 transition"><i class="fa-solid fa-plus mr-2"></i>Add</button>
                    </div>
                    <div class="md:col-span-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Image (Optional)</label>
                        <input type="file" name="medicine_image" id="quickMedicineImage" accept=".jpg,.jpeg,.png,.webp" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:bg-blue-50 file:text-blue-600 file:font-medium">
                        <div id="quickImagePreviewContainer" class="mt-2 hidden">
                            <img id="quickImagePreview" class="max-w-24 max-h-24 rounded-lg border border-gray-300 object-cover">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Medicines Table with Edit/Withdraw -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200"><h2 class="text-xl font-bold text-gray-800"><i class="fa-solid fa-list text-blue-600 mr-2"></i>Your Listed Medicines</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (count($medicines) > 0): ?>
                                <?php foreach ($medicines as $m): ?>
                                    <?php $expiry_info = get_expiry_coding($m['expiry_date']); ?>
                                    <tr class="hover:bg-gray-50 <?php echo $expiry_info['row_class']; ?>">
                                        <td class="px-4 py-4"><?php echo render_medicine_thumbnail($m['image_path'] ?? null, $m['medicine_name']); ?></td>
                                        <td class="px-4 py-4 text-gray-800 font-medium"><?php echo sanitize_output($m['medicine_name']); ?></td>
                                        <td class="px-4 py-4 text-gray-600 text-sm"><?php echo sanitize_output($m['category'] ?? 'Other'); ?></td>
                                        <td class="px-4 py-4 <?php echo $expiry_info['class']; ?>">
                                            <?php echo sanitize_output($m['expiry_date']); ?>
                                            <?php if ($expiry_info['label']): ?><span class="block text-xs <?php echo $expiry_info['badge']; ?> px-1 py-0.5 rounded mt-1 inline-block"><?php echo $expiry_info['label']; ?></span><?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 text-gray-600"><?php echo (int)$m['quantity_strips']; ?></td>
                                        <td class="px-4 py-4"><?php echo render_status_badge($m['status']); ?></td>
                                        <td class="px-4 py-4">
                                            <?php if ($m['status'] !== 'donated'): ?>
                                                <button onclick="openEditModal(<?php echo (int)$m['medicine_id']; ?>, '<?php echo addslashes($m['medicine_name']); ?>', '<?php echo $m['expiry_date']; ?>', <?php echo (int)$m['quantity_strips']; ?>, '<?php echo $m['category'] ?? 'Other'; ?>')" class="text-blue-600 hover:text-blue-800 text-sm mr-2" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                                <form method="POST" class="inline"><?php csrf_field(); ?><input type="hidden" name="action" value="withdraw_medicine"><input type="hidden" name="medicine_id" value="<?php echo (int)$m['medicine_id']; ?>"><button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Withdraw this medicine?')" title="Withdraw"><i class="fa-solid fa-xmark"></i></button></form>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No medicines listed yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="mt-8">
                <?php include '../common/activity_feed.php'; render_activity_feed($pdo); ?>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fa-solid fa-pen-to-square text-blue-600 mr-2"></i>Edit Medicine</h3>
            <form method="POST" id="editForm">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="edit_medicine">
                <input type="hidden" name="medicine_id" id="edit_id">
                <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1">Name</label><input type="text" name="medicine_name" id="edit_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none"></div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Expiry</label><input type="date" name="expiry_date" id="edit_expiry" required class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Qty</label><input type="number" name="quantity_strips" id="edit_qty" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none"></div>
                </div>
                <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Category</label><select name="category" id="edit_cat" class="w-full px-4 py-2 border border-gray-300 rounded-lg"><option value="Other">Other</option><option value="Antibiotic">Antibiotic</option><option value="Painkiller">Painkiller</option><option value="Vitamin">Vitamin</option><option value="Supplement">Supplement</option></select></div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Update Image (Optional)</label>
                    <input type="file" name="medicine_image" id="editMedicineImage" accept=".jpg,.jpeg,.png,.webp" class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:bg-blue-50 file:text-blue-600 file:font-medium">
                    <div id="editImagePreviewContainer" class="mt-2 hidden">
                        <img id="editImagePreview" class="max-w-24 max-h-24 rounded-lg border border-gray-300 object-cover">
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">Save</button>
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-300 transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../common/script.js"></script>
    <script>
    // Quick add image preview
    document.getElementById('quickMedicineImage')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('quickImagePreview').src = e.target.result;
                document.getElementById('quickImagePreviewContainer').classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Edit modal image preview
    document.getElementById('editMedicineImage')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editImagePreview').src = e.target.result;
                document.getElementById('editImagePreviewContainer').classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    });
    
    function openEditModal(id, name, expiry, qty, cat) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_expiry').value = expiry;
        document.getElementById('edit_qty').value = qty;
        document.getElementById('edit_cat').value = cat;
        // Reset image preview when opening modal
        document.getElementById('editMedicineImage').value = '';
        document.getElementById('editImagePreview').src = '';
        document.getElementById('editImagePreviewContainer').classList.add('hidden');
        document.getElementById('editModal').classList.remove('hidden');
    }
    </script>
</body>
</html>
