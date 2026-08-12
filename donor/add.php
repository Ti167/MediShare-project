<?php
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: ../login.php");
    exit;
}

$current_page = 'add';
$user_name = $_SESSION['full_name'];
$role = 'donor';
$base_path = '..';
$pending_count = 0;

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medicine - Medi-Share</title>
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

            <h1 class="text-2xl font-bold text-gray-800 mb-6">Add New Medicine</h1>
            <div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
                <form method="POST" action="add_process.php" enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Medicine Name</label>
                        <input type="text" name="medicine_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Enter medicine name">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Expiry Date</label>
                            <input type="date" name="expiry_date" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Quantity (Strips)</label>
                            <input type="number" name="quantity_strips" required min="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Enter quantity">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Category</label>
                            <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="Other">Other</option>
                                <option value="Antibiotic">Antibiotic</option>
                                <option value="Painkiller">Painkiller</option>
                                <option value="Vitamin">Vitamin</option>
                                <option value="Supplement">Supplement</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Image (Optional)</label>
                            <input type="file" name="medicine_image" id="medicineImage" accept=".jpg,.jpeg,.png,.webp" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:bg-blue-50 file:text-blue-600 file:font-medium">
                            <p class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, WebP</p>
                            <div id="imagePreviewContainer" class="mt-3 hidden">
                                <img id="imagePreview" class="max-w-xs max-h-48 rounded-lg border border-gray-300 object-cover">
                                <button type="button" id="removeImage" class="mt-2 text-red-600 text-sm hover:text-red-800">
                                    <i class="fa-solid fa-times-circle mr-1"></i>Remove image
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-emerald-500 text-white py-3 rounded-lg font-semibold hover:bg-emerald-600 transition">
                        <i class="fa-solid fa-plus mr-2"></i>Add Medicine
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
    // Image preview functionality
    document.getElementById('medicineImage').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreviewContainer').classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    });
    
    document.getElementById('removeImage').addEventListener('click', function() {
        document.getElementById('medicineImage').value = '';
        document.getElementById('imagePreview').src = '';
        document.getElementById('imagePreviewContainer').classList.add('hidden');
    });
    </script>
    <script src="../common/script.js"></script>
</body>
</html>
