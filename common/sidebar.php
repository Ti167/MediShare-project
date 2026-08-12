<?php
// Common Sidebar — Donor/NGO panels with Search, Wishlist, Dark Mode
$current_page = $current_page ?? '';
$pending_count = $pending_count ?? 0;
$user_name = $user_name ?? '';
$role = $role ?? '';
$base_path = $base_path ?? '..';

$notif_count_sidebar = 0;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    $notif_count_sidebar = get_unread_notification_count($pdo, $_SESSION['user_id']);
}
?>

<!-- Desktop Sidebar -->
<aside class="hidden md:flex md:flex-col w-64 bg-white shadow-lg h-screen sticky top-0">
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center">
            <i class="fa-solid fa-pills text-blue-600 text-xl mr-2"></i>
            <span class="text-lg font-bold text-gray-800">Medi-Share</span>
        </div>
        <p class="text-xs <?php echo $role === 'donor' ? 'text-emerald-600' : 'text-blue-600'; ?> mt-1 font-medium">
            <?php echo $role === 'donor' ? 'Donor Panel' : 'NGO Panel'; ?>
        </p>
        <!-- Desktop Search -->

    </div>
    <nav class="flex-1 p-4 overflow-y-auto">
        <?php if ($role === 'donor'): ?>
            <a href="<?php echo $base_path; ?>/donor/index.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'dashboard' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-pills w-6"></i>My Medicines
            </a>
            <a href="<?php echo $base_path; ?>/donor/add.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'add' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-plus-circle w-6"></i>Add Medicine
            </a>
            <a href="<?php echo $base_path; ?>/donor/requests.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'requests' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-hand-holding-heart w-6"></i>Requests
                <?php if ($pending_count > 0): ?>
                    <span
                        class="ml-auto bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo $base_path; ?>/donor/history.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'history' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-clock-rotate-left w-6"></i>History
            </a>
            <a href="<?php echo $base_path; ?>/ngo/wishlist.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'wishlist' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-heart w-6 text-pink-500"></i>NGO Wishlist
            </a>
        <?php else: ?>
            <a href="<?php echo $base_path; ?>/ngo/index.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'dashboard' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-search w-6"></i>Find Medicines
            </a>
            <a href="<?php echo $base_path; ?>/ngo/wishlist.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'wishlist' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-heart w-6 text-pink-500"></i>My Wishlist
            </a>
            <a href="<?php echo $base_path; ?>/ngo/requests.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'requests' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-clipboard-list w-6"></i>My Requests
                <?php if ($pending_count > 0): ?>
                    <span
                        class="ml-auto bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <div class="border-t border-gray-200 mt-3 pt-3">
            <a href="<?php echo $base_path; ?>/common/notifications.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'notifications' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-bell w-6"></i>Notifications
                <?php if ($notif_count_sidebar > 0): ?>
                    <span
                        class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?php echo $notif_count_sidebar > 9 ? '9+' : $notif_count_sidebar; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo $base_path; ?>/<?php echo $role; ?>/profile.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'profile' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-user-gear w-6"></i>My Profile
            </a>
            <a href="<?php echo $base_path; ?>/faq.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'faq' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2">
                <i class="fa-solid fa-circle-question w-6"></i>FAQ
            </a>
        </div>
    </nav>
    <div class="p-4 border-t border-gray-200">
        <div class="flex items-center mb-3">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fa-solid <?php echo $role === 'donor' ? 'fa-user' : 'fa-building-ngo'; ?> text-blue-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-800"><?php echo sanitize_output($user_name); ?></p>
                <p class="text-xs text-gray-500"><?php echo ucfirst($role); ?></p>
            </div>
        </div>
        <a href="<?php echo $base_path; ?>/logout.php"
            class="flex items-center px-4 py-2 text-red-600 hover:text-red-800">
            <i class="fa-solid fa-sign-out-alt w-6"></i>Logout
        </a>
    </div>
</aside>

<!-- Mobile Sidebar (same structure) -->
<div id="mobileSidebar"
    class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full transition-transform duration-300 z-40 md:hidden">
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fa-solid fa-pills text-blue-600 text-xl mr-2"></i>
                <span class="text-lg font-bold text-gray-800">Medi-Share</span>
            </div>
            <button id="closeSidebar" class="text-gray-500 hover:text-gray-700 p-2 -mr-2"><i
                    class="fa-solid fa-times text-xl"></i></button>
        </div>
    </div>
    <nav class="flex-1 p-4 overflow-y-auto">
        <?php if ($role === 'donor'): ?>
            <a href="<?php echo $base_path; ?>/donor/index.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'dashboard' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2"><i
                    class="fa-solid fa-pills w-6"></i>My Medicines</a>
            <a href="<?php echo $base_path; ?>/donor/add.php"
                class="flex items-center px-4 py-3 <?php echo $current_page === 'add' ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-50'; ?> rounded-lg mb-2"><i
                    class="fa-solid fa-plus-circle w-6"></i>Add Medicine</a>
            <a href="<?php echo $base_path; ?>/donor/requests.php" class="flex items-center px-4 py-3 rounded-lg mb-2"><i
                    class="fa-solid fa-hand-holding-heart w-6"></i>Requests</a>
            <a href="<?php echo $base_path; ?>/donor/history.php" class="flex items-center px-4 py-3 rounded-lg mb-2"><i
                    class="fa-solid fa-clock-rotate-left w-6"></i>History</a>
            <a href="<?php echo $base_path; ?>/ngo/wishlist.php" class="flex items-center px-4 py-3 rounded-lg mb-2"><i
                    class="fa-solid fa-heart w-6 text-pink-500"></i>NGO Wishlist</a>
        <?php else: ?>
            <a href="<?php echo $base_path; ?>/ngo/index.php" class="flex items-center px-4 py-3 rounded-lg mb-2"><i
                    class="fa-solid fa-search w-6"></i>Find Medicines</a>
            <a href="<?php echo $base_path; ?>/ngo/wishlist.php" class="flex items-center px-4 py-3 rounded-lg mb-2"><i
                    class="fa-solid fa-heart w-6 text-pink-500"></i>My Wishlist</a>
            <a href="<?php echo $base_path; ?>/ngo/requests.php" class="flex items-center px-4 py-3 rounded-lg mb-2"><i
                    class="fa-solid fa-clipboard-list w-6"></i>My Requests</a>
        <?php endif; ?>
        <div class="border-t border-gray-200 mt-3 pt-3">
            <a href="<?php echo $base_path; ?>/common/notifications.php"
                class="flex items-center px-4 py-3 rounded-lg mb-2"><i
                    class="fa-solid fa-bell w-6"></i>Notifications</a>
            <a href="<?php echo $base_path; ?>/<?php echo $role; ?>/profile.php"
                class="flex items-center px-4 py-3 rounded-lg mb-2"><i class="fa-solid fa-user-gear w-6"></i>My
                Profile</a>
            <a href="<?php echo $base_path; ?>/faq.php" class="flex items-center px-4 py-3 rounded-lg mb-2"><i
                    class="fa-solid fa-circle-question w-6"></i>FAQ</a>
        </div>
    </nav>
    <div class="p-4 border-t border-gray-200">
        <a href="<?php echo $base_path; ?>/logout.php" class="flex items-center px-4 py-2 text-red-600"><i
                class="fa-solid fa-sign-out-alt w-6"></i>Logout</a>
    </div>
</div>