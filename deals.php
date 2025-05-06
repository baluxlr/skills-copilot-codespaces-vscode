<?php
/**
 * Deals listing template
 */
defined('ABSPATH') || exit;

$sectors = DealRoom_Utilities::get_sectors();
$funding_stages = DealRoom_Utilities::get_funding_stages();
?>

<div class="dealroom-deals">
    <!-- Filters -->
    <div class="deals-filters">
        <div class="filter-group">
            <select id="sector-filter">
                <option value=""><?php _e('All Sectors', 'dealroom'); ?></option>
                <?php foreach ($sectors as $sector): ?>
                    <option value="<?php echo esc_attr($sector); ?>"><?php echo esc_html($sector); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="stage-filter">
                <option value=""><?php _e('All Stages', 'dealroom'); ?></option>
                <?php foreach ($funding_stages as $stage): ?>
                    <option value="<?php echo esc_attr($stage); ?>"><?php echo esc_html($stage); ?></option>
                <?php endforeach; ?>
            </select>

            <div class="funding-range">
                <input type="number" id="min-funding" placeholder="<?php _e('Min Funding', 'dealroom'); ?>">
                <input type="number" id="max-funding" placeholder="<?php _e('Max Funding', 'dealroom'); ?>">
            </div>
        </div>

        <div class="search-group">
            <input type="text" id="deals-search" placeholder="<?php _e('Search deals...', 'dealroom'); ?>">
        </div>
    </div>

    <!-- Deals Grid -->
    <div class="deals-grid">
        <?php if ($deals->have_posts()): ?>
            <?php while ($deals->have_posts()): $deals->the_post(); 
                $post_id = get_the_ID();
                $organization_name = get_post_meta($post_id, 'organization_name', true);
                $sector = get_post_meta($post_id, 'sector', true);
                $funding_ask = get_post_meta($post_id, 'funding_ask', true);
                $funding_stage = get_post_meta($post_id, 'funding_stage', true);
                $location = get_post_meta($post_id, 'location', true);
                
                // Check if in watchlist
                $in_watchlist = false;
                if (is_user_logged_in()) {
                    global $wpdb;
                    $in_watchlist = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_watchlist WHERE user_id = %d AND deal_id = %d",
                        get_current_user_id(),
                        $post_id
                    )) > 0;
                }
            ?>
                <div class="deal-card" data-sector="<?php echo esc_attr($sector); ?>" data-stage="<?php echo esc_attr($funding_stage); ?>" data-funding="<?php echo esc_attr($funding_ask); ?>">
                    <div class="deal-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else: ?>
                            <div class="no-image">
                                <span class="dashicons dashicons-portfolio"></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (is_user_logged_in()): ?>
                            <div class="deal-actions">
                                <button class="action-button toggle-watchlist <?php echo $in_watchlist ? 'in-watchlist' : ''; ?>" data-deal-id="<?php echo esc_attr($post_id); ?>">
                                    <span class="dashicons <?php echo $in_watchlist ? 'dashicons-heart' : 'dashicons-heart-outline'; ?>"></span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="deal-content">
                        <h3 class="deal-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <div class="deal-meta">
                            <?php if ($organization_name): ?>
                                <span class="deal-company"><?php echo esc_html($organization_name); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($location): ?>
                                <span class="deal-location"><?php echo esc_html($location); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="deal-tags">
                            <?php if ($sector): ?>
                                <span class="deal-tag sector"><?php echo esc_html($sector); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($funding_stage): ?>
                                <span class="deal-tag stage"><?php echo esc_html($funding_stage); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="deal-excerpt">
                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                        </div>

                        <div class="deal-footer">
                            <?php if ($funding_ask): ?>
                                <span class="deal-funding"><?php echo DealRoom_Utilities::format_currency($funding_ask); ?></span>
                            <?php endif; ?>
                            
                            <a href="<?php the_permalink(); ?>" class="deal-link"><?php _e('View Deal', 'dealroom'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else: ?>
            <div class="no-deals">
                <p><?php _e('No deals found matching your criteria.', 'dealroom'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($deals->max_num_pages > 1): ?>
        <div class="deals-pagination">
            <?php
            echo paginate_links(array(
                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $deals->max_num_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ));
            ?>
        </div>
    <?php endif; ?>
</div>

<style>
.dealroom-deals {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.deals-filters {
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filter-group {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.filter-group select,
.filter-group input {
    min-width: 150px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.funding-range {
    display: flex;
    gap: 10px;
}

.search-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.deals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
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

.no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
}

.no-image .dashicons {
    font-size: 48px;
    color: #ddd;
}

.deal-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.deal-card:hover .deal-actions {
    opacity: 1;
}

.action-button {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 50%;
    background: white;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.action-button:hover {
    background: #f8f9fa;
}

.action-button.in-watchlist {
    color: #dc3232;
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

.deal-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.deal-tag {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.deal-tag.sector {
    background: #e8f5e9;
    color: #2e7d32;
}

.deal-tag.stage {
    background: #e3f2fd;
    color: #1976d2;
}

.deal-excerpt {
    margin-bottom: 15px;
    font-size: 14px;
    color: #666;
    line-height: 1.5;
}

.deal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.deal-funding {
    font-size: 18px;
    font-weight: 600;
    color: #0073aa;
}

.deal-link {
    display: inline-block;
    padding: 6px 12px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.deal-link:hover {
    background: #006291;
    color: white;
}

.deals-pagination {
    text-align: center;
    margin-top: 30px;
}

.deals-pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 4px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #666;
}

.deals-pagination .page-numbers.current {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.no-deals {
    grid-column: 1 / -1;
    text-align: center;
    padding: 50px;
    background: white;
    border-radius: 8px;
    color: #666;
}

@media screen and (max-width: 768px) {
    .filter-group {
        flex-direction: column;
    }
    
    .filter-group select,
    .filter-group input {
        width: 100%;
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
                        button.addClass('in-watchlist')
                            .find('.dashicons')
                            .removeClass('dashicons-heart-outline')
                            .addClass('dashicons-heart');
                    } else {
                        button.removeClass('in-watchlist')
                            .find('.dashicons')
                            .removeClass('dashicons-heart')
                            .addClass('dashicons-heart-outline');
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

    // Handle filters
    function filterDeals() {
        const sector = $('#sector-filter').val();
        const stage = $('#stage-filter').val();
        const minFunding = $('#min-funding').val();
        const maxFunding = $('#max-funding').val();
        const search = $('#deals-search').val().toLowerCase();

        $('.deal-card').each(function() {
            const card = $(this);
            let show = true;

            // Sector filter
            if (sector && card.data('sector') !== sector) {
                show = false;
            }

            // Stage filter
            if (stage && card.data('stage') !== stage) {
                show = false;
            }

            // Funding range filter
            const funding = parseFloat(card.data('funding'));
            if (minFunding && funding < parseFloat(minFunding)) {
                show = false;
            }
            if (maxFunding && funding > parseFloat(maxFunding)) {
                show = false;
            }

            // Search filter
            if (search) {
                const content = card.text().toLowerCase();
                if (!content.includes(search)) {
                    show = false;
                }
            }

            card.toggle(show);
        });

        // Show/hide no results message
        const visibleDeals = $('.deal-card:visible').length;
        if (visibleDeals === 0) {
            if (!$('.no-deals').length) {
                $('.deals-grid').append('<div class="no-deals"><p><?php _e('No deals found matching your criteria.', 'dealroom'); ?></p></div>');
            }
        } else {
            $('.no-deals').remove();
        }
    }

    // Attach filter event handlers
    $('#sector-filter, #stage-filter').on('change', filterDeals);
    $('#min-funding, #max-funding').on('input', filterDeals);
    $('#deals-search').on('input', filterDeals);
});
</script>