<?php
/**
 * Entrepreneur dashboard template
 */
defined('ABSPATH') || exit;

$user_id = get_current_user_id();

// Get user's deals
$deals = get_posts(array(
    'post_type' => 'deal',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => array('publish', 'pending', 'draft')
));

// Get deal stats
$total_views = 0;
$total_watchlist = 0;
$total_messages = 0;

global $wpdb;
foreach ($deals as $deal) {
    // Get views
    $views = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_activity_log
        WHERE action = 'view_deal' AND object_id = %d",
        $deal->ID
    ));
    $total_views += $views;

    // Get watchlist adds
    $watchlist = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_watchlist
        WHERE deal_id = %d",
        $deal->ID
    ));
    $total_watchlist += $watchlist;

    // Get messages
    $messages = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_messages
        WHERE deal_id = %d",
        $deal->ID
    ));
    $total_messages += $messages;
}
?>

<div class="dealroom-entrepreneur-dashboard">
    <div class="dashboard-header">
        <h2><?php _e('Entrepreneur Dashboard', 'dealroom'); ?></h2>
        <div class="header-actions">
            <a href="<?php echo esc_url(home_url('/submit-deal/')); ?>" class="button button-primary">
                <?php _e('Submit New Deal', 'dealroom'); ?>
            </a>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Stats Overview -->
        <div class="dashboard-card">
            <h3><?php _e('Overview', 'dealroom'); ?></h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-label"><?php _e('Active Deals', 'dealroom'); ?></span>
                    <span class="stat-value"><?php echo count($deals); ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label"><?php _e('Total Views', 'dealroom'); ?></span>
                    <span class="stat-value"><?php echo $total_views; ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label"><?php _e('Watchlist Adds', 'dealroom'); ?></span>
                    <span class="stat-value"><?php echo $total_watchlist; ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label"><?php _e('Messages', 'dealroom'); ?></span>
                    <span class="stat-value"><?php echo $total_messages; ?></span>
                </div>
            </div>
        </div>

        <!-- Deals List -->
        <div class="dashboard-card full-width">
            <h3><?php _e('My Deals', 'dealroom'); ?></h3>
            <?php if (empty($deals)): ?>
                <div class="empty-state">
                    <p><?php _e('You haven\'t submitted any deals yet.', 'dealroom'); ?></p>
                    <a href="<?php echo esc_url(home_url('/submit-deal/')); ?>" class="button button-primary">
                        <?php _e('Submit Your First Deal', 'dealroom'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="deals-table-wrapper">
                    <table class="deals-table">
                        <thead>
                            <tr>
                                <th><?php _e('Deal', 'dealroom'); ?></th>
                                <th><?php _e('Status', 'dealroom'); ?></th>
                                <th><?php _e('Views', 'dealroom'); ?></th>
                                <th><?php _e('Watchlist', 'dealroom'); ?></th>
                                <th><?php _e('Messages', 'dealroom'); ?></th>
                                <th><?php _e('Actions', 'dealroom'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deals as $deal): ?>
                                <?php
                                $views = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_activity_log
                                    WHERE action = 'view_deal' AND object_id = %d",
                                    $deal->ID
                                ));
                                
                                $watchlist = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_watchlist
                                    WHERE deal_id = %d",
                                    $deal->ID
                                ));
                                
                                $messages = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_messages
                                    WHERE deal_id = %d",
                                    $deal->ID
                                ));
                                ?>
                                <tr>
                                    <td>
                                        <strong><a href="<?php echo get_permalink($deal); ?>"><?php echo esc_html($deal->post_title); ?></a></strong>
                                        <div class="deal-meta">
                                            <?php echo esc_html(get_post_meta($deal->ID, 'organization_name', true)); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="deal-status status-<?php echo esc_attr($deal->post_status); ?>">
                                            <?php
                                            switch ($deal->post_status) {
                                                case 'publish':
                                                    _e('Published', 'dealroom');
                                                    break;
                                                case 'pending':
                                                    _e('Pending Review', 'dealroom');
                                                    break;
                                                case 'draft':
                                                    _e('Draft', 'dealroom');
                                                    break;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo $views; ?></td>
                                    <td><?php echo $watchlist; ?></td>
                                    <td><?php echo $messages; ?></td>
                                    <td>
                                        <div class="deal-actions">
                                            <?php if ($deal->post_status === 'draft'): ?>
                                                <a href="<?php echo get_edit_post_link($deal->ID); ?>" class="button button-small">
                                                    <?php _e('Edit', 'dealroom'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo get_permalink($deal); ?>" class="button button-small">
                                                <?php _e('View', 'dealroom'); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Messages -->
        <div class="dashboard-card">
            <h3><?php _e('Recent Messages', 'dealroom'); ?></h3>
            <?php
            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, d.post_title as deal_title
                FROM {$wpdb->prefix}dealroom_messages m
                LEFT JOIN {$wpdb->posts} d ON m.deal_id = d.ID
                WHERE m.recipient_id = %d
                ORDER BY m.created_at DESC
                LIMIT 5",
                $user_id
            ));

            if (empty($messages)):
            ?>
                <div class="empty-state">
                    <p><?php _e('No messages yet.', 'dealroom'); ?></p>
                </div>
            <?php else: ?>
                <div class="messages-list">
                    <?php foreach ($messages as $message): ?>
                        <?php
                        $sender = get_userdata($message->sender_id);
                        if (!$sender) continue;
                        ?>
                        <div class="message-item">
                            <div class="message-header">
                                <span class="message-sender"><?php echo esc_html($sender->display_name); ?></span>
                                <?php if ($message->deal_title): ?>
                                    <span class="message-deal"><?php echo esc_html($message->deal_title); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="message-preview">
                                <?php echo wp_trim_words($message->message, 10); ?>
                            </div>
                            <div class="message-time">
                                <?php echo human_time_diff(strtotime($message->created_at), current_time('timestamp')); ?>
                                <?php _e('ago', 'dealroom'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-action">
                    <a href="<?php echo esc_url(home_url('/messages/')); ?>" class="button">
                        <?php _e('View All Messages', 'dealroom'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dealroom-entrepreneur-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 20px;
}

.dashboard-card.full-width {
    grid-column: 1 / -1;
}

.dashboard-card h3 {
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 18px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.stat-box {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.empty-state {
    text-align: center;
    padding: 30px;
    color: #666;
}

.deals-table-wrapper {
    overflow-x: auto;
}

.deals-table {
    width: 100%;
    border-collapse: collapse;
}

.deals-table th,
.deals-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.deals-table th {
    background: #f8f9fa;
    font-weight: 500;
}

.deal-meta {
    font-size: 13px;
    color: #666;
    margin-top: 3px;
}

.deal-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-publish {
    background: #dcf7e3;
    color: #0a6b2d;
}

.status-pending {
    background: #fff7e6;
    color: #805d00;
}

.status-draft {
    background: #f0f0f1;
    color: #50575e;
}

.deal-actions {
    display: flex;
    gap: 8px;
}

.messages-list {
    margin: -10px;
}

.message-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.message-item:last-child {
    border-bottom: none;
}

.message-header {
    margin-bottom: 5px;
}

.message-sender {
    font-weight: 500;
}

.message-deal {
    font-size: 12px;
    color: #666;
    margin-left: 8px;
}

.message-preview {
    font-size: 14px;
    color: #444;
    margin-bottom: 5px;
}

.message-time {
    font-size: 12px;
    color: #666;
}

.card-action {
    margin-top: 20px;
    text-align: center;
}

@media screen and (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .deals-table th:nth-child(3),
    .deals-table th:nth-child(4),
    .deals-table td:nth-child(3),
    .deals-table td:nth-child(4) {
        display: none;
    }
}
</style>