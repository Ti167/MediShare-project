<?php
// ============================================
// Medi-Share — Shared Helper Functions V2
// Security, UI, Notifications, Audit, Mailer, Dark Mode, Activity Feed
// ============================================

// ────────────────────────────────────────────
// XSS Protection
// ────────────────────────────────────────────
function sanitize_output($data) {
    if ($data === null) return '';
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

// ────────────────────────────────────────────
// CSRF Protection
// ────────────────────────────────────────────
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . sanitize_output($token) . '">';
}

function verify_csrf_token() {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (empty($submitted) || empty($stored)) return false;
    return hash_equals($stored, $submitted);
}

// ────────────────────────────────────────────
// Session Hardening & Auth
// ────────────────────────────────────────────
function init_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
    $timeout = 1800;
    if (isset($_SESSION['last_active'])) {
        if (time() - $_SESSION['last_active'] > $timeout) {
            $_SESSION = array();
            session_destroy();
            session_start();
            $_SESSION['error'] = 'Session expired. Please log in again.';
            return false;
        }
    }
    $_SESSION['last_active'] = time();
    return true;
}

function require_role($required_role) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== $required_role) {
        header("Location: ../login.php");
        exit;
    }
}

// ────────────────────────────────────────────
// Flash Messages
// ────────────────────────────────────────────
function display_flash_message() {
    if (!isset($_SESSION)) return;
    $success = $_SESSION['success'] ?? '';
    $error = $_SESSION['error'] ?? '';
    if (isset($_SESSION['success'])) unset($_SESSION['success']);
    if (isset($_SESSION['error'])) unset($_SESSION['error']);
    if (empty($success) && empty($error)) return;
    
    if (!empty($success)): ?>
        <div class="fixed top-4 right-4 z-50 animate-slide-in">
            <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 px-6 py-4 rounded-lg shadow-lg flex items-center max-w-md">
                <i class="fa-solid fa-check-circle text-xl mr-3"></i>
                <span class="font-medium"><?php echo sanitize_output($success); ?></span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-times"></i></button>
            </div>
        </div>
    <?php endif;
    if (!empty($error)): ?>
        <div class="fixed top-4 right-4 z-50 animate-slide-in">
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-lg flex items-center max-w-md">
                <i class="fa-solid fa-exclamation-circle text-xl mr-3"></i>
                <span class="font-medium"><?php echo sanitize_output($error); ?></span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-red-500 hover:text-red-700"><i class="fa-solid fa-times"></i></button>
            </div>
        </div>
    <?php endif;
}

// ────────────────────────────────────────────
// Status Badges
// ────────────────────────────────────────────
function render_status_badge($status) {
    $status_lower = strtolower(trim($status));
    $colors = [
        'available'  => 'bg-emerald-100 text-emerald-700',
        'requested'  => 'bg-blue-100 text-blue-700',
        'donated'    => 'bg-gray-200 text-gray-600',
        'pending'    => 'bg-amber-100 text-amber-700',
        'approved'   => 'bg-emerald-100 text-emerald-700',
        'rejected'   => 'bg-red-100 text-red-700',
        'verified'   => 'bg-emerald-100 text-emerald-700',
        'unverified' => 'bg-amber-100 text-amber-700',
        'donor'      => 'bg-green-100 text-green-700',
        'ngo'        => 'bg-purple-100 text-purple-700',
        'admin'      => 'bg-gray-200 text-gray-700',
        'open'       => 'bg-blue-100 text-blue-700',
        'fulfilled'  => 'bg-emerald-100 text-emerald-700',
        'withdrawn'  => 'bg-gray-200 text-gray-500',
    ];
    $class = $colors[$status_lower] ?? 'bg-gray-100 text-gray-600';
    $label = ucfirst(str_replace('_', ' ', $status_lower));
    return '<span class="px-2 py-1 ' . $class . ' text-xs font-semibold rounded-full">' . sanitize_output($label) . '</span>';
}

