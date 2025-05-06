<?php
/**
 * Single deal template
 */
defined('ABSPATH') || exit;

$post_id = get_the_ID();
$user_id = get_current_user_id();

// Get deal meta
$organization_name = get_post_meta($post_id, 'organization_name', true);
$sector = get_post_meta($post_id, 'sector', true);
$funding_ask = get_post_meta($post_id, 'funding_ask', true);
$funding_stage = get_post_meta($post_id, 'funding_stage', true);
$equity_offered = get_post_meta($post_id, 'equity_offered', true);
$minimum_investment = get_post_meta($post_id, 'minimum_investment', true);
$location = get_post_meta($post_id, 'location', true);
$company_description = get_post_meta($post_id, 'company_description', true);
$traction = get_post_meta($post_id, 'traction', true);
$video_url = get_post_meta($post_id, 'video_url', true);
$team_members = get_post_meta($post_id, 'team_members', true);
$milestones = get_post_meta($post_id, 'milestones', true);

// Get file attachments
$company_logo_id = get_post_meta($post_id, 'company_logo_id', true);
$pitchdeck = get_post_meta($post_id, 'pitchdeck', true);
$financial_model = get_post_meta($post_id, 'financial_model', true);
$additional_docs = get_post_meta($post_id, 'additional_docs', true);

// Check if deal is in user's watchlist
$in_watchlist = false;
if (is_user_logged_in()) {
    global $wpdb;
    $in_watchlist = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_watchlist WHERE user_id = %d AND deal_id = %d",
        $user_id,
        $post_id
    )) > 0;
}
?>

