<?php
/**
 * Main DealRoom Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom {
    /**
     * The single instance of DealRoom
     */
    private static $_instance = null;

    /**
     * Component instances
     */
    public $user_roles;
    public $submission;
    public $communication;
    public $investor_tools;
    public $admin_dashboard;
    public $api;
    public $settings;
    public $admin;

    /**
     * Main DealRoom Instance
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
        require_once DEALROOM_PATH . 'includes/class-dealroom-settings.php';
        require_once DEALROOM_PATH . 'includes/class-dealroom-utilities.php';
        require_once DEALROOM_PATH . 'includes/class-dealroom-admin.php';
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

        $this->settings = new DealRoom_Settings();
        $this->settings->init();

        // Initialize admin last to ensure all dependencies are loaded
        $this->admin = new DealRoom_Admin();
        $this->admin->init();
    }

    /**
     * Register hooks
     */
    private function register_hooks() {
        // Register post type
        add_action('init', array($this, 'register_post_type'));
        
        // Register taxonomies
        add_action('init', array($this, 'register_taxonomies'));
        
        // Add plugin action links
        add_filter('plugin_action_links_' . DEALROOM_BASENAME, array($this, 'add_plugin_action_links'));

        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));

        // Filter post type link
        add_filter('post_type_link', array($this, 'filter_deal_permalink'), 10, 2);
    }

    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            'deal/([^/]+)/?$',
            'index.php?post_type=deal&name=$matches[1]',
            'top'
        );
    }

    /**
     * Filter deal permalink
     */
    public function filter_deal_permalink($post_link, $post) {
        if ($post->post_type === 'deal') {
            return home_url('deal/' . $post->post_name);
        }
        return $post_link;
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('dealroom_dashboard', array($this, 'render_dashboard_shortcode'));
        add_shortcode('dealroom_deals', array($this, 'render_deals_shortcode'));
        add_shortcode('dealroom_submit_deal', array($this, 'render_submit_deal_shortcode'));
        add_shortcode('dealroom_investor_tools', array($this->investor_tools, 'render_watchlist'));
        add_shortcode('dealroom_messaging', array($this->communication, 'render_messaging_shortcode'));
        add_shortcode('dealroom_watchlist', array($this->investor_tools, 'render_watchlist'));
        add_shortcode('dealroom_deal_comparison', array($this->investor_tools, 'render_deal_comparison'));
        add_shortcode('dealroom_investment_tracker', array($this->investor_tools, 'render_investment_tracker'));
    }

    /**
     * Render submit deal shortcode
     */
    public function render_submit_deal_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dealroom-message">' . 
                   __('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to submit a deal.', 'dealroom') . 
                   '</div>';
        }

        if (!current_user_can('dealroom_submit_deal')) {
            return '<div class="dealroom-message">' . 
                   __('You do not have permission to submit deals.', 'dealroom') . 
                   '</div>';
        }

        ob_start();
        include DEALROOM_PATH . 'templates/submission-form.php';
        return ob_get_clean();
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Main stylesheet
        wp_enqueue_style(
            'dealroom-styles',
            DEALROOM_URL . 'assets/css/dealroom.css',
            array(),
            DEALROOM_VERSION
        );

        // Main script
        wp_enqueue_script(
            'dealroom-scripts',
            DEALROOM_URL . 'assets/js/dealroom.js',
            array('jquery'),
            DEALROOM_VERSION,
            true
        );

        // Localize script
        wp_localize_script('dealroom-scripts', 'dealroom', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dealroom-nonce'),
            'is_logged_in' => is_user_logged_in(),
            'user_role' => DealRoom_Utilities::get_user_role(),
            'assets_url' => DEALROOM_URL . 'assets/',
        ));
    }

    /**
     * Render dashboard shortcode
     */
    public function render_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dealroom-message">' . 
                   __('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access your dashboard.', 'dealroom') . 
                   '</div>';
        }

        ob_start();
        include DEALROOM_PATH . 'templates/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Render deals shortcode
     */
    public function render_deals_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'sector' => '',
            'stage' => '',
            'featured' => false,
        ), $atts);

        $args = array(
            'post_type' => 'deal',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish'
        );

        // Add sector filter
        if (!empty($atts['sector'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'deal_sector',
                'field' => 'slug',
                'terms' => $atts['sector']
            );
        }

        // Add stage filter
        if (!empty($atts['stage'])) {
            $args['meta_query'][] = array(
                'key' => 'funding_stage',
                'value' => $atts['stage']
            );
        }

        // Add featured filter
        if ($atts['featured']) {
            $args['meta_query'][] = array(
                'key' => 'dealroom_featured',
                'value' => '1'
            );
        }

        $deals = new WP_Query($args);

        ob_start();
        include DEALROOM_PATH . 'templates/deals.php';
        return ob_get_clean();
    }

    /**
     * Register deal post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Deals', 'dealroom'),
            'singular_name' => __('Deal', 'dealroom'),
            'menu_name' => __('DealRoom', 'dealroom'),
            'add_new' => __('Add New', 'dealroom'),
            'add_new_item' => __('Add New Deal', 'dealroom'),
            'edit_item' => __('Edit Deal', 'dealroom'),
            'new_item' => __('New Deal', 'dealroom'),
            'view_item' => __('View Deal', 'dealroom'),
            'search_items' => __('Search Deals', 'dealroom'),
            'not_found' => __('No deals found', 'dealroom'),
            'not_found_in_trash' => __('No deals found in trash', 'dealroom'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'deal'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-portfolio',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author'),
            'show_in_rest' => true,
        );

        register_post_type('deal', $args);
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Deal Sector taxonomy
        $sector_labels = array(
            'name' => __('Sectors', 'dealroom'),
            'singular_name' => __('Sector', 'dealroom'),
            'search_items' => __('Search Sectors', 'dealroom'),
            'all_items' => __('All Sectors', 'dealroom'),
            'parent_item' => __('Parent Sector', 'dealroom'),
            'parent_item_colon' => __('Parent Sector:', 'dealroom'),
            'edit_item' => __('Edit Sector', 'dealroom'),
            'update_item' => __('Update Sector', 'dealroom'),
            'add_new_item' => __('Add New Sector', 'dealroom'),
            'new_item_name' => __('New Sector Name', 'dealroom'),
            'menu_name' => __('Sectors', 'dealroom'),
        );

        register_taxonomy('deal_sector', array('deal'), array(
            'hierarchical' => true,
            'labels' => $sector_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'sector'),
            'show_in_rest' => true,
        ));
    }

    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('edit.php?post_type=deal&page=dealroom-settings') . '">' . __('Settings', 'dealroom') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
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
        
        // Register post type and taxonomies
        $instance = self::instance();
        $instance->register_post_type();
        $instance->register_taxonomies();
        $instance->add_rewrite_rules();
        
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
        
        update_option('dealroom_settings', $default_settings);
    }

    /**
     * Create necessary pages
     */
    private static function create_pages() {
        $pages = array(
            'dashboard' => array(
                'title' => __('Dashboard', 'dealroom'),
                'content' => '[dealroom_dashboard]',
                'order' => 1
            ),
            'deals' => array(
                'title' => __('Deals', 'dealroom'),
                'content' => '[dealroom_deals]',
                'order' => 2
            ),
            'investor-tools' => array(
                'title' => __('Investor Tools', 'dealroom'),
                'content' => '[dealroom_investor_tools]',
                'order' => 3
            ),
            'messages' => array(
                'title' => __('Messages', 'dealroom'),
                'content' => '[dealroom_messaging]',
                'order' => 4
            ),
            'submit-deal' => array(
                'title' => __('Submit Deal', 'dealroom'),
                'content' => '[dealroom_submit_deal]',
                'order' => 5
            )
        );
        
        foreach ($pages as $slug => $page) {
            // Check if page exists
            $existing_page = get_page_by_path($slug);
            
            if (!$existing_page) {
                // Create new page
                wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                    'menu_order' => $page['order']
                ));
            } else {
                // Update existing page
                wp_update_post(array(
                    'ID' => $existing_page->ID,
                    'post_content' => $page['content'],
                    'menu_order' => $page['order']
                ));
            }
        }
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}