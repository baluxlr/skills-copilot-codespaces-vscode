<?php
/**
 * Wishlist template
 */
defined('ABSPATH') || exit;

$user_id = get_current_user_id();
?>

<div class="dealroom-watchlist">
    <div class="watchlist-header">
        <h2><?php _e('My Watchlist', 'dealroom'); ?></h2>
        <div class="watchlist-filters">
            <select id="watchlist-filter">
                <option value="all"><?php _e('All Sectors', 'dealroom'); ?></option>
                <?php foreach (DealRoom_Utilities::get_sectors() as $sector) : ?>
                    <option value="<?php echo esc_attr($sector); ?>"><?php echo esc_html($sector); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="watchlist-sort">
                <option value="date_desc"><?php _e('Newest First', 'dealroom'); ?></option>
                <option value="date_asc"><?php _e('Oldest First', 'dealroom'); ?></option>
                <option value="funding_desc"><?php _e('Highest Funding', 'dealroom'); ?></option>
                <option value="funding_asc"><?php _e('Lowest Funding', 'dealroom'); ?></option>
            </select>
        </div>
    </div>

    <?php if (empty($watchlist)) : ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <h3><?php _e('Your watchlist is empty', 'dealroom'); ?></h3>
            <p><?php _e('Add deals to your watchlist to track them here.', 'dealroom'); ?></p>
            <a href="<?php echo esc_url(home_url('/deals/')); ?>" class="button button-primary"><?php _e('Explore Deals', 'dealroom'); ?></a>
        </div>
    <?php else : ?>
        <div class="watchlist-content">
            <div class="watchlist-deals">
                <?php foreach ($watchlist as $deal) : ?>
                    <div class="watchlist-deal" data-id="<?php echo esc_attr($deal['id']); ?>" data-sector="<?php echo esc_attr($deal['sector']); ?>" data-funding="<?php echo esc_attr($deal['funding_ask']); ?>" data-date="<?php echo esc_attr($deal['added_on']); ?>">
                        <div class="deal-card">
                            <div class="deal-image">
                                <?php if ($deal['thumbnail']) : ?>
                                    <img src="<?php echo esc_url($deal['thumbnail']); ?>" alt="<?php echo esc_attr($deal['title']); ?>">
                                <?php else : ?>
                                    <div class="no-image">
                                        <span class="dashicons dashicons-format-image"></span>
                                    </div>
                                <?php endif; ?>
                                <div class="deal-actions">
                                    <button class="action-button view-deal" data-id="<?php echo esc_attr($deal['id']); ?>" data-url="<?php echo esc_url($deal['permalink']); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <button class="action-button toggle-notes" data-id="<?php echo esc_attr($deal['id']); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <button class="action-button add-to-compare" data-id="<?php echo esc_attr($deal['id']); ?>" data-title="<?php echo esc_attr($deal['title']); ?>">
                                        <span class="dashicons dashicons-table-row-after"></span>
                                    </button>
                                    <button class="action-button remove-from-watchlist" data-id="<?php echo esc_attr($deal['id']); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="deal-content">
                                <h3 class="deal-title">
                                    <a href="<?php echo esc_url($deal['permalink']); ?>"><?php echo esc_html($deal['title']); ?></a>
                                </h3>
                                <div class="deal-meta">
                                    <span class="deal-company"><?php echo esc_html($deal['organization_name']); ?></span>
                                    <span class="deal-sector"><?php echo esc_html($deal['sector']); ?></span>
                                </div>
                                <div class="deal-funding">
                                    <?php if ($deal['funding_ask']) : ?>
                                        <span class="funding-amount"><?php echo DealRoom_Utilities::format_currency($deal['funding_ask']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($deal['funding_stage']) : ?>
                                        <span class="funding-stage"><?php echo esc_html($deal['funding_stage']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="deal-footer">
                                    <span class="added-on"><?php _e('Added', 'dealroom'); ?>: <?php echo DealRoom_Utilities::time_ago($deal['added_on']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="deal-notes" id="notes-<?php echo esc_attr($deal['id']); ?>" style="display: none;">
                            <textarea class="deal-notes-content" data-id="<?php echo esc_attr($deal['id']); ?>" placeholder="<?php _e('Add your notes here...', 'dealroom'); ?>"><?php echo esc_textarea($deal['notes']); ?></textarea>
                            <div class="notes-actions">
                                <button class="save-notes button-primary" data-id="<?php echo esc_attr($deal['id']); ?>"><?php _e('Save Notes', 'dealroom'); ?></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.dealroom-watchlist {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.watchlist-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.watchlist-filters {
    display: flex;
    gap: 10px;
}

.watchlist-filters select {
    min-width: 150px;
}

.empty-state {
    text-align: center;
    padding: 50px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.empty-state-icon {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 20px;
}

.watchlist-deals {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.deal-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.deal-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.deal-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.deal-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.deal-card:hover .deal-image img {
    transform: scale(1.05);
}

.deal-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.deal-card:hover .deal-actions {
    opacity: 1;
}

.action-button {
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 4px;
    background: rgba(255,255,255,0.9);
    color: #333;
    cursor: pointer;
    transition: background-color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-button:hover {
    background: white;
    color: #0073aa;
}

.deal-content {
    padding: 20px;
}

.deal-title {
    margin: 0 0 10px;
    font-size: 18px;
}

.deal-title a {
    color: inherit;
    text-decoration: none;
}

.deal-title a:hover {
    color: #0073aa;
}

.deal-meta {
    margin-bottom: 10px;
    font-size: 14px;
    color: #666;
}

.deal-sector {
    display: inline-block;
    padding: 2px 8px;
    background: #f0f0f1;
    border-radius: 4px;
    margin-left: 10px;
}

.deal-funding {
    margin-bottom: 15px;
}

.funding-amount {
    font-size: 18px;
    font-weight: 600;
    color: #0073aa;
}

.funding-stage {
    display: inline-block;
    padding: 2px 8px;
    background: #e8f5e9;
    color: #2e7d32;
    border-radius: 4px;
    margin-left: 10px;
    font-size: 14px;
}

.deal-footer {
    padding-top: 15px;
    border-top: 1px solid #eee;
    font-size: 14px;
    color: #666;
}

.deal-notes {
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.deal-notes textarea {
    width: 100%;
    min-height: 100px;
    margin-bottom: 10px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
}

.notes-actions {
    text-align: right;
}

@media screen and (max-width: 768px) {
    .watchlist-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .watchlist-filters {
        width: 100%;
    }
    
    .watchlist-filters select {
        flex: 1;
    }
}
</style>