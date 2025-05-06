<?php
/**
 * DealRoom Admin Dashboard Class
 * 
 * Handles the admin dashboard, analytics, and reporting.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_Admin_Dashboard {
    /**
     * Initialize the class
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add dashboard widgets
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        
        // Register admin scripts
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
        
        // Add Ajax handlers
        add_action('wp_ajax_dealroom_get_stats', array($this, 'get_stats'));
        add_action('wp_ajax_dealroom_get_deal_analytics', array($this, 'get_deal_analytics'));
        add_action('wp_ajax_dealroom_get_user_activity', array($this, 'get_user_activity'));
        
        // Add meta boxes to dashboard
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Only show to users with manage_settings capability
        if (!current_user_can('dealroom_manage_settings')) {
            return;
        }
        
        // Dashboard page
        add_submenu_page(
            'edit.php?post_type=deal',
            __('Dashboard', 'dealroom'),
            __('Dashboard', 'dealroom'),
            'dealroom_manage_settings',
            'dealroom-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        // Analytics page
        add_submenu_page(
            'edit.php?post_type=deal',
            __('Analytics', 'dealroom'),
            __('Analytics', 'dealroom'),
            'dealroom_view_reports',
            'dealroom-analytics',
            array($this, 'render_analytics_page')
        );
        
        // Users page
        add_submenu_page(
            'edit.php?post_type=deal',
            __('Users', 'dealroom'),
            __('Users', 'dealroom'),
            'dealroom_manage_settings',
            'dealroom-users',
            array($this, 'render_users_page')
        );
    }

    /**
     * Register admin scripts
     */
    public function register_admin_scripts($hook) {
        // Only load on dealroom pages
        if (strpos($hook, 'dealroom') === false && get_post_type() !== 'deal') {
            return;
        }
        
        // Load Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
            array(),
            '3.7.0',
            true
        );
        
        // Main admin scripts
        wp_enqueue_script(
            'dealroom-admin-dashboard',
            DEALROOM_URL . 'assets/js/dealroom-admin-dashboard.js',
            array('jquery', 'chartjs'),
            DEALROOM_VERSION,
            true
        );
        
        // Main admin styles
        wp_enqueue_style(
            'dealroom-admin-dashboard',
            DEALROOM_URL . 'assets/css/dealroom-admin-dashboard.css',
            array(),
            DEALROOM_VERSION
        );
        
        // Localize script
        wp_localize_script('dealroom-admin-dashboard', 'dealroomAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dealroom-admin-nonce'),
            'colors' => array(
                'primary' => '#0073aa',
                'secondary' => '#46b450',
                'tertiary' => '#ffba00',
                'quaternary' => '#dc3232',
                'quinary' => '#826eb4',
            ),
            'i18n' => array(
                'loading' => __('Loading...', 'dealroom'),
                'error' => __('Error loading data', 'dealroom'),
                'noData' => __('No data available', 'dealroom'),
            ),
        ));
    }

    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        // Only show to users with manage_settings capability
        if (!current_user_can('dealroom_manage_settings')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'dealroom_stats_widget',
            __('DealRoom Overview', 'dealroom'),
            array($this, 'render_stats_widget')
        );
    }

    /**
     * Add meta boxes to deal edit screen
     */
    public function add_meta_boxes() {
        // Only show to users with view_reports capability
        if (!current_user_can('dealroom_view_reports')) {
            return;
        }
        
        add_meta_box(
            'dealroom_deal_analytics',
            __('Deal Analytics', 'dealroom'),
            array($this, 'render_deal_analytics_meta_box'),
            'deal',
            'normal',
            'default'
        );
    }

    /**
     * Render the dashboard page
     */
    public function render_dashboard_page() {
        // Get statistics
        $total_deals = wp_count_posts('deal');
        $pending = $total_deals->pending;
        $published = $total_deals->publish;
        $drafts = $total_deals->draft;
        
        $users = count_users();
        $entrepreneurs = isset($users['avail_roles']['dealroom_entrepreneur']) ? $users['avail_roles']['dealroom_entrepreneur'] : 0;
        $investors = isset($users['avail_roles']['dealroom_investor']) ? $users['avail_roles']['dealroom_investor'] : 0;
        
        // Get recent activity
        global $wpdb;
        $recent_activity = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dealroom_activity_log
            ORDER BY created_at DESC
            LIMIT 10"
        );
        
        // Get funding statistics
        $funding_stats = $this->get_funding_statistics();
        $sector_distribution = $this->get_sector_distribution();
        
        include DEALROOM_PATH . 'templates/admin/dashboard.php';
    }

    /**
     * Render the analytics page
     */
    public function render_analytics_page() {
        include DEALROOM_PATH . 'templates/admin/analytics.php';
    }

    /**
     * Render the users page
     */
    public function render_users_page() {
        global $wpdb;
        
        // Get users with dealroom roles
        $entrepreneur_users = get_users(array(
            'role' => 'dealroom_entrepreneur',
            'orderby' => 'display_name',
        ));
        
        $investor_users = get_users(array(
            'role' => 'dealroom_investor',
            'orderby' => 'display_name',
        ));
        
        // Get verification stats
        $verified_entrepreneurs = 0;
        foreach ($entrepreneur_users as $user) {
            if (get_user_meta($user->ID, 'dealroom_verified', true) === '1') {
                $verified_entrepreneurs++;
            }
        }
        
        $verified_investors = 0;
        foreach ($investor_users as $user) {
            if (get_user_meta($user->ID, 'dealroom_verified', true) === '1') {
                $verified_investors++;
            }
        }
        
        include DEALROOM_PATH . 'templates/admin/users.php';
    }

    /**
     * Get stats (Ajax handler)
     */
    public function get_stats() {
        check_ajax_referer('dealroom-admin-nonce', 'nonce');
        
        if (!current_user_can('dealroom_view_reports')) {
            wp_send_json_error(array('message' => __('Permission denied', 'dealroom')));
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30_days';
        $end_date = current_time('mysql');
        
        switch ($period) {
            case '7_days':
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '30_days':
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case '90_days':
                $start_date = date('Y-m-d H:i:s', strtotime('-90 days'));
                break;
            case 'year':
                $start_date = date('Y-m-d H:i:s', strtotime('-1 year'));
                break;
            default:
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        }
        
        global $wpdb;
        
        // Get deal views
        $views = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count
            FROM {$wpdb->prefix}dealroom_activity_log
            WHERE action = 'view_deal'
            AND created_at BETWEEN %s AND %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $start_date,
            $end_date
        ));
        
        // Get new deals
        $new_deals = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(post_date) as date, COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'deal'
            AND post_date BETWEEN %s AND %s
            GROUP BY DATE(post_date)
            ORDER BY date ASC",
            $start_date,
            $end_date
        ));
        
        // Get new users
        $new_users = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(user_registered) as date, COUNT(*) as count
            FROM {$wpdb->users} u
            JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
            AND (
                um.meta_value LIKE %s
                OR um.meta_value LIKE %s
            )
            AND user_registered BETWEEN %s AND %s
            GROUP BY DATE(user_registered)
            ORDER BY date ASC",
            '%dealroom_entrepreneur%',
            '%dealroom_investor%',
            $start_date,
            $end_date
        ));
        
        // Format data for charts
        $dates = array();
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        while ($current <= $end) {
            $dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
        
        $data = array(
            'labels' => array_map(function($date) {
                return date('M j', strtotime($date));
            }, $dates),
            'views' => array_fill(0, count($dates), 0),
            'deals' => array_fill(0, count($dates), 0),
            'users' => array_fill(0, count($dates), 0),
        );
        
        // Fill in actual data
        foreach ($views as $view) {
            $index = array_search($view->date, $dates);
            if ($index !== false) {
                $data['views'][$index] = (int)$view->count;
            }
        }
        
        foreach ($new_deals as $deal) {
            $index = array_search($deal->date, $dates);
            if ($index !== false) {
                $data['deals'][$index] = (int)$deal->count;
            }
        }
        
        foreach ($new_users as $user) {
            $index = array_search($user->date, $dates);
            if ($index !== false) {
                $data['users'][$index] = (int)$user->count;
            }
        }
        
        wp_send_json_success($data);
    }

    /**
     * Get deal analytics (Ajax handler)
     */
    public function get_deal_analytics() {
        check_ajax_referer('dealroom-admin-nonce', 'nonce');
        
        if (!current_user_can('dealroom_view_reports')) {
            wp_send_json_error(array('message' => __('Permission denied', 'dealroom')));
        }
        
        $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
        
        if (!$deal_id) {
            wp_send_json_error(array('message' => __('Invalid deal ID', 'dealroom')));
        }
        
        global $wpdb;
        
        // Get views over time
        $views = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count
            FROM {$wpdb->prefix}dealroom_activity_log
            WHERE action = 'view_deal'
            AND object_id = %d
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $deal_id
        ));
        
        // Format data for chart
        $dates = array();
        $current = new DateTime('-30 days');
        $end = new DateTime();
        
        while ($current <= $end) {
            $dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
        
        $data = array(
            'labels' => array_map(function($date) {
                return date('M j', strtotime($date));
            }, $dates),
            'views' => array_fill(0, count($dates), 0),
        );
        
        foreach ($views as $view) {
            $index = array_search($view->date, $dates);
            if ($index !== false) {
                $data['views'][$index] = (int)$view->count;
            }
        }
        
        wp_send_json_success($data);
    }

    /**
     * Get user activity (Ajax handler)
     */
    public function get_user_activity() {
        check_ajax_referer('dealroom-admin-nonce', 'nonce');
        
        if (!current_user_can('dealroom_view_reports')) {
            wp_send_json_error(array('message' => __('Permission denied', 'dealroom')));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'dealroom')));
        }
        
        global $wpdb;
        
        // Get recent activity
        $activity = $wpdb->get_results($wpdb->prepare(
            "SELECT *
            FROM {$wpdb->prefix}dealroom_activity_log
            WHERE user_id = %d
            ORDER BY created_at DESC
            LIMIT 50",
            $user_id
        ));
        
        $data = array();
        foreach ($activity as $item) {
            $data[] = array(
                'id' => $item->id,
                'action' => $item->action,
                'object_type' => $item->object_type,
                'object_id' => $item->object_id,
                'details' => $item->details ? json_decode($item->details) : null,
                'created_at' => $item->created_at,
            );
        }
        
        wp_send_json_success(array('activity' => $data));
    }

    /**
     * Get funding statistics
     */
    private function get_funding_statistics() {
        global $wpdb;
        
        // Get total funding ask
        $total_funding = $wpdb->get_var(
            "SELECT SUM(meta_value) 
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'funding_ask'
            AND p.post_type = 'deal'
            AND p.post_status = 'publish'"
        );
        
        // Get funding by stage
        $stage_funding = $wpdb->get_results(
            "SELECT pm2.meta_value as stage, SUM(pm1.meta_value) as total
            FROM {$wpdb->postmeta} pm1
            JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            JOIN {$wpdb->posts} p ON pm1.post_id = p.ID
            WHERE pm1.meta_key = 'funding_ask'
            AND pm2.meta_key = 'funding_stage'
            AND p.post_type = 'deal'
            AND p.post_status = 'publish'
            GROUP BY pm2.meta_value"
        );
        
        return array(
            'total' => $total_funding,
            'by_stage' => $stage_funding,
        );
    }

    /**
     * Get sector distribution
     */
    private function get_sector_distribution() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT t.name, COUNT(*) as count
            FROM {$wpdb->term_relationships} tr
            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            WHERE tt.taxonomy = 'deal_sector'
            AND p.post_type = 'deal'
            AND p.post_status = 'publish'
            GROUP BY t.term_id
            ORDER BY count DESC"
        );
    }
}