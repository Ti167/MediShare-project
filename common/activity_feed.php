<?php
// Activity Feed — Shared component, renders recent anonymised activity
function render_activity_feed($pdo, $limit = 8) {
    try {
        $stmt = $pdo->query("SELECT * FROM activity_feed ORDER BY created_at DESC LIMIT {$limit}");
        $activities = $stmt->fetchAll();
    } catch (PDOException $e) {
        $activities = [];
    }
    
    $icons = [
        'listing'  => ['fa-plus-circle', 'text-emerald-500', 'bg-emerald-50'],
        'request'  => ['fa-hand-holding-heart', 'text-blue-500', 'bg-blue-50'],
        'donation' => ['fa-check-circle', 'text-purple-500', 'bg-purple-50'],
        'fulfil'   => ['fa-heart', 'text-pink-500', 'bg-pink-50'],
        'wishlist' => ['fa-star', 'text-yellow-500', 'bg-yellow-50'],
        'withdraw' => ['fa-xmark', 'text-gray-500', 'bg-gray-50'],
    ];
    ?>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fa-solid fa-rss text-blue-600 mr-2"></i>Recent Activity</h3>
        <?php if (count($activities) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($activities as $a): ?>
                    <?php $icon_info = $icons[$a['action_type']] ?? ['fa-circle', 'text-gray-500', 'bg-gray-50']; ?>
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 rounded-full <?php echo $icon_info[2]; ?> flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid <?php echo $icon_info[0]; ?> <?php echo $icon_info[1]; ?> text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-700"><?php echo sanitize_output($a['description']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo time_ago($a['created_at']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-500 text-center py-4">No activity yet. Be the first!</p>
        <?php endif; ?>
    </div>
    <?php
}
