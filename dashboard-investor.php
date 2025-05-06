<?php
/**
 * Investor dashboard template
 */
defined('ABSPATH') || exit;

$user_id = get_current_user_id();
$investor_tools = dealroom()->investor_tools;
$watchlist = $investor_tools->get_user_watchlist($user_id);
?>

<div class="dealroom-investor-dashboard">
    <div class="dashboard-header">
        <h2><?php _e('Investor Dashboard', 'dealroom'); ?></h2>
    </div>

    <div class="dashboard-grid">
        <!-- Watchlist Summary -->
        <div class="dashboard-card">
            <h3><?php _e('My Watchlist', 'dealroom'); ?></h3>
            <?php if (empty($watchlist)): ?>
                <div class="empty-state">
                    <p><?php _e('Your watchlist is empty.', 'dealroom'); ?></p>
                    <a href="<?php echo esc_url(home_url('/deals/')); ?>" class="button"><?php _e('Browse Deals', 'dealroom'); ?></a>
                </div>
            <?php else: ?>
                <div class="watchlist-preview">
                    <?php foreach (array_slice($watchlist, 0, 3) as $deal): ?>
                        <div class="watchlist-item">
                            <h4><a href="<?php echo esc_url($deal['permalink']); ?>"><?php echo esc_html($deal['title']); ?></a></h4>
                            <div class="deal-meta">
                                <span class="deal-company"><?php echo esc_html($deal['organization_name']); ?></span>
                                <span class="deal-amount"><?php echo DealRoom_Utilities::format_currency($deal['funding_ask']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-action">
                    <a href="<?php echo esc_url(home_url('/investor-tools/')); ?>" class="button"><?php _e('View All', 'dealroom'); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Investment Pipeline -->
        <div class="dashboard-card">
            <h3><?php _e('Investment Pipeline', 'dealroom'); ?></h3>
            <?php
            $investments = get_user_meta($user_id, 'dealroom_investments', true);
            if (empty($investments)):
            ?>
                <div class="empty-state">
                    <p><?php _e('No investments being tracked.', 'dealroom'); ?></p>
                </div>
            <?php else: ?>
                <div class="pipeline-summary">
                    <?php
                    $stages = array(
                        'interested' => __('Interested', 'dealroom'),
                        'researching' => __('Researching', 'dealroom'),
                        'diligence' => __('Due Diligence', 'dealroom'),
                        'negotiating' => __('Negotiating', 'dealroom'),
                        'committed' => __('Committed', 'dealroom')
                    );

                    foreach ($stages as $stage => $label):
                        $count = 0;
                        foreach ($investments as $investment) {
                            if ($investment['status'] === $stage) $count++;
                        }
                    ?>
                        <div class="pipeline-stage">
                            <span class="stage-label"><?php echo $label; ?></span>
                            <span class="stage-count"><?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-card">
            <h3><?php _e('Recent Activity', 'dealroom'); ?></h3>
            <?php
            global $wpdb;
            $activity = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dealroom_activity_log
                WHERE user_id = %d
                ORDER BY created_at DESC
                LIMIT 5",
                $user_id
            ));

            if (empty($activity)):
            ?>
                <div class="empty-state">
                    <p><?php _e('No recent activity.', 'dealroom'); ?></p>
                </div>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($activity as $item): ?>
                        <div class="activity-item">
                            <?php
                            switch ($item->action) {
                                case 'view_deal':
                                    $deal = get_post($item->object_id);
                                    echo sprintf(
                                        __('Viewed deal: %s', 'dealroom'),
                                        $deal ? '<a href="' . get_permalink($deal) . '">' . esc_html($deal->post_title) . '</a>' : __('Unknown Deal', 'dealroom')
                                    );
                                    break;
                                case 'add_to_watchlist':
                                    $deal = get_post($item->object_id);
                                    echo sprintf(
                                        __('Added %s to watchlist', 'dealroom'),
                                        $deal ? '<a href="' . get_permalink($deal) . '">' . esc_html($deal->post_title) . '</a>' : __('Unknown Deal', 'dealroom')
                                    );
                                    break;
                                default:
                                    echo esc_html($item->action);
                            }
                            ?>
                            <span class="activity-time">
                                <?php echo human_time_diff(strtotime($item->created_at), current_time('timestamp')); ?>
                                <?php _e('ago', 'dealroom'); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dealroom-investor-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
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

.dashboard-card h3 {
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 18px;
}

.empty-state {
    text-align: center;
    padding: 30px;
    color: #666;
}

.watchlist-preview {
    margin-bottom: 20px;
}

.watchlist-item {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.watchlist-item:last-child {
    border-bottom: none;
}

.watchlist-item h4 {
    margin: 0 0 5px;
    font-size: 16px;
}

.deal-meta {
    font-size: 14px;
    color: #666;
}

.deal-amount {
    float: right;
    color: #0073aa;
    font-weight: 500;
}

.pipeline-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 10px;
}

.pipeline-stage {
    text-align: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stage-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.stage-count {
    display: block;
    font-size: 20px;
    font-weight: bold;
    color: #0073aa;
}

.activity-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-time {
    display: block;
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.card-action {
    margin-top: 20px;
    text-align: center;
}
</style>