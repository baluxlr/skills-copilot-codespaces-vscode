<?php
/**
 * DealRoom Investor Tools Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_Investor_Tools {
    /**
     * Initialize the class
     */
    public function init() {
        // Add Ajax handlers
        add_action('wp_ajax_dealroom_toggle_watchlist', array($this, 'toggle_watchlist'));
        add_action('wp_ajax_dealroom_get_watchlist', array($this, 'get_watchlist'));
        add_action('wp_ajax_dealroom_compare_deals', array($this, 'compare_deals'));
        add_action('wp_ajax_dealroom_save_notes', array($this, 'save_notes'));
        add_action('wp_ajax_dealroom_track_investment', array($this, 'track_investment'));
        
        // Add shortcodes
        add_shortcode('dealroom_watchlist', array($this, 'render_watchlist'));
        add_shortcode('dealroom_deal_comparison', array($this, 'render_deal_comparison'));
        add_shortcode('dealroom_investment_tracker', array($this, 'render_investment_tracker'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on investor tools pages
        global $post;
        
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'dealroom_watchlist') || 
            has_shortcode($post->post_content, 'dealroom_deal_comparison') || 
            has_shortcode($post->post_content, 'dealroom_investment_tracker') ||
            has_shortcode($post->post_content, 'dealroom_investor_tools')
        )) {
            // Styles
            wp_enqueue_style(
                'dealroom-investor-tools',
                DEALROOM_URL . 'assets/css/dealroom-investor.css',
                array(),
                DEALROOM_VERSION
            );
            
            // Scripts
            wp_enqueue_script(
                'dealroom-investor-tools',
                DEALROOM_URL . 'assets/js/dealroom-investor.js',
                array('jquery'),
                DEALROOM_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('dealroom-investor-tools', 'dealroomInvestor', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dealroom-investor-nonce'),
                'i18n' => array(
                    'addToWatchlist' => __('Add to Watchlist', 'dealroom'),
                    'removeFromWatchlist' => __('Remove from Watchlist', 'dealroom'),
                    'adding' => __('Adding...', 'dealroom'),
                    'removing' => __('Removing...', 'dealroom'),
                    'compare' => __('Compare Deals', 'dealroom'),
                    'comparing' => __('Comparing...', 'dealroom'),
                    'save' => __('Save', 'dealroom'),
                    'saving' => __('Saving...', 'dealroom'),
                    'saved' => __('Saved', 'dealroom'),
                    'error' => __('Error', 'dealroom'),
                ),
            ));
        }
    }

    /**
     * Toggle deal in user's watchlist (Ajax handler)
     */
    public function toggle_watchlist() {
        check_ajax_referer('dealroom-investor-nonce', 'nonce');
        
        if (!current_user_can('dealroom_add_to_watchlist')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage watchlist.', 'dealroom')));
        }
        
        $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
        $user_id = get_current_user_id();
        
        if (!$deal_id) {
            wp_send_json_error(array('message' => __('Invalid deal ID.', 'dealroom')));
        }
        
        global $wpdb;
        
        // Check if deal is already in watchlist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dealroom_watchlist WHERE user_id = %d AND deal_id = %d",
            $user_id,
            $deal_id
        ));
        
        if ($existing) {
            // Remove from watchlist
            $result = $wpdb->delete(
                $wpdb->prefix . 'dealroom_watchlist',
                array(
                    'user_id' => $user_id,
                    'deal_id' => $deal_id,
                ),
                array('%d', '%d')
            );
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Deal removed from watchlist.', 'dealroom'),
                    'in_watchlist' => false,
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to remove deal from watchlist.', 'dealroom')));
            }
        } else {
            // Add to watchlist
            $result = $wpdb->insert(
                $wpdb->prefix . 'dealroom_watchlist',
                array(
                    'user_id' => $user_id,
                    'deal_id' => $deal_id,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%s')
            );
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Deal added to watchlist.', 'dealroom'),
                    'in_watchlist' => true,
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to add deal to watchlist.', 'dealroom')));
            }
        }
    }

    /**
     * Get user's watchlist (Ajax handler)
     */
    public function get_watchlist() {
        check_ajax_referer('dealroom-investor-nonce', 'nonce');
        
        if (!current_user_can('dealroom_add_to_watchlist')) {
            wp_send_json_error(array('message' => __('You do not have permission to view watchlist.', 'dealroom')));
        }
        
        $user_id = get_current_user_id();
        
        // Get watchlist items
        $watchlist = $this->get_user_watchlist($user_id);
        
        wp_send_json_success(array(
            'watchlist' => $watchlist,
        ));
    }

    /**
     * Compare deals (Ajax handler)
     */
    public function compare_deals() {
        check_ajax_referer('dealroom-investor-nonce', 'nonce');
        
        if (!current_user_can('dealroom_view_deals')) {
            wp_send_json_error(array('message' => __('You do not have permission to compare deals.', 'dealroom')));
        }
        
        $deal_ids = isset($_POST['deal_ids']) ? array_map('intval', $_POST['deal_ids']) : array();
        
        if (empty($deal_ids)) {
            wp_send_json_error(array('message' => __('No deals selected.', 'dealroom')));
        }
        
        // Get deal data
        $deals = array();
        
        foreach ($deal_ids as $deal_id) {
            $post = get_post($deal_id);
            
            if (!$post || $post->post_type !== 'deal') {
                continue;
            }
            
            $deals[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'permalink' => get_permalink($post->ID),
                'sector' => get_post_meta($post->ID, 'sector', true),
                'organization_name' => get_post_meta($post->ID, 'organization_name', true),
                'funding_ask' => get_post_meta($post->ID, 'funding_ask', true),
                'funding_stage' => get_post_meta($post->ID, 'funding_stage', true),
                'equity_offered' => get_post_meta($post->ID, 'equity_offered', true),
                'minimum_investment' => get_post_meta($post->ID, 'minimum_investment', true),
                'location' => get_post_meta($post->ID, 'location', true),
                'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
            );
        }
        
        ob_start();
        include DEALROOM_PATH . 'templates/deal-comparison.php';
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
        ));
    }

    /**
     * Save investor notes for a deal (Ajax handler)
     */
    public function save_notes() {
        check_ajax_referer('dealroom-investor-nonce', 'nonce');
        
        if (!current_user_can('dealroom_view_deals')) {
            wp_send_json_error(array('message' => __('You do not have permission to save notes.', 'dealroom')));
        }
        
        $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $user_id = get_current_user_id();
        
        if (!$deal_id) {
            wp_send_json_error(array('message' => __('Invalid deal ID.', 'dealroom')));
        }
        
        // Save notes
        update_user_meta($user_id, 'dealroom_notes_' . $deal_id, $notes);
        
        wp_send_json_success(array(
            'message' => __('Notes saved successfully.', 'dealroom'),
        ));
    }

    /**
     * Track investment (Ajax handler)
     */
    public function track_investment() {
        check_ajax_referer('dealroom-investor-nonce', 'nonce');
        
        if (!current_user_can('dealroom_view_deals')) {
            wp_send_json_error(array('message' => __('You do not have permission to track investments.', 'dealroom')));
        }
        
        $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'interested';
        $user_id = get_current_user_id();
        
        if (!$deal_id) {
            wp_send_json_error(array('message' => __('Invalid deal ID.', 'dealroom')));
        }
        
        // Get existing investments
        $investments = get_user_meta($user_id, 'dealroom_investments', true);
        
        if (!is_array($investments)) {
            $investments = array();
        }
        
        // Add or update investment
        $investments[$deal_id] = array(
            'amount' => $amount,
            'notes' => $notes,
            'status' => $status,
            'updated_at' => current_time('mysql'),
        );
        
        // Save investments
        update_user_meta($user_id, 'dealroom_investments', $investments);
        
        wp_send_json_success(array(
            'message' => __('Investment tracked successfully.', 'dealroom'),
        ));
    }

    /**
     * Render watchlist shortcode
     */
    public function render_watchlist($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dealroom-message">' . 
                   __('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to view your watchlist.', 'dealroom') . 
                   '</div>';
        }
        
        if (!current_user_can('dealroom_add_to_watchlist')) {
            return '<div class="dealroom-message">' . 
                   __('You do not have permission to view watchlist.', 'dealroom') . 
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        
        // Get watchlist items
        $watchlist = $this->get_user_watchlist($user_id);
        
        ob_start();
        include DEALROOM_PATH . 'templates/wishlist.php';
        return ob_get_clean();
    }

    /**
     * Render deal comparison shortcode
     */
    public function render_deal_comparison($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dealroom-message">' . 
                   __('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to compare deals.', 'dealroom') . 
                   '</div>';
        }
        
        if (!current_user_can('dealroom_view_deals')) {
            return '<div class="dealroom-message">' . 
                   __('You do not have permission to compare deals.', 'dealroom') . 
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'deals' => '',
        ), $atts);
        
        $deal_ids = array();
        
        if (!empty($atts['deals'])) {
            $deal_ids = array_map('intval', explode(',', $atts['deals']));
        }
        
        ob_start();
        include DEALROOM_PATH . 'templates/compare-deals.php';
        return ob_get_clean();
    }

    /**
     * Render investment tracker shortcode
     */
    public function render_investment_tracker($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dealroom-message">' . 
                   __('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to track investments.', 'dealroom') . 
                   '</div>';
        }
        
        if (!current_user_can('dealroom_view_deals')) {
            return '<div class="dealroom-message">' . 
                   __('You do not have permission to track investments.', 'dealroom') . 
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        
        // Get investments
        $investments = get_user_meta($user_id, 'dealroom_investments', true);
        
        if (!is_array($investments)) {
            $investments = array();
        }
        
        // Get deal details
        $investment_data = array();
        
        foreach ($investments as $deal_id => $investment) {
            $post = get_post($deal_id);
            
            if (!$post || $post->post_type !== 'deal') {
                continue;
            }
            
            $investment_data[] = array_merge($investment, array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'permalink' => get_permalink($post->ID),
                'organization_name' => get_post_meta($post->ID, 'organization_name', true),
                'sector' => get_post_meta($post->ID, 'sector', true),
                'funding_stage' => get_post_meta($post->ID, 'funding_stage', true),
                'thumbnail' => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
            ));
        }
        
        ob_start();
        include DEALROOM_PATH . 'templates/investor-tools.php';
        return ob_get_clean();
    }

    /**
     * Get user's watchlist
     */
    public function get_user_watchlist($user_id) {
        global $wpdb;
        
        $result = $wpdb->get_results($wpdb->prepare(
            "SELECT w.*, p.post_title, p.post_status
            FROM {$wpdb->prefix}dealroom_watchlist w
            JOIN {$wpdb->posts} p ON w.deal_id = p.ID
            WHERE w.user_id = %d
            AND p.post_type = 'deal'
            AND p.post_status IN ('publish', 'pending', 'draft')
            ORDER BY w.created_at DESC",
            $user_id
        ));
        
        $watchlist = array();
        
        foreach ($result as $item) {
            $post_id = $item->deal_id;
            
            $watchlist[] = array(
                'id' => $post_id,
                'title' => $item->post_title,
                'permalink' => get_permalink($post_id),
                'status' => $item->post_status,
                'organization_name' => get_post_meta($post_id, 'organization_name', true),
                'sector' => get_post_meta($post_id, 'sector', true),
                'funding_ask' => get_post_meta($post_id, 'funding_ask', true),
                'funding_stage' => get_post_meta($post_id, 'funding_stage', true),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                'added_on' => $item->created_at,
                'notes' => get_user_meta($user_id, 'dealroom_notes_' . $post_id, true),
            );
        }
        
        return $watchlist;
    }
}