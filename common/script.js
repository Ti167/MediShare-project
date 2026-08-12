// Common JavaScript for mobile sidebar toggle
const mobileSidebar = document.getElementById('mobileSidebar');
const overlay = document.getElementById('overlay');
const menuToggle = document.getElementById('menuToggle');
const closeSidebar = document.getElementById('closeSidebar');

function toggleSidebar() {
    mobileSidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

if (menuToggle) {
    menuToggle.addEventListener('click', toggleSidebar);
}
if (closeSidebar) {
    closeSidebar.addEventListener('click', toggleSidebar);
}
if (overlay) {
    overlay.addEventListener('click', toggleSidebar);
}
