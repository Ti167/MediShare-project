<?php
// User Login & Role-Based Routing
require_once 'db.php';
require_once 'helpers.php';
init_secure_session();

$message = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'donor':
            header("Location: donor/index.php");
            exit;
        case 'ngo':
            header("Location: ngo/index.php");
            exit;
        case 'admin':
            header("Location: admin/admin_dashboard.php");
            exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $message = "Invalid form submission. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_active'] = time();

                    switch ($user['role']) {
                        case 'donor':
                            header("Location: donor/index.php");
                            exit;
                        case 'ngo':
                            header("Location: ngo/index.php");
                            exit;
                        case 'admin':
                            header("Location: admin/admin_dashboard.php");
                            exit;
                    }
                } else {
                    $message = "Invalid email or password!";
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
    <title>Login - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-4 my-8">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-6">
                <i class="fa-solid fa-pills text-blue-600 text-4xl"></i>
                <h1 class="text-2xl font-bold text-gray-800 mt-2">Medi-Share</h1>
                <p class="text-gray-500">Welcome back!</p>
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
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                           placeholder="Enter your email">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                           placeholder="Enter your password">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fa-solid fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>
            <div class="mt-4 text-center">
                <p class="text-gray-600">Don't have an account? 
                    <a href="register.php" class="text-blue-600 hover:underline font-medium">Register here</a>
                </p>
            </div>
            <div class="mt-4 text-center">
                <a href="admin/login.php" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-shield-halved mr-1"></i>Admin Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
