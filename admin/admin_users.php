<?php
// Admin — User Management with NGO verify/revoke, pagination, CSRF, audit
require_once '../db.php';
require_once '../helpers.php';
init_secure_session();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION['full_name'] ?? 'Admin';
$current_admin_page = 'users';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        $_SESSION['error'] = "Invalid form submission.";
        header("Location: admin_users.php");
        exit;
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'];

    if ($target_user_id > 0) {
        if ($action === 'delete_user') {
            try {
                // Get user info before deletion
                $info = $pdo->prepare("SELECT full_name, role FROM users WHERE user_id = ? AND role != 'admin'");
                $info->execute([$target_user_id]);
                $target = $info->fetch();
                if ($target) {
                    $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'")->execute([$target_user_id]);
                    audit_log($pdo, $_SESSION['user_id'], 'user_banned', "Admin deleted user: '{$target['full_name']}' (Role: {$target['role']})");
                    $_SESSION['success'] = "User deleted!";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        } elseif ($action === 'verify_ngo') {
            try {
                $pdo->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ? AND role = 'ngo'")->execute([$target_user_id]);
                $info = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
                $info->execute([$target_user_id]);
                $target = $info->fetch();
                
                // Notification
                add_notification($pdo, $target_user_id, "Your account has been verified. You can now request medicines.");
                
                // TRIGGER 3: Admin verifies an NGO account - send email notification
                $email_body = "Dear {$target['full_name']},\n\n";
                $email_body .= "Great news! Your Medi-Share NGO account has been verified by our admin team.\n\n";
                $email_body .= "You can now browse all available medicines on the platform and submit donation requests.\n\n";
                $email_body .= "Thank you for partnering with us to help those in need.\n\n";
                $email_body .= "Best regards,\nMedi-Share Team";
                
                send_email($target['email'], $target['full_name'], 'Medi-Share: Your account has been verified', $email_body);
                
                audit_log($pdo, $_SESSION['user_id'], 'ngo_verified', "Admin verified NGO: '{$target['full_name']}'");
                $_SESSION['success'] = "NGO verified!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error verifying NGO.";
            }
        } elseif ($action === 'revoke_ngo') {
            try {
                $pdo->prepare("UPDATE users SET is_verified = 0 WHERE user_id = ? AND role = 'ngo'")->execute([$target_user_id]);
                $info = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
                $info->execute([$target_user_id]);
                $target = $info->fetch();
                
                audit_log($pdo, $_SESSION['user_id'], 'ngo_revoked', "Admin revoked verification for NGO: '{$target['full_name']}'");
                $_SESSION['success'] = "NGO verification revoked!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error revoking NGO.";
            }
        } elseif ($action === 'add_admin_note') {
            $note_text = trim($_POST['note'] ?? '');
            if (!empty($note_text)) {
                try {
                    $pdo->prepare("INSERT INTO admin_notes (user_id, note, created_by) VALUES (?,?,?)")->execute([$target_user_id, $note_text, $_SESSION['user_id']]);
                    $_SESSION['success'] = "Note added!";
                } catch (PDOException $e) { $_SESSION['error'] = "Error adding note."; }
            }
        }
    }
    header("Location: admin_users.php");
    exit;
}

// Pagination
$per_page = 20;
$total_users = (int)$pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'];
$total_pages = max(1, ceil($total_users / $per_page));
$current_pg = max(1, min((int)($_GET['page'] ?? 1), $total_pages));
$offset = ($current_pg - 1) * $per_page;

$users = $pdo->query("SELECT user_id, full_name, email, role, is_verified, created_at FROM users ORDER BY created_at DESC LIMIT $per_page OFFSET $offset")->fetchAll();

// Fetch admin notes for all displayed users
$admin_notes = [];
try {
    $user_ids = array_column($users, 'user_id');
    if (!empty($user_ids)) {
        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM admin_notes WHERE user_id IN ($placeholders) ORDER BY created_at DESC");
        $stmt->execute($user_ids);
        while ($n = $stmt->fetch()) {
            $admin_notes[$n['user_id']][] = $n;
        }
    }
} catch (PDOException $e) {}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Medi-Share Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'admin_sidebar.php'; ?>

        <main class="flex-1 min-h-screen p-4 md:p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Manage Users</h1>
            <?php if ($success): ?><div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo sanitize_output($error); ?></div><?php endif; ?>

            <!-- Desktop Table -->
            <div class="hidden md:block bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verification</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $u): ?>
                                <?php $user_notes = $admin_notes[$u['user_id']] ?? []; ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-gray-600"><?php echo (int)$u['user_id']; ?></td>
                                    <td class="px-4 py-4 text-gray-800 font-medium"><?php echo sanitize_output($u['full_name']); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo sanitize_output($u['email']); ?></td>
                                    <td class="px-4 py-4"><?php echo render_status_badge($u['role']); ?></td>
                                    <td class="px-4 py-4">
                                        <?php if ($u['role'] === 'ngo'): ?>
                                            <?php echo render_status_badge($u['is_verified'] ? 'verified' : 'unverified'); ?>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?php if (!empty($user_notes)): ?>
                                            <?php foreach (array_slice($user_notes, 0, 2) as $note): ?>
                                                <div class="text-xs bg-yellow-50 text-yellow-800 px-2 py-1 rounded mb-1 border border-yellow-200 max-w-xs truncate" title="<?php echo sanitize_output($note['note']); ?>">
                                                    <i class="fa-solid fa-note-sticky mr-1"></i><?php echo sanitize_output(substr($note['note'], 0, 50)); ?><?php echo strlen($note['note']) > 50 ? '...' : ''; ?>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($user_notes) > 2): ?><p class="text-xs text-gray-400">+<?php echo count($user_notes) - 2; ?> more</p><?php endif; ?>
                                        <?php endif; ?>
                                        <button onclick="openNoteModal(<?php echo (int)$u['user_id']; ?>, '<?php echo addslashes($u['full_name']); ?>')" class="text-yellow-600 hover:text-yellow-800 text-xs"><i class="fa-solid fa-plus mr-1"></i>Add Note</button>
                                    </td>
                                    <td class="px-4 py-4 space-x-2">
                                        <?php if ($u['role'] === 'ngo'): ?>
                                            <?php if ($u['is_verified']): ?>
                                                <form method="POST" class="inline"><?php csrf_field(); ?><input type="hidden" name="action" value="revoke_ngo"><input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>"><button type="submit" class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded hover:bg-amber-200" onclick="return confirm('Revoke verification?')"><i class="fa-solid fa-ban mr-1"></i>Revoke</button></form>
                                            <?php else: ?>
                                                <form method="POST" class="inline"><?php csrf_field(); ?><input type="hidden" name="action" value="verify_ngo"><input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>"><button type="submit" class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs rounded hover:bg-emerald-200"><i class="fa-solid fa-check mr-1"></i>Verify</button></form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($u['role'] !== 'admin'): ?>
                                            <form method="POST" class="inline"><?php csrf_field(); ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>"><button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Delete user?')"><i class="fa-solid fa-trash"></i></button></form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?><tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No users.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden space-y-4">
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $u): ?>
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs text-gray-500">ID: <?php echo (int)$u['user_id']; ?></p>
                                    <p class="text-lg font-bold text-gray-800"><?php echo sanitize_output($u['full_name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo sanitize_output($u['email']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1">Joined: <?php echo date('Y-m-d', strtotime($u['created_at'])); ?></p>
                                </div>
                                <div class="text-right space-y-2">
                                    <?php echo render_status_badge($u['role']); ?>
                                    <?php if ($u['role'] === 'ngo'): ?>
                                        <div class="mt-1"><?php echo render_status_badge($u['is_verified'] ? 'verified' : 'unverified'); ?></div>
                                        <?php if ($u['is_verified']): ?>
                                            <form method="POST" class="mt-2"><?php csrf_field(); ?><input type="hidden" name="action" value="revoke_ngo"><input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>"><button type="submit" class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded" onclick="return confirm('Revoke?')">Revoke</button></form>
                                        <?php else: ?>
                                            <form method="POST" class="mt-2"><?php csrf_field(); ?><input type="hidden" name="action" value="verify_ngo"><input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>"><button type="submit" class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs rounded">Verify</button></form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($u['role'] !== 'admin'): ?>
                                        <form method="POST" class="mt-1"><?php csrf_field(); ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>"><button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash mr-1"></i>Delete</button></form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm p-8 text-center"><p class="text-gray-500">No users.</p></div>
                <?php endif; ?>
            </div>

            <?php render_pagination($current_pg, $total_pages, 'admin_users.php'); ?>
        </main>
    </div>

    <!-- Add Note Modal -->
    <div id="noteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-1"><i class="fa-solid fa-note-sticky text-yellow-500 mr-2"></i>Add Admin Note</h3>
            <p id="noteUserLabel" class="text-sm text-gray-500 mb-4"></p>
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_admin_note">
                <input type="hidden" name="user_id" id="note_user_id">
                <textarea name="note" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none mb-4" placeholder="Internal note (only visible to admins)..."></textarea>
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-yellow-500 text-white py-2 rounded-lg font-semibold hover:bg-yellow-600 transition">Save Note</button>
                    <button type="button" onclick="document.getElementById('noteModal').classList.add('hidden')" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg font-semibold transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const mobileSidebar = document.getElementById('mobileSidebar'), overlay = document.getElementById('overlay');
    document.getElementById('menuToggle')?.addEventListener('click', () => { mobileSidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); });
    document.getElementById('closeSidebar')?.addEventListener('click', () => { mobileSidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
    overlay?.addEventListener('click', () => { mobileSidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
    
    function openNoteModal(userId, userName) {
        document.getElementById('note_user_id').value = userId;
        document.getElementById('noteUserLabel').textContent = 'For: ' + userName;
        document.getElementById('noteModal').classList.remove('hidden');
    }
    </script>
</body>
</html>

