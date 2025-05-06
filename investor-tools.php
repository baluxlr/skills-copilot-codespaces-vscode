<?php
/**
 * Investor tools template
 */
defined('ABSPATH') || exit;

$user_id = get_current_user_id();
$investor = new DealRoom_Investor_Tools();
$watchlist = $investor->get_user_watchlist($user_id);
?>

<div class="dealroom-investor-dashboard">
    <!-- Tabs Navigation -->
    <div class="dealroom-tabs">
        <button class="tab-button active" data-tab="watchlist"><?php _e('My Watchlist', 'dealroom-extension'); ?></button>
        <button class="tab-button" data-tab="compare"><?php _e('Compare Deals', 'dealroom-extension'); ?></button>
        <button class="tab-button" data-tab="investment-tracker"><?php _e('Investment Tracker', 'dealroom-extension'); ?></button>
    </div>
    
    <!-- Tab Content -->
    <div class="dealroom-tab-content">
        <!-- Watchlist Tab -->
        <div class="tab-pane active" id="watchlist">
            <div class="section-header">
                <h2><?php _e('My Watchlist', 'dealroom-extension'); ?></h2>
                <div class="section-actions">
                    <select id="watchlist-filter">
                        <option value="all"><?php _e('All Sectors', 'dealroom-extension'); ?></option>
                        <?php foreach (DealRoom_Utilities::get_sectors() as $sector) : ?>
                            <option value="<?php echo esc_attr($sector); ?>"><?php echo esc_html($sector); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="watchlist-sort">
                        <option value="date_desc"><?php _e('Newest First', 'dealroom-extension'); ?></option>
                        <option value="date_asc"><?php _e('Oldest First', 'dealroom-extension'); ?></option>
                        <option value="funding_desc"><?php _e('Highest Funding', 'dealroom-extension'); ?></option>
                        <option value="funding_asc"><?php _e('Lowest Funding', 'dealroom-extension'); ?></option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($watchlist)) : ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <span class="dashicons dashicons-heart"></span>
                    </div>
                    <h3><?php _e('Your watchlist is empty', 'dealroom-extension'); ?></h3>
                    <p><?php _e('Add deals to your watchlist to track them here.', 'dealroom-extension'); ?></p>
                    <a href="<?php echo esc_url(home_url('/deals/')); ?>" class="button button-primary"><?php _e('Explore Deals', 'dealroom-extension'); ?></a>
                </div>
            <?php else : ?>
                <div class="watchlist-content">
                    <div class="watchlist-deals">
                        <?php foreach ($watchlist as $deal) : ?>
                            <div class="watchlist-deal" data-sector="<?php echo esc_attr($deal['sector']); ?>" data-funding="<?php echo esc_attr($deal['funding_ask']); ?>" data-date="<?php echo esc_attr($deal['added_on']); ?>">
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
                                            <span class="added-on"><?php _e('Added', 'dealroom-extension'); ?>: <?php echo DealRoom_Utilities::time_ago($deal['added_on']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="deal-notes" id="notes-<?php echo esc_attr($deal['id']); ?>" style="display: none;">
                                    <textarea class="deal-notes-content" data-id="<?php echo esc_attr($deal['id']); ?>" placeholder="<?php _e('Add your notes here...', 'dealroom-extension'); ?>"><?php echo esc_textarea($deal['notes']); ?></textarea>
                                    <div class="notes-actions">
                                        <button class="save-notes button-primary" data-id="<?php echo esc_attr($deal['id']); ?>"><?php _e('Save Notes', 'dealroom-extension'); ?></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Compare Deals Tab -->
        <div class="tab-pane" id="compare">
            <div class="section-header">
                <h2><?php _e('Compare Deals', 'dealroom-extension'); ?></h2>
            </div>
            
            <div class="comparison-setup">
                <div class="comparison-instructions">
                    <p><?php _e('Select deals from your watchlist to compare them side by side.', 'dealroom-extension'); ?></p>
                </div>
                
                <div class="comparison-selection">
                    <div class="selected-deals">
                        <h3><?php _e('Selected Deals', 'dealroom-extension'); ?></h3>
                        <ul id="selected-deals-list">
                            <li class="empty-selection"><?php _e('No deals selected. Add deals from your watchlist.', 'dealroom-extension'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="comparison-actions">
                        <button id="start-comparison" class="button button-primary" disabled><?php _e('Compare Deals', 'dealroom-extension'); ?></button>
                        <button id="clear-selection" class="button button-secondary" disabled><?php _e('Clear Selection', 'dealroom-extension'); ?></button>
                    </div>
                </div>
            </div>
            
            <div id="comparison-results"></div>
        </div>
        
        <!-- Investment Tracker Tab -->
        <div class="tab-pane" id="investment-tracker">
            <div class="section-header">
                <h2><?php _e('Investment Tracker', 'dealroom-extension'); ?></h2>
            </div>
            
            <div class="investment-tracking">
                <div class="tracking-status">
                    <div class="status-card">
                        <h3><?php _e('Actively Tracking', 'dealroom-extension'); ?></h3>
                        <div class="status-value" id="active-tracking-count">0</div>
                    </div>
                    
                    <div class="status-card">
                        <h3><?php _e('Total Potential', 'dealroom-extension'); ?></h3>
                        <div class="status-value" id="total-potential">$0</div>
                    </div>
                    
                    <div class="status-card">
                        <h3><?php _e('Committed', 'dealroom-extension'); ?></h3>
                        <div class="status-value" id="committed-amount">$0</div>
                    </div>
                </div>
                
                <div class="investment-pipeline">
                    <h3><?php _e('Investment Pipeline', 'dealroom-extension'); ?></h3>
                    
                    <div class="pipeline-stages">
                        <div class="pipeline-stage" id="stage-interested">
                            <div class="stage-header">
                                <h4><?php _e('Interested', 'dealroom-extension'); ?></h4>
                                <span class="stage-count">0</span>
                            </div>
                            <div class="stage-deals"></div>
                        </div>
                        
                        <div class="pipeline-stage" id="stage-researching">
                            <div class="stage-header">
                                <h4><?php _e('Researching', 'dealroom-extension'); ?></h4>
                                <span class="stage-count">0</span>
                            </div>
                            <div class="stage-deals"></div>
                        </div>
                        
                        <div class="pipeline-stage" id="stage-diligence">
                            <div class="stage-header">
                                <h4><?php _e('Due Diligence', 'dealroom-extension'); ?></h4>
                                <span class="stage-count">0</span>
                            </div>
                            <div class="stage-deals"></div>
                        </div>
                        
                        <div class="pipeline-stage" id="stage-negotiating">
                            <div class="stage-header">
                                <h4><?php _e('Negotiating', 'dealroom-extension'); ?></h4>
                                <span class="stage-count">0</span>
                            </div>
                            <div class="stage-deals"></div>
                        </div>
                        
                        <div class="pipeline-stage" id="stage-committed">
                            <div class="stage-header">
                                <h4><?php _e('Committed', 'dealroom-extension'); ?></h4>
                                <span class="stage-count">0</span>
                            </div>
                            <div class="stage-deals"></div>
                        </div>
                        
                        <div class="pipeline-stage" id="stage-passed">
                            <div class="stage-header">
                                <h4><?php _e('Passed', 'dealroom-extension'); ?></h4>
                                <span class="stage-count">0</span>
                            </div>
                            <div class="stage-deals"></div>
                        </div>
                    </div>
                </div>
                
                <div id="investment-tracking-form" class="investment-form" style="display: none;">
                    <h3 id="tracking-form-title"><?php _e('Track Investment', 'dealroom-extension'); ?></h3>
                    <form id="track-investment-form">
                        <input type="hidden" id="tracking-deal-id" name="deal_id" value="">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="investment-status"><?php _e('Status', 'dealroom-extension'); ?></label>
                                <select id="investment-status" name="status">
                                    <option value="interested"><?php _e('Interested', 'dealroom-extension'); ?></option>
                                    <option value="researching"><?php _e('Researching', 'dealroom-extension'); ?></option>
                                    <option value="diligence"><?php _e('Due Diligence', 'dealroom-extension'); ?></option>
                                    <option value="negotiating"><?php _e('Negotiating', 'dealroom-extension'); ?></option>
                                    <option value="committed"><?php _e('Committed', 'dealroom-extension'); ?></option>
                                    <option value="passed"><?php _e('Passed', 'dealroom-extension'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="investment-amount"><?php _e('Potential Investment Amount', 'dealroom-extension'); ?></label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">$</span>
                                    <input type="number" id="investment-amount" name="amount" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="investment-notes"><?php _e('Notes', 'dealroom-extension'); ?></label>
                                <textarea id="investment-notes" name="notes" rows="4"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php _e('Save', 'dealroom-extension'); ?></button>
                            <button type="button" id="cancel-tracking" class="button button-secondary"><?php _e('Cancel', 'dealroom-extension'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deal Tracking Modal Template -->
<div id="deal-tracking-modal" class="dealroom-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3 id="modal-title"><?php _e('Track Investment', 'dealroom-extension'); ?></h3>
        
        <div class="modal-body">
            <!-- Content will be dynamically populated -->
        </div>
    </div>
</div>