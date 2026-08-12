<?php
// Admin Login with CSRF and session hardening
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

$message = '';

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $message = "Invalid form submission. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_active'] = time();
                    
                    header("Location: admin_dashboard.php");
                    exit;
                } else {
                    $message = "Invalid admin credentials!";
                }
            } catch (PDOException $e) {
                $message = "Login failed. Please try again.";
            }
        } else {
            $message = "Email and password are required!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-4">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-6">
                <i class="fa-solid fa-shield-halved text-purple-600 text-4xl"></i>
                <h1 class="text-2xl font-bold text-gray-800 mt-2">Admin Login</h1>
                <p class="text-gray-500">Medi-Share Administration</p>
            </div>
            
            <?php if ($message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <?php echo sanitize_output($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php csrf_field(); ?>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none">
                </div>
                <button type="submit" class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition">
                    <i class="fa-solid fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>
            <div class="mt-4 text-center">
                <a href="../login.php" class="text-blue-600 hover:underline">Back to User Login</a>
            </div>
        </div>
    </div>
</body>
</html>
