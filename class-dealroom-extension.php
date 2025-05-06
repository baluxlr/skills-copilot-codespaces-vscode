<?php
/**
 * Main DealRoom Extension Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_Extension {
    /**
     * The single instance of DealRoom_Extension
     */
    private static $_instance = null;

    /**
     * Component instances
     */
    public $user_roles;
    public $submission;
    public $investor_tools;
    public $communication;
    public $admin_dashboard;
    public $api;

    /**
     * Main DealRoom_Extension Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->includes();
        $this->init_modules();
        $this->register_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once DEALROOM_PATH . 'includes/class-dealroom-user-roles.php';
        require_once DEALROOM_PATH . 'includes/class-dealroom-submission.php';
        require_once DEALROOM_PATH . 'includes/class-dealroom-communication.php';
        require_once DEALROOM_PATH . 'includes/class-dealroom-investor-tools.php';
        require_once DEALROOM_PATH . 'includes/class-dealroom-admin-dashboard.php';
        require_once DEALROOM_PATH . 'includes/class-dealroom-api.php';
        require_once DEALROOM_PATH . 'includes/class-dealroom-utilities.php';
    }

    /**
     * Initialize all modules
     */
    private function init_modules() {
        $this->user_roles = new DealRoom_User_Roles();
        $this->user_roles->init();
        
        $this->submission = new DealRoom_Submission();
        $this->submission->init();
        
        $this->communication = new DealRoom_Communication();
        $this->communication->init();
        
        $this->investor_tools = new DealRoom_Investor_Tools();
        $this->investor_tools->init();
        
        $this->admin_dashboard = new DealRoom_Admin_Dashboard();
        $this->admin_dashboard->init();
        
        $this->api = new DealRoom_API();
        $this->api->init();
    }

    /**
     * Register hooks
     */
    private function register_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add plugin action links
        add_filter('plugin_action_links_' . DEALROOM_BASENAME, array($this, 'add_plugin_action_links'));
        
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Main stylesheet
        wp_enqueue_style(
            'dealroom-extension-styles',
            DEALROOM_URL . 'assets/css/dealroom-extension.css',
            array(),
            DEALROOM_VERSION
        );
        
        // Main script
        wp_enqueue_script(
            'dealroom-extension-scripts',
            DEALROOM_URL . 'assets/js/dealroom-extension.js',
            array('jquery'),
            DEALROOM_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('dealroom-extension-scripts', 'dealroomExt', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dealroom-extension-nonce'),
            'is_logged_in' => is_user_logged_in(),
            'user_role' => $this->get_user_role(),
            'assets_url' => DEALROOM_URL . 'assets/',
        ));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on dealroom pages
        if (strpos($hook, 'dealroom') === false && get_post_type() !== 'deal') {
            return;
        }
        
        // Admin stylesheet
        wp_enqueue_style(
            'dealroom-extension-admin-styles',
            DEALROOM_URL . 'assets/css/dealroom-extension-admin.css',
            array(),
            DEALROOM_VERSION
        );
        
        // Load Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
            array(),
            '3.7.0',
            true
        );
        
        // Admin script
        wp_enqueue_script(
            'dealroom-extension-admin-scripts',
            DEALROOM_URL . 'assets/js/dealroom-extension-admin.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker', 'chartjs'),
            DEALROOM_VERSION,
            true
        );

        // Localize admin script
        wp_localize_script('dealroom-extension-admin-scripts', 'dealroomAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dealroom-admin-nonce'),
            'i18n' => array(
                'saving' => __('Saving...', 'dealroom-extension'),
                'saved' => __('Saved!', 'dealroom-extension'),
                'error' => __('Error', 'dealroom-extension'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'dealroom-extension'),
            ),
            'refresh_interval' => 30000, // 30 seconds
            'colors' => array(
                'primary' => '#0073aa',
                'secondary' => '#46b450',
                'tertiary' => '#ffba00',
                'quaternary' => '#dc3232',
            ),
        ));
    }

    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('edit.php?post_type=deal&page=dealroom-settings') . '">' . __('Settings', 'dealroom-extension') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('dealroom_dashboard', array($this, 'render_dashboard_shortcode'));
        add_shortcode('dealroom_investor_tools', array($this, 'render_investor_tools_shortcode'));
        add_shortcode('dealroom_messaging', array($this, 'render_messaging_shortcode'));
        add_shortcode('dealroom_profile', array($this, 'render_profile_shortcode'));
    }

    /**
     * Dashboard shortcode callback
     */
    public function render_dashboard_shortcode($atts) {
        // Render user dashboard based on role
        $user_role = $this->get_user_role();
        
        ob_start();
        include DEALROOM_PATH . 'templates/dashboard-' . $user_role . '.php';
        return ob_get_clean();
    }

    /**
     * Investor tools shortcode callback
     */
    public function render_investor_tools_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dealroom-message">You need to <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access investor tools.</div>';
        }
        
        // Check if user has investor role
        if (!current_user_can('dealroom_investor')) {
            return '<div class="dealroom-message">You need investor access to view this content.</div>';
        }
        
        ob_start();
        include DEALROOM_PATH . 'templates/investor-tools.php';
        return ob_get_clean();
    }

    /**
     * Messaging shortcode callback
     */
    public function render_messaging_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dealroom-message">You need to <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access messaging.</div>';
        }
        
        ob_start();
        include DEALROOM_PATH . 'templates/messaging.php';
        return ob_get_clean();
    }

    /**
     * Profile shortcode callback
     */
    public function render_profile_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dealroom-message">You need to <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access your profile.</div>';
        }
        
        ob_start();
        include DEALROOM_PATH . 'templates/profile.php';
        return ob_get_clean();
    }

    /**
     * Get current user role
     */
    private function get_user_role() {
        if (!is_user_logged_in()) {
            return 'guest';
        }
        
        $user = wp_get_current_user();
        
        if (in_array('administrator', $user->roles)) {
            return 'admin';
        } elseif (in_array('dealroom_investor', $user->roles)) {
            return 'investor';
        } elseif (in_array('dealroom_entrepreneur', $user->roles)) {
            return 'entrepreneur';
        } else {
            return 'subscriber';
        }
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        // Create necessary database tables
        self::create_tables();
        
        // Set up default settings
        self::setup_default_settings();
        
        // Create necessary pages
        self::create_pages();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Messages table
        $table_messages = $wpdb->prefix . 'dealroom_messages';
        $sql_messages = "CREATE TABLE IF NOT EXISTS $table_messages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) unsigned NOT NULL,
            recipient_id bigint(20) unsigned NOT NULL,
            deal_id bigint(20) unsigned DEFAULT NULL,
            message text NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY sender_id (sender_id),
            KEY recipient_id (recipient_id),
            KEY deal_id (deal_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Watchlist table
        $table_watchlist = $wpdb->prefix . 'dealroom_watchlist';
        $sql_watchlist = "CREATE TABLE IF NOT EXISTS $table_watchlist (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            deal_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_deal (user_id, deal_id),
            KEY user_id (user_id),
            KEY deal_id (deal_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Activity log table
        $table_activity = $wpdb->prefix . 'dealroom_activity_log';
        $sql_activity = "CREATE TABLE IF NOT EXISTS $table_activity (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            action varchar(100) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) unsigned DEFAULT NULL,
            details text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY object_id (object_id),
            KEY action_type (action, object_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_messages);
        dbDelta($sql_watchlist);
        dbDelta($sql_activity);
    }

    /**
     * Setup default settings
     */
    private static function setup_default_settings() {
        $default_settings = array(
            'enable_messaging' => true,
            'enable_watchlist' => true,
            'enable_analytics' => true,
            'verification_required' => false,
            'deal_approval_required' => true,
            'investor_registration_open' => true,
            'entrepreneur_registration_open' => true,
            'analytics_refresh_interval' => 30,
        );
        
        update_option('dealroom_extension_settings', $default_settings);
    }

    /**
     * Create necessary pages
     */
    private static function create_pages() {
        $pages = array(
            'dealroom-dashboard' => array(
                'title' => 'DealRoom Dashboard',
                'content' => '[dealroom_dashboard]',
            ),
            'investor-tools' => array(
                'title' => 'Investor Tools',
                'content' => '[dealroom_investor_tools]',
            ),
            'dealroom-messaging' => array(
                'title' => 'Messages',
                'content' => '[dealroom_messaging]',
            ),
            'dealroom-profile' => array(
                'title' => 'My Profile',
                'content' => '[dealroom_profile]',
            ),
        );
        
        foreach ($pages as $slug => $page_data) {
            // Check if page exists
            $page_exists = get_page_by_path($slug);
            
            if (!$page_exists) {
                // Create page
                wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                ));
            }
        }
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}