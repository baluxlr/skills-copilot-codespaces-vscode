<?php
/**
 * DealRoom Admin Class
 * 
 * Handles admin-specific functionality for deals.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_Admin {
    /**
     * Initialize the class
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save deal data
        add_action('save_post_deal', array($this, 'save_deal'));
        
        // Add custom columns
        add_filter('manage_deal_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_deal_posts_custom_column', array($this, 'display_custom_columns'), 10, 2);
        
        // Add sortable columns
        add_filter('manage_edit-deal_sortable_columns', array($this, 'add_sortable_columns'));
        
        // Handle custom sorting
        add_action('pre_get_posts', array($this, 'handle_custom_sorting'));
        
        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=deal',
            __('Add New Deal', 'dealroom'),
            __('Add New', 'dealroom'),
            'edit_posts',
            'post-new.php?post_type=deal'
        );
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'dealroom_deal_details',
            __('Deal Details', 'dealroom'),
            array($this, 'render_deal_details_meta_box'),
            'deal',
            'normal',
            'high'
        );

        add_meta_box(
            'dealroom_deal_status',
            __('Deal Status', 'dealroom'),
            array($this, 'render_deal_status_meta_box'),
            'deal',
            'side',
            'high'
        );
    }

    /**
     * Render deal details meta box
     */
    public function render_deal_details_meta_box($post) {
        // Get deal meta
        $organization_name = get_post_meta($post->ID, 'organization_name', true);
        $sector = get_post_meta($post->ID, 'sector', true);
        $funding_ask = get_post_meta($post->ID, 'funding_ask', true);
        $funding_stage = get_post_meta($post->ID, 'funding_stage', true);
        $equity_offered = get_post_meta($post->ID, 'equity_offered', true);
        $minimum_investment = get_post_meta($post->ID, 'minimum_investment', true);
        $location = get_post_meta($post->ID, 'location', true);
        
        // Add nonce
        wp_nonce_field('dealroom_save_deal', 'dealroom_nonce');
        
        // Include template
        include DEALROOM_PATH . 'templates/deal-edit.php';
    }

    /**
     * Render deal status meta box
     */
    public function render_deal_status_meta_box($post) {
        $custom_status = get_post_meta($post->ID, 'dealroom_custom_status', true);
        $featured = get_post_meta($post->ID, 'dealroom_featured', true);
        
        include DEALROOM_PATH . 'templates/admin/deal-status.php';
    }

    /**
     * Save deal data
     */
    public function save_deal($post_id) {
        // Check nonce
        if (!isset($_POST['dealroom_nonce']) || !wp_verify_nonce($_POST['dealroom_nonce'], 'dealroom_save_deal')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        $meta_fields = array(
            'organization_name',
            'sector',
            'funding_ask',
            'funding_stage',
            'equity_offered',
            'minimum_investment',
            'location',
            'company_description',
            'traction',
            'video_url',
            'dealroom_custom_status',
            'dealroom_featured'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save arrays
        if (isset($_POST['team_members'])) {
            update_post_meta($post_id, 'team_members', array_map('sanitize_text_field', $_POST['team_members']));
        }
        
        if (isset($_POST['milestones'])) {
            update_post_meta($post_id, 'milestones', array_map('sanitize_text_field', $_POST['milestones']));
        }
        
        // Handle featured image
        if (isset($_POST['featured_image_id'])) {
            if ($_POST['featured_image_id']) {
                set_post_thumbnail($post_id, intval($_POST['featured_image_id']));
            } else {
                delete_post_thumbnail($post_id);
            }
        }
    }

    /**
     * Add custom columns to deals list
     */
    public function add_custom_columns($columns) {
        $date = $columns['date'];
        unset($columns['date']);
        
        $columns['funding_ask'] = __('Funding Ask', 'dealroom');
        $columns['funding_stage'] = __('Stage', 'dealroom');
        $columns['custom_status'] = __('Deal Status', 'dealroom');
        $columns['featured'] = __('Featured', 'dealroom');
        $columns['date'] = $date;
        
        return $columns;
    }

    /**
     * Display custom column content
     */
    public function display_custom_columns($column, $post_id) {
        switch ($column) {
            case 'funding_ask':
                $funding_ask = get_post_meta($post_id, 'funding_ask', true);
                echo $funding_ask ? DealRoom_Utilities::format_currency($funding_ask) : '—';
                break;
                
            case 'funding_stage':
                $funding_stage = get_post_meta($post_id, 'funding_stage', true);
                echo $funding_stage ? esc_html($funding_stage) : '—';
                break;
                
            case 'custom_status':
                $custom_status = get_post_meta($post_id, 'dealroom_custom_status', true);
                if ($custom_status) {
                    $status_labels = array(
                        'reviewing' => __('Reviewing', 'dealroom'),
                        'active' => __('Active', 'dealroom'),
                        'funded' => __('Funded', 'dealroom'),
                        'closed' => __('Closed', 'dealroom'),
                    );
                    
                    $label = isset($status_labels[$custom_status]) ? $status_labels[$custom_status] : $custom_status;
                    echo '<span class="dealroom-status ' . esc_attr($custom_status) . '">' . esc_html($label) . '</span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'featured':
                $featured = get_post_meta($post_id, 'dealroom_featured', true);
                echo $featured ? '✓' : '—';
                break;
        }
    }

    /**
     * Add sortable columns
     */
    public function add_sortable_columns($columns) {
        $columns['funding_ask'] = 'funding_ask';
        $columns['funding_stage'] = 'funding_stage';
        $columns['custom_status'] = 'custom_status';
        return $columns;
    }

    /**
     * Handle custom sorting
     */
    public function handle_custom_sorting($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'deal') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'funding_ask':
                $query->set('meta_key', 'funding_ask');
                $query->set('orderby', 'meta_value_num');
                break;
                
            case 'funding_stage':
                $query->set('meta_key', 'funding_stage');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'custom_status':
                $query->set('meta_key', 'dealroom_custom_status');
                $query->set('orderby', 'meta_value');
                break;
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        // Only load on deal pages
        if ($post_type !== 'deal') {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_style(
            'dealroom-admin-styles',
            DEALROOM_URL . 'assets/css/dealroom-admin-dashboard.css',
            array(),
            DEALROOM_VERSION
        );
        
        wp_enqueue_script(
            'dealroom-admin-scripts',
            DEALROOM_URL . 'assets/js/dealroom-admin.js',
            array('jquery', 'wp-util'),
            DEALROOM_VERSION,
            true
        );
        
        wp_localize_script('dealroom-admin-scripts', 'dealroomAdmin', array(
            'nonce' => wp_create_nonce('dealroom-admin-nonce'),
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this item?', 'dealroom'),
                'saving' => __('Saving...', 'dealroom'),
                'saved' => __('Saved!', 'dealroom'),
                'error' => __('Error', 'dealroom'),
            ),
        ));
    }
}