// ────────────────────────────────────────────
// Medicine Thumbnail
// ────────────────────────────────────────────
function render_medicine_thumbnail($image_path, $medicine_name, $base_path = '..') {
    if (!empty($image_path) && file_exists(__DIR__ . '/uploads/' . $image_path)) {
        return '<img src="' . $base_path . '/uploads/' . sanitize_output($image_path) . '" alt="' . sanitize_output($medicine_name) . '" class="w-12 h-12 rounded-lg object-cover border border-gray-200">';
    }
    $initial = strtoupper(substr($medicine_name, 0, 1));
    $colors = ['bg-blue-100 text-blue-600', 'bg-emerald-100 text-emerald-600', 'bg-purple-100 text-purple-600', 'bg-amber-100 text-amber-600', 'bg-rose-100 text-rose-600'];
    $color = $colors[ord($initial) % count($colors)];
    return '<div class="w-12 h-12 rounded-lg ' . $color . ' flex items-center justify-center font-bold text-lg">' . sanitize_output($initial) . '</div>';
}

/**
 * Get full URL for a medicine image (for use in emails)
 */
function get_medicine_image_url($image_path) {
    if (empty($image_path)) return '';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host . '/Medi-Share/uploads/' . $image_path;
}

// ────────────────────────────────────────────
// Expiry Color Coding
// ────────────────────────────────────────────
function get_expiry_coding($expiry_date) {
    $now = new DateTime('today');
    $expiry = new DateTime($expiry_date);
    $diff = $now->diff($expiry);
    $days = (int)$diff->format('%r%a');
    if ($days < 0) {
        return ['class' => 'text-red-700 font-bold', 'label' => 'Expired', 'row_class' => 'bg-red-50', 'badge' => 'bg-red-100 text-red-700'];
    } elseif ($days < 7) {
        return ['class' => 'text-red-600 font-semibold', 'label' => $days . 'd left', 'row_class' => 'bg-red-50', 'badge' => 'bg-red-100 text-red-700'];
    } elseif ($days <= 30) {
        return ['class' => 'text-amber-600 font-semibold', 'label' => $days . 'd left', 'row_class' => 'bg-amber-50', 'badge' => 'bg-amber-100 text-amber-700'];
    }
    return ['class' => 'text-gray-600', 'label' => '', 'row_class' => '', 'badge' => ''];
}

// ────────────────────────────────────────────
// Notification System
// ────────────────────────────────────────────
function add_notification($pdo, $user_id, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $message]);
    } catch (PDOException $e) {}
}

function get_unread_notification_count($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    } catch (PDOException $e) { return 0; }
}

function time_ago($datetime) {
    $timezone = new DateTimeZone('Asia/Kolkata');
    $now = new DateTime('now', $timezone);
    $then = new DateTime($datetime, $timezone);
    $diff = $now->diff($then);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// ────────────────────────────────────────────
// Audit Log
// ────────────────────────────────────────────
function audit_log($pdo, $user_id, $action, $details) {
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $details]);
    } catch (PDOException $e) {}
}

// ────────────────────────────────────────────
// Activity Feed
// ────────────────────────────────────────────
function add_activity($pdo, $action_type, $description) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_feed (action_type, description) VALUES (?, ?)");
        $stmt->execute([$action_type, $description]);
    } catch (PDOException $e) {}
}

// ────────────────────────────────────────────
// Announcements
// ────────────────────────────────────────────
function get_active_announcement($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
        return $stmt->fetch();
    } catch (PDOException $e) { return null; }
}

/**
 * Render dismissible announcement banner
 */
function render_announcement_banner($pdo) {
    $ann = get_active_announcement($pdo);
    if (!$ann) return;
    // Check if user dismissed this specific announcement
    $dismissed_id = $_SESSION['dismissed_announcement'] ?? 0;
    if ((int)$dismissed_id === (int)$ann['id']) return;
    ?>
    <div id="announcementBanner" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-4 py-3 relative">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-bullhorn text-yellow-300 text-lg"></i>
                <div>
                    <span class="font-bold"><?php echo sanitize_output($ann['title']); ?></span>
                    <span class="ml-2 text-sm opacity-90"><?php echo sanitize_output($ann['message']); ?></span>
                </div>
            </div>
            <button onclick="dismissAnnouncement(<?php echo (int)$ann['id']; ?>)" class="text-white hover:text-yellow-300 ml-4 flex-shrink-0">
                <i class="fa-solid fa-times text-lg"></i>
            </button>
        </div>
    </div>
    <script>
    function dismissAnnouncement(id) {
        document.getElementById('announcementBanner').remove();
        fetch('<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '' : '../'; ?>common/dismiss_announcement.php?id=' + id);
    }
    </script>
    <?php
}

