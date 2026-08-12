<?php
// User Registration
require_once 'db.php';
require_once 'helpers.php';
init_secure_session();

$message = '';
$is_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $message = "Invalid form submission. Please try again.";
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'donor';
        $contact_number = trim($_POST['contact_number'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($full_name && $email && $password && $contact_number && $address) {
            if (!in_array($role, ['donor', 'ngo'])) {
                $role = 'donor';
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Donors are auto-verified, NGOs need admin verification
            $is_verified = ($role === 'donor') ? 1 : 0;

            try {
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, contact_number, phone, address, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $hashed_password, $role, $contact_number, $contact_number, $address, $is_verified]);

                $is_success = true;
                if ($role === 'ngo') {
                    $message = "Registration successful! Your NGO account is pending admin verification. <a href='login.php' class='text-blue-600 hover:underline'>Login here</a>";
                } else {
                    $message = "Registration successful! <a href='login.php' class='text-blue-600 hover:underline'>Login here</a>";
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $message = "Email already registered!";
                } else {
                    $message = "Registration failed. Please try again.";
                }
            }
        } else {
            $message = "All fields are required!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-emerald-50 to-blue-100 min-h-screen flex items-center justify-center py-8">
    <div class="w-full max-w-lg mx-4">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-6">
                <i class="fa-solid fa-pills text-blue-600 text-4xl"></i>
                <h1 class="text-2xl font-bold text-gray-800 mt-2">Create Account</h1>
                <p class="text-gray-500">Join Medi-Share today</p>
            </div>
            
            <?php if ($message): ?>
                <div class="<?php echo $is_success ? 'bg-emerald-100 border border-emerald-400 text-emerald-700' : 'bg-red-100 border border-red-400 text-red-700'; ?> px-4 py-3 rounded-lg mb-4">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php csrf_field(); ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Full Name</label>
                        <input type="text" name="full_name" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                               placeholder="Enter your name">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Email</label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Password</label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                               placeholder="Create a password">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Register As</label>
                        <select name="role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="donor">Donor (Individual/Pharmacy)</option>
                            <option value="ngo">NGO/Clinic</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Contact Number</label>
                    <input type="text" name="contact_number" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                           placeholder="Enter contact number">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Address</label>
                    <textarea name="address" required rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                              placeholder="Enter your address"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-emerald-500 text-white py-3 rounded-lg font-semibold hover:bg-emerald-600 transition">
                    <i class="fa-solid fa-user-plus mr-2"></i>Register
                </button>
            </form>
            <div class="mt-4 text-center">
                <p class="text-gray-600">Already have an account? 
                    <a href="login.php" class="text-blue-600 hover:underline font-medium">Login here</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
