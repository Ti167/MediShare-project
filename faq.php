<?php
// FAQ / How it Works Page
require_once 'db.php';
require_once 'helpers.php';
init_secure_session();

$base_path = '.';
$role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';
$current_page = 'faq';
$pending_count = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Medi-Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php if (isset($_SESSION['user_id']) && $role !== 'admin'): ?>
        <?php include 'common/header.php'; ?>
        <div class="flex">
            <?php include 'common/sidebar.php'; ?>
            <main class="flex-1 min-h-screen p-4 md:p-8">
    <?php else: ?>
        <div class="max-w-4xl mx-auto p-4 md:p-8">
    <?php endif; ?>

                <h1 class="text-3xl font-bold text-gray-800 mb-2"><i class="fa-solid fa-circle-question text-blue-600 mr-3"></i>How Medi-Share Works</h1>
                <p class="text-gray-500 mb-8">A platform connecting medicine donors with charitable NGOs</p>

                <!-- How it works steps -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-plus-circle text-emerald-600 text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 mb-2">1. Donor Lists</h3>
                        <p class="text-sm text-gray-600">A donor registers and lists available medicines with details like name, expiry, quantity, and category.</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-hand-holding-heart text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 mb-2">2. NGO Requests</h3>
                        <p class="text-sm text-gray-600">A verified NGO browses available medicines and submits a request with an optional note explaining their need.</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-check-circle text-purple-600 text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 mb-2">3. Donor Approves</h3>
                        <p class="text-sm text-gray-600">The donor reviews the request and approves or rejects it. On approval, both get each other's contact info.</p>
                    </div>
                </div>

                <!-- FAQ Accordion -->
                <h2 class="text-2xl font-bold text-gray-800 mb-6"><i class="fa-solid fa-comments text-blue-600 mr-2"></i>Frequently Asked Questions</h2>
                <div class="space-y-3">
                    <?php
                    $faqs = [
                        ['q' => 'Who can donate medicines?', 'a' => 'Anyone can register as a donor. Simply create an account, list your unused medicines with their expiry dates, and wait for NGO requests.'],
                        ['q' => 'How does NGO verification work?', 'a' => 'After registration, NGO accounts require admin verification before they can submit medicine requests. This ensures trust and accountability on the platform.'],
                        ['q' => 'What happens when my request is approved?', 'a' => 'You\'ll receive an in-app notification and an email with the donor\'s contact details (phone, address). Coordinate directly for pickup or delivery.'],
                        ['q' => 'Can donors edit or withdraw their listings?', 'a' => 'Yes! Donors can edit medicine details or withdraw a listing anytime — unless the medicine has already been marked as donated.'],
                        ['q' => 'What is the Wishlist feature?', 'a' => 'NGOs can post what medicines they need. Donors browsing the wishlist can volunteer to fulfil a need, reversing the usual flow.'],
                        ['q' => 'Why was my request rejected?', 'a' => 'Donors may reject requests for various reasons. If a reason was provided, you\'ll see it on your requests page and in your email notification.'],
                        ['q' => 'Is my data safe?', 'a' => 'Yes. We use session hardening, CSRF protection, password hashing (bcrypt), and prepared SQL statements. Your contact info is only shared when a request is approved.'],
                        ['q' => 'How do I contact support?', 'a' => 'For support queries, please contact the admin through the platform or email us at the address provided on the platform.'],
                    ];
                    foreach ($faqs as $i => $faq): ?>
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('i:last-child').classList.toggle('rotate-180')" class="w-full flex items-center justify-between p-5 text-left">
                                <span class="font-semibold text-gray-800"><?php echo sanitize_output($faq['q']); ?></span>
                                <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200"></i>
                            </button>
                            <div class="hidden px-5 pb-5">
                                <p class="text-gray-600 text-sm leading-relaxed"><?php echo sanitize_output($faq['a']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="mt-8 text-center">
                        <a href="login.php" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition"><i class="fa-solid fa-sign-in-alt mr-2"></i>Login to Get Started</a>
                    </div>
                <?php endif; ?>

    <?php if (isset($_SESSION['user_id']) && $role !== 'admin'): ?>
            </main>
        </div>
    <?php else: ?>
        </div>
    <?php endif; ?>
    <script src="common/script.js"></script>
</body>
</html>
