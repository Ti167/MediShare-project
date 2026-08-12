<?php
// Common Mobile Header with Notification Bell, Global Search, Announcements
$notif_count = 0;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    $notif_count = get_unread_notification_count($pdo, $_SESSION['user_id']);
}
?>

<!-- Announcement Banner -->
<?php if (isset($pdo)) render_announcement_banner($pdo); ?>

<!-- Mobile Header -->
<div class="md:hidden bg-white shadow-md sticky top-0 z-30">
    <div class="flex items-center justify-between p-4">
        <button id="menuToggle" class="text-gray-600 hover:text-gray-800 p-2 -ml-2">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
        <div class="flex items-center">
            <i class="fa-solid fa-pills text-blue-600 text-xl mr-2"></i>
            <span class="text-lg font-bold text-gray-800">Medi-Share</span>
        </div>
        <div class="flex items-center space-x-1">
            <a href="<?php echo $base_path ?? '..'; ?>/common/notifications.php" class="relative text-gray-600 hover:text-blue-600 p-2">
                <i class="fa-solid fa-bell text-lg"></i>
                <?php if ($notif_count > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"><?php echo $notif_count > 9 ? '9+' : $notif_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo $base_path ?? '..'; ?>/logout.php" class="text-red-600 p-2">
                <i class="fa-solid fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
    <!-- Mobile Search -->
    <div class="px-4 pb-3">
        <div class="relative">
            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="globalSearchMobile" placeholder="Search medicines..." 
                   class="w-full pl-10 pr-4 py-2 bg-gray-100 border-0 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                   autocomplete="off">
            <div id="searchResultsMobile" class="absolute top-full left-0 right-0 bg-white rounded-lg shadow-xl mt-1 hidden z-50 max-h-64 overflow-y-auto"></div>
        </div>
    </div>
</div>

<!-- Mobile Overlay -->
<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden" onclick="toggleSidebar()"></div>

<script>
// Global Search Logic
function initSearch(inputId, resultsId, basePath) {
    const input = document.getElementById(inputId);
    const results = document.getElementById(resultsId);
    if (!input || !results) return;
    let timer;
    input.addEventListener('input', function() {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { results.classList.add('hidden'); return; }
        timer = setTimeout(() => {
            fetch(basePath + '/common/search.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (data.results.length === 0) {
                        results.innerHTML = '<div class="p-4 text-sm text-gray-500 text-center">No results found</div>';
                    } else {
                     results.innerHTML = data.results.map(r => 
                        '<a href="' + basePath + '/' + r.url + '" class="flex items-center px-4 py-3 hover:bg-gray-50 border-b border-gray-100">' +
                        '<i class="fa-solid ' + r.icon + ' ' + r.color + ' w-8"></i>' +
                        '<div><p class="text-sm font-medium text-gray-800">' + r.title + '</p>' +
                        '<p class="text-xs text-gray-500">' + r.subtitle + '</p></div></a>'
                    ).join('');
                    }
                    results.classList.remove('hidden');
                });
        }, 300);
    });
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !results.contains(e.target)) results.classList.add('hidden');
    });
}
document.addEventListener('DOMContentLoaded', function() {
    const bp = '<?php echo $base_path ?? ".."; ?>';
    initSearch('globalSearchMobile', 'searchResultsMobile', bp);
    initSearch('globalSearchDesktop', 'searchResultsDesktop', bp);
});
</script>
