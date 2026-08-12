<?php
require_once 'db.php';
require_once 'helpers.php';

$message = '';
$autoload_path = __DIR__ . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!empty($email)) {
        $result = send_email($email, 'Test User', 'Medi-Share Test Email', 'This is a test email from Medi-Share. If you received this, email is working correctly!');
        $message = $result ? "✓ Email sent successfully!" : "✗ Failed to send email";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white p-6 rounded-xl shadow">
        <h1 class="text-2xl font-bold mb-6">Medi-Share Email Test</h1>
        
        <div class="mb-4 p-3 bg-gray-50 rounded text-sm">
            <p><strong>Autoload path:</strong> <?php echo $autoload_path; ?></p>
            <p><strong>Autoload exists:</strong> <?php echo file_exists($autoload_path) ? "✅ Yes" : "❌ No"; ?></p>
        </div>
        
        <?php if ($message): ?>
        <div class="mb-4 p-3 rounded <?php echo $result ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Test Email Address</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border rounded" placeholder="your@email.com">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Send Test Email</button>
        </form>
    </div>
</body>
</html>