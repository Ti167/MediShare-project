<?php
// Admin Sidebar — with Announcements, CSV Export, Notes
$current_admin_page = $current_admin_page ?? '';
?>
<!-- Mobile Header -->
<div class="md:hidden bg-gray-800 shadow-md sticky top-0 z-30">
    <div class="flex items-center justify-between p-4">
        <button id="menuToggle" class="text-white p-2 -ml-2"><i class="fa-solid fa-bars text-xl"></i></button>
        <div class="flex items-center text-white"><i class="fa-solid fa-pills text-blue-400 text-xl mr-2"></i><span class="text-lg font-bold">Medi-Share</span></div>
        <a href="logout.php" class="text-red-400 p-2"><i class="fa-solid fa-sign-out-alt"></i></a>
    </div>
</div>

<div class="flex">
    <!-- Desktop Sidebar -->
    <aside class="hidden md:flex md:flex-col w-64 bg-gray-800 text-white h-screen sticky top-0">
        <div class="p-4 border-b border-gray-700">
            <div class="flex items-center"><i class="fa-solid fa-pills text-blue-400 text-xl mr-2"></i><span class="text-lg font-bold">Medi-Share</span></div>
            <p class="text-xs text-gray-400 mt-1">Admin Panel</p>
        </div>
        <nav class="flex-1 p-4 overflow-y-auto">
            <a href="admin_dashboard.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'dashboard' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-chart-line w-6"></i>Dashboard</a>
            <a href="admin_users.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'users' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-users w-6"></i>Manage Users</a>
            <a href="admin_medicines.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'medicines' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-pills w-6"></i>Medicines</a>
            <a href="admin_requests.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'requests' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-hand-holding-heart w-6"></i>Requests</a>
            <a href="admin_announcements.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'announcements' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-bullhorn w-6"></i>Announcements</a>
            <a href="admin_audit.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'audit' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-clipboard-check w-6"></i>Audit Log</a>
            <div class="border-t border-gray-700 mt-3 pt-3">
                <p class="text-xs text-gray-500 uppercase tracking-wider px-4 mb-2">Export</p>
                <a href="export_csv.php?type=medicines" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg mb-1 text-sm"><i class="fa-solid fa-file-csv w-6 text-emerald-400"></i>Medicines CSV</a>
                <a href="export_csv.php?type=users" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg mb-1 text-sm"><i class="fa-solid fa-file-csv w-6 text-blue-400"></i>Users CSV</a>
                <a href="export_csv.php?type=donations" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg mb-1 text-sm"><i class="fa-solid fa-file-csv w-6 text-purple-400"></i>Donations CSV</a>
            </div>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <a href="logout.php" class="flex items-center px-4 py-2 text-red-400 hover:text-red-300"><i class="fa-solid fa-sign-out-alt w-6"></i>Logout</a>
        </div>
    </aside>

    <!-- Mobile Sidebar -->
    <div id="mobileSidebar" class="fixed inset-y-0 left-0 w-64 bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 z-40 md:hidden">
        <div class="p-4 border-b border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center"><i class="fa-solid fa-pills text-blue-400 text-xl mr-2"></i><span class="text-lg font-bold">Medi-Share</span></div>
                <button id="closeSidebar" class="text-gray-400 p-2 -mr-2"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Admin Panel</p>
        </div>
        <nav class="flex-1 p-4 overflow-y-auto">
            <a href="admin_dashboard.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'dashboard' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-chart-line w-6"></i>Dashboard</a>
            <a href="admin_users.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'users' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-users w-6"></i>Manage Users</a>
            <a href="admin_medicines.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'medicines' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-pills w-6"></i>Medicines</a>
            <a href="admin_requests.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'requests' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-hand-holding-heart w-6"></i>Requests</a>
            <a href="admin_announcements.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'announcements' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-bullhorn w-6"></i>Announcements</a>
            <a href="admin_audit.php" class="flex items-center px-4 py-3 <?php echo $current_admin_page === 'audit' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> rounded-lg mb-2"><i class="fa-solid fa-clipboard-check w-6"></i>Audit Log</a>
            <div class="border-t border-gray-700 mt-3 pt-3">
                <a href="export_csv.php?type=medicines" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg mb-1 text-sm"><i class="fa-solid fa-file-csv w-6 text-emerald-400"></i>Medicines CSV</a>
                <a href="export_csv.php?type=users" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg mb-1 text-sm"><i class="fa-solid fa-file-csv w-6 text-blue-400"></i>Users CSV</a>
                <a href="export_csv.php?type=donations" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg mb-1 text-sm"><i class="fa-solid fa-file-csv w-6 text-purple-400"></i>Donations CSV</a>
            </div>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <a href="logout.php" class="flex items-center px-4 py-2 text-red-400 hover:text-red-300"><i class="fa-solid fa-sign-out-alt w-6"></i>Logout</a>
        </div>
    </div>

    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>