<div class="dealroom-deal-single">
    <div class="deal-header">
        <?php if (has_post_thumbnail()): ?>
            <div class="deal-featured-image">
                <?php the_post_thumbnail('full'); ?>
            </div>
        <?php endif; ?>

        <div class="deal-header-content">
            <h1 class="deal-title"><?php the_title(); ?></h1>
            
            <div class="deal-meta">
                <?php if ($organization_name): ?>
                    <span class="deal-company"><?php echo esc_html($organization_name); ?></span>
                <?php endif; ?>
                
                <?php if ($sector): ?>
                    <span class="deal-sector"><?php echo esc_html($sector); ?></span>
                <?php endif; ?>
                
                <?php if ($location): ?>
                    <span class="deal-location"><?php echo esc_html($location); ?></span>
                <?php endif; ?>
            </div>

            <?php if (is_user_logged_in()): ?>
                <div class="deal-actions">
                    <button type="button" class="toggle-watchlist <?php echo $in_watchlist ? 'in-watchlist' : ''; ?>" data-deal-id="<?php echo esc_attr($post_id); ?>">
                        <?php echo $in_watchlist ? __('Remove from Watchlist', 'dealroom') : __('Add to Watchlist', 'dealroom'); ?>
                    </button>
                    
                    <?php if (current_user_can('dealroom_contact_entrepreneurs')): ?>
                        <a href="<?php echo esc_url(home_url('/messages/?deal=' . $post_id)); ?>" class="contact-button">
                            <?php _e('Contact Entrepreneur', 'dealroom'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="deal-content">
        <div class="deal-main">
            <!-- Overview -->
            <div class="deal-section">
                <h2><?php _e('Overview', 'dealroom'); ?></h2>
                <div class="deal-overview">
                    <?php the_content(); ?>
                </div>
            </div>

            <!-- Company Description -->
            <?php if ($company_description): ?>
                <div class="deal-section">
                    <h2><?php _e('Company Description', 'dealroom'); ?></h2>
                    <div class="company-description">
                        <?php echo wp_kses_post($company_description); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Traction & Milestones -->
            <?php if ($traction || !empty($milestones)): ?>
                <div class="deal-section">
                    <h2><?php _e('Traction & Milestones', 'dealroom'); ?></h2>
                    
                    <?php if ($traction): ?>
                        <div class="traction">
                            <?php echo wp_kses_post($traction); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($milestones)): ?>
                        <div class="milestones">
                            <h3><?php _e('Key Milestones', 'dealroom'); ?></h3>
                            <ul class="milestone-list">
                                <?php foreach ($milestones as $milestone): ?>
                                    <li>
                                        <span class="milestone-description"><?php echo esc_html($milestone['description']); ?></span>
                                        <?php if (!empty($milestone['amount'])): ?>
                                            <span class="milestone-amount"><?php echo DealRoom_Utilities::format_currency($milestone['amount']); ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Team -->
            <?php if (!empty($team_members)): ?>
                <div class="deal-section">
                    <h2><?php _e('Team', 'dealroom'); ?></h2>
                    <div class="team-members">
                        <?php foreach ($team_members as $member): ?>
                            <div class="team-member">
                                <h4 class="member-name"><?php echo esc_html($member['name']); ?></h4>
                                <?php if (!empty($member['title'])): ?>
                                    <div class="member-title"><?php echo esc_html($member['title']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="deal-sidebar">
            <!-- Investment Details -->
            <div class="sidebar-box">
                <h3><?php _e('Investment Details', 'dealroom'); ?></h3>
                <div class="investment-details">
                    <?php if ($funding_ask): ?>
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Funding Ask', 'dealroom'); ?></span>
                            <span class="detail-value"><?php echo DealRoom_Utilities::format_currency($funding_ask); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($funding_stage): ?>
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Stage', 'dealroom'); ?></span>
                            <span class="detail-value"><?php echo esc_html($funding_stage); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($equity_offered): ?>
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Equity Offered', 'dealroom'); ?></span>
                            <span class="detail-value"><?php echo esc_html($equity_offered); ?>%</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($minimum_investment): ?>
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Minimum Investment', 'dealroom'); ?></span>
                            <span class="detail-value"><?php echo DealRoom_Utilities::format_currency($minimum_investment); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Documents -->
            <?php if (is_user_logged_in() && (current_user_can('dealroom_view_deals') || get_current_user_id() === $post->post_author)): ?>
                <div class="sidebar-box">
                    <h3><?php _e('Documents', 'dealroom'); ?></h3>
                    <div class="deal-documents">
                        <?php if ($pitchdeck): ?>
                            <a href="<?php echo esc_url($pitchdeck); ?>" target="_blank" class="document-link">
                                <span class="dashicons dashicons-media-document"></span>
                                <?php _e('View Pitch Deck', 'dealroom'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($financial_model): ?>
                            <a href="<?php echo esc_url($financial_model); ?>" target="_blank" class="document-link">
                                <span class="dashicons dashicons-media-spreadsheet"></span>
                                <?php _e('View Financial Model', 'dealroom'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($additional_docs): ?>
                            <a href="<?php echo esc_url($additional_docs); ?>" target="_blank" class="document-link">
                                <span class="dashicons dashicons-media-default"></span>
                                <?php _e('View Additional Documents', 'dealroom'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Video -->
            <?php if ($video_url): ?>
                <div class="sidebar-box">
                    <h3><?php _e('Video', 'dealroom'); ?></h3>
                    <div class="deal-video">
                        <?php
                        // Extract video ID from URL
                        $video_id = '';
                        if (strpos($video_url, 'youtube.com') !== false) {
                            parse_str(parse_url($video_url, PHP_URL_QUERY), $params);
                            $video_id = isset($params['v']) ? $params['v'] : '';
                        } elseif (strpos($video_url, 'youtu.be') !== false) {
                            $video_id = basename(parse_url($video_url, PHP_URL_PATH));
                        }
                        
                        if ($video_id):
                        ?>
                            <div class="video-wrapper">
                                <iframe width="100%" height="200" src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" frameborder="0" allowfullscreen></iframe>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo esc_url($video_url); ?>" target="_blank" class="video-link">
                                <?php _e('Watch Video', 'dealroom'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dealroom-deal-single {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.deal-header {
    margin-bottom: 30px;
}

.deal-featured-image {
    margin-bottom: 20px;
}

.deal-featured-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.deal-title {
    margin: 0 0 15px;
    font-size: 32px;
}

.deal-meta {
    margin-bottom: 20px;
}

.deal-meta > span {
    display: inline-block;
    margin-right: 15px;
    padding: 5px 10px;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 14px;
}

.deal-actions {
    display: flex;
    gap: 10px;
}

.deal-actions button,
.deal-actions a {
    padding: 8px 16px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
}

.toggle-watchlist {
    background: #0073aa;
    color: white;
}

.toggle-watchlist.in-watchlist {
    background: #dc3232;
}

.contact-button {
    background: #46b450;
    color: white;
}

.deal-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.deal-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.deal-section h2 {
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 24px;
}

.milestone-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.milestone-list li {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.milestone-list li:last-child {
    border-bottom: none;
}

.team-members {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.team-member {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
}

.member-name {
    margin: 0 0 5px;
    font-size: 16px;
}

.member-title {
    color: #666;
    font-size: 14px;
}

.sidebar-box {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.sidebar-box h3 {
    margin: 0 0 15px;
    font-size: 18px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    color: #666;
}

.detail-value {
    font-weight: 500;
}

.deal-documents {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.document-link {
    display: flex;
    align-items: center;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
    text-decoration: none;
    color: inherit;
}

.document-link .dashicons {
    margin-right: 8px;
    color: #0073aa;
}

.video-wrapper {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
}

.video-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

@media screen and (max-width: 768px) {
    .deal-content {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle watchlist toggle
    $('.toggle-watchlist').on('click', function() {
        const button = $(this);
        const dealId = button.data('deal-id');
        
        $.ajax({
            url: dealroom.ajaxurl,
            type: 'POST',
            data: {
                action: 'dealroom_toggle_watchlist',
                nonce: dealroom.nonce,
                deal_id: dealId
            },
            beforeSend: function() {
                button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.in_watchlist) {
                        button.addClass('in-watchlist').text(dealroomInvestor.i18n.removeFromWatchlist);
                    } else {
                        button.removeClass('in-watchlist').text(dealroomInvestor.i18n.addToWatchlist);
                    }
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(dealroomInvestor.i18n.error);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
</script>