// ────────────────────────────────────────────
// Email (PHPMailer Wrapper — HTML support + image)
// ────────────────────────────────────────────
function send_email($to_email, $to_name, $subject, $body, $image_path = null) {
    $autoload_path = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload_path)) return false;
    require_once $autoload_path;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'trhptl7@gmail.com';
        $mail->Password = 'lgsq ujij ejra ysho';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0; // Disable verbose debug output
        $mail->Timeout = 10; // Add timeout for slow connections
        $mail->setFrom('trhptl7@gmail.com', 'Medi-Share');
        $mail->addAddress($to_email, $to_name);
        
        // Build HTML email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Convert plain text body to HTML and add image if available
        $html_body = build_email_html($subject, $body, $image_path);
        $mail->Body = $html_body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log email failure to audit log if available
        global $pdo;
        if (isset($pdo) && function_exists('audit_log')) {
            audit_log($pdo, 0, 'email_failed', "Failed to send email to: {$to_email} — Error: {$e->getMessage()}");
        }
        return false;
    }
}

/**
 * Build a styled HTML email template
 */
function build_email_html($subject, $text_body, $image_path = null) {
    // Convert newlines to <br>
    $html_content = nl2br(sanitize_output($text_body));
    
    $image_html = '';
    if (!empty($image_path)) {
        $image_url = get_medicine_image_url($image_path);
        if ($image_url) {
            $image_html = '<div style="text-align:center;margin:20px 0;">
                <img src="' . $image_url . '" alt="Medicine" style="max-width:200px;max-height:200px;border-radius:12px;border:2px solid #e5e7eb;object-fit:cover;">
                <p style="font-size:12px;color:#6b7280;margin-top:8px;">Medicine Preview</p>
            </div>';
        }
    }
    
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
<div style="max-width:600px;margin:30px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <div style="background:linear-gradient(135deg,#2563eb,#7c3aed);padding:24px 30px;text-align:center;">
        <h1 style="color:white;margin:0;font-size:22px;">💊 Medi-Share</h1>
        <p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:13px;">' . sanitize_output($subject) . '</p>
    </div>
    ' . $image_html . '
    <div style="padding:30px;line-height:1.7;color:#374151;font-size:15px;">
        ' . $html_content . '
    </div>
    <div style="background:#f9fafb;padding:16px 30px;text-align:center;border-top:1px solid #e5e7eb;">
        <p style="margin:0;font-size:12px;color:#9ca3af;">© ' . date('Y') . ' Medi-Share — Connecting Medicines with those in Need</p>
    </div>
</div>
</body></html>';
}

// ────────────────────────────────────────────
// Pagination Helper
// ────────────────────────────────────────────
function render_pagination($current_page, $total_pages, $base_url, $page_param = 'page') {
    if ($total_pages <= 1) return;
    $sep = (strpos($base_url, '?') !== false) ? '&' : '?';
    echo '<div class="flex items-center justify-center space-x-2 mt-6">';
    if ($current_page > 1) {
        echo '<a href="' . $base_url . $sep . $page_param . '=' . ($current_page - 1) . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm"><i class="fa-solid fa-chevron-left mr-1"></i>Prev</a>';
    }
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    if ($start > 1) {
        echo '<a href="' . $base_url . $sep . $page_param . '=1" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm">1</a>';
        if ($start > 2) echo '<span class="px-2 text-gray-400">...</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            echo '<span class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">' . $i . '</span>';
        } else {
            echo '<a href="' . $base_url . $sep . $page_param . '=' . $i . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm">' . $i . '</a>';
        }
    }
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) echo '<span class="px-2 text-gray-400">...</span>';
        echo '<a href="' . $base_url . $sep . $page_param . '=' . $total_pages . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm">' . $total_pages . '</a>';
    }
    if ($current_page < $total_pages) {
        echo '<a href="' . $base_url . $sep . $page_param . '=' . ($current_page + 1) . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm">Next<i class="fa-solid fa-chevron-right ml-1"></i></a>';
    }
    echo '</div>';
}


