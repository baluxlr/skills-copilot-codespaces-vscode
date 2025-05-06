<?php
/**
 * DealRoom Settings Class
 * 
 * Handles plugin settings and options management.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_Settings {
    /**
     * Initialize the class
     */
    public function init() {
        // Add settings menu
        add_action('admin_menu', array($this, 'add_settings_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings menu
     */
    public function add_settings_menu() {
        add_submenu_page(
            'edit.php?post_type=deal',
            __('Settings', 'dealroom'),
            __('Settings', 'dealroom'),
            'dealroom_manage_settings',
            'dealroom-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('dealroom_settings', 'dealroom_settings');

        // General Settings
        add_settings_section(
            'dealroom_general_settings',
            __('General Settings', 'dealroom'),
            array($this, 'render_general_section'),
            'dealroom-settings'
        );

        add_settings_field(
            'enable_messaging',
            __('Messaging', 'dealroom'),
            array($this, 'render_checkbox_field'),
            'dealroom-settings',
            'dealroom_general_settings',
            array(
                'id' => 'enable_messaging',
                'description' => __('Enable messaging between users', 'dealroom')
            )
        );

        add_settings_field(
            'enable_watchlist',
            __('Watchlist', 'dealroom'),
            array($this, 'render_checkbox_field'),
            'dealroom-settings',
            'dealroom_general_settings',
            array(
                'id' => 'enable_watchlist',
                'description' => __('Enable deal watchlist feature', 'dealroom')
            )
        );

        add_settings_field(
            'enable_analytics',
            __('Analytics', 'dealroom'),
            array($this, 'render_checkbox_field'),
            'dealroom-settings',
            'dealroom_general_settings',
            array(
                'id' => 'enable_analytics',
                'description' => __('Enable analytics tracking', 'dealroom')
            )
        );

        // Deal Settings
        add_settings_section(
            'dealroom_deal_settings',
            __('Deal Settings', 'dealroom'),
            array($this, 'render_deal_section'),
            'dealroom-settings'
        );

        add_settings_field(
            'deal_approval_required',
            __('Deal Approval', 'dealroom'),
            array($this, 'render_checkbox_field'),
            'dealroom-settings',
            'dealroom_deal_settings',
            array(
                'id' => 'deal_approval_required',
                'description' => __('Require admin approval for new deals', 'dealroom')
            )
        );

        // User Settings
        add_settings_section(
            'dealroom_user_settings',
            __('User Settings', 'dealroom'),
            array($this, 'render_user_section'),
            'dealroom-settings'
        );

        add_settings_field(
            'verification_required',
            __('User Verification', 'dealroom'),
            array($this, 'render_checkbox_field'),
            'dealroom-settings',
            'dealroom_user_settings',
            array(
                'id' => 'verification_required',
                'description' => __('Require user verification before allowing deal submission', 'dealroom')
            )
        );

        add_settings_field(
            'investor_registration_open',
            __('Investor Registration', 'dealroom'),
            array($this, 'render_checkbox_field'),
            'dealroom-settings',
            'dealroom_user_settings',
            array(
                'id' => 'investor_registration_open',
                'description' => __('Allow new investor registrations', 'dealroom')
            )
        );

        add_settings_field(
            'entrepreneur_registration_open',
            __('Entrepreneur Registration', 'dealroom'),
            array($this, 'render_checkbox_field'),
            'dealroom-settings',
            'dealroom_user_settings',
            array(
                'id' => 'entrepreneur_registration_open',
                'description' => __('Allow new entrepreneur registrations', 'dealroom')
            )
        );

        // Notification Settings
        add_settings_section(
            'dealroom_notification_settings',
            __('Notification Settings', 'dealroom'),
            array($this, 'render_notification_section'),
            'dealroom-settings'
        );

        add_settings_field(
            'notification_email',
            __('Notification Email', 'dealroom'),
            array($this, 'render_text_field'),
            'dealroom-settings',
            'dealroom_notification_settings',
            array(
                'id' => 'notification_email',
                'description' => __('Email address for admin notifications', 'dealroom')
            )
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('dealroom_manage_settings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dealroom'));
        }
        ?>
        <div class="wrap">
            <h1><?php _e('DealRoom Settings', 'dealroom'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('dealroom_settings');
                do_settings_sections('dealroom-settings');
                submit_button();
                ?>
            </form>
        </div>

        <style>
            .form-table th {
                width: 250px;
            }
            .description {
                color: #666;
                font-style: italic;
                margin-top: 4px;
            }
        </style>
        <?php
    }

    /**
     * Render general settings section
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general platform settings.', 'dealroom') . '</p>';
    }

    /**
     * Render deal settings section
     */
    public function render_deal_section() {
        echo '<p>' . __('Configure deal submission and management settings.', 'dealroom') . '</p>';
    }

    /**
     * Render user settings section
     */
    public function render_user_section() {
        echo '<p>' . __('Configure user registration and verification settings.', 'dealroom') . '</p>';
    }

    /**
     * Render notification settings section
     */
    public function render_notification_section() {
        echo '<p>' . __('Configure notification settings.', 'dealroom') . '</p>';
    }

    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $options = get_option('dealroom_settings');
        $value = isset($options[$args['id']]) ? $options[$args['id']] : true;
        ?>
        <label>
            <input type="checkbox" name="dealroom_settings[<?php echo esc_attr($args['id']); ?>]" value="1" <?php checked($value, true); ?>>
            <?php echo esc_html($args['description']); ?>
        </label>
        <?php
    }

    /**
     * Render text field
     */
    public function render_text_field($args) {
        $options = get_option('dealroom_settings');
        $value = isset($options[$args['id']]) ? $options[$args['id']] : '';
        ?>
        <input type="text" class="regular-text" name="dealroom_settings[<?php echo esc_attr($args['id']); ?>]" value="<?php echo esc_attr($value); ?>">
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }
}