<?php
/**
 * DealRoom User Roles Class
 * 
 * Handles the creation and management of custom user roles and capabilities.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_User_Roles {
    /**
     * Initialize the class
     */
    public function init() {
        // Register custom roles
        add_action('init', array($this, 'register_roles'));
        
        // Add profile fields
        add_action('show_user_profile', array($this, 'add_custom_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_custom_profile_fields'));
        
        // Save profile fields
        add_action('personal_options_update', array($this, 'save_custom_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_custom_profile_fields'));
        
        // Modify user registration
        add_action('register_form', array($this, 'add_registration_fields'));
        add_filter('registration_errors', array($this, 'validate_registration_fields'), 10, 3);
        add_action('user_register', array($this, 'save_registration_fields'));
        
        // Add verification badge display
        add_filter('get_avatar', array($this, 'add_verification_badge'), 10, 5);

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Add Ajax handlers
        add_action('wp_ajax_dealroom_verify_user', array($this, 'verify_user'));
        add_action('wp_ajax_dealroom_get_user_stats', array($this, 'get_user_stats'));
    }

    /**
     * Register custom user roles and capabilities
     */
    public function register_roles() {
        // Remove old capabilities first
        $admin = get_role('administrator');
        if ($admin) {
            $admin->remove_cap('dealroom_manage_settings');
            $admin->remove_cap('dealroom_view_reports');
            $admin->remove_cap('dealroom_approve_deals');
            $admin->remove_cap('dealroom_verify_users');
        }

        // Entrepreneur role
        add_role(
            'dealroom_entrepreneur',
            __('Entrepreneur', 'dealroom'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
                'dealroom_submit_deal' => true,
                'dealroom_edit_own_deals' => true,
            )
        );
        
        // Investor role
        add_role(
            'dealroom_investor',
            __('Investor', 'dealroom'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
                'dealroom_view_deals' => true,
                'dealroom_contact_entrepreneurs' => true,
                'dealroom_add_to_watchlist' => true,
            )
        );
        
        // Add custom capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('dealroom_manage_settings');
            $admin->add_cap('dealroom_view_reports');
            $admin->add_cap('dealroom_approve_deals');
            $admin->add_cap('dealroom_verify_users');
            $admin->add_cap('dealroom_view_deals');
            $admin->add_cap('dealroom_contact_entrepreneurs');
            $admin->add_cap('dealroom_add_to_watchlist');
            $admin->add_cap('dealroom_submit_deal');
            $admin->add_cap('dealroom_edit_own_deals');
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
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
     * Render users admin page
     */
    public function render_users_page() {
        // Get users with dealroom roles
        $entrepreneur_users = get_users(array(
            'role' => 'dealroom_entrepreneur',
            'orderby' => 'registered',
            'order' => 'DESC'
        ));

        $investor_users = get_users(array(
            'role' => 'dealroom_investor',
            'orderby' => 'registered',
            'order' => 'DESC'
        ));

        ?>
        <div class="wrap">
            <h1><?php _e('DealRoom Users', 'dealroom'); ?></h1>

            <div class="dealroom-users-stats">
                <div class="stats-box">
                    <h3><?php _e('Entrepreneurs', 'dealroom'); ?></h3>
                    <div class="stats-number"><?php echo count($entrepreneur_users); ?></div>
                    <div class="stats-meta">
                        <?php 
                        $verified_entrepreneurs = 0;
                        foreach ($entrepreneur_users as $user) {
                            if (get_user_meta($user->ID, 'dealroom_verified', true)) {
                                $verified_entrepreneurs++;
                            }
                        }
                        printf(__('%d Verified', 'dealroom'), $verified_entrepreneurs);
                        ?>
                    </div>
                </div>

                <div class="stats-box">
                    <h3><?php _e('Investors', 'dealroom'); ?></h3>
                    <div class="stats-number"><?php echo count($investor_users); ?></div>
                    <div class="stats-meta">
                        <?php 
                        $verified_investors = 0;
                        foreach ($investor_users as $user) {
                            if (get_user_meta($user->ID, 'dealroom_verified', true)) {
                                $verified_investors++;
                            }
                        }
                        printf(__('%d Verified', 'dealroom'), $verified_investors);
                        ?>
                    </div>
                </div>
            </div>

            <div class="dealroom-users-tabs">
                <button type="button" class="tab-button active" data-tab="entrepreneurs"><?php _e('Entrepreneurs', 'dealroom'); ?></button>
                <button type="button" class="tab-button" data-tab="investors"><?php _e('Investors', 'dealroom'); ?></button>
            </div>

            <div class="dealroom-users-content">
                <!-- Entrepreneurs Tab -->
                <div id="entrepreneurs" class="tab-content active">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('User', 'dealroom'); ?></th>
                                <th><?php _e('Company', 'dealroom'); ?></th>
                                <th><?php _e('Deals', 'dealroom'); ?></th>
                                <th><?php _e('Verified', 'dealroom'); ?></th>
                                <th><?php _e('Registered', 'dealroom'); ?></th>
                                <th><?php _e('Actions', 'dealroom'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entrepreneur_users as $user): ?>
                                <tr>
                                    <td>
                                        <?php echo get_avatar($user->ID, 32); ?>
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <br>
                                        <small><?php echo esc_html($user->user_email); ?></small>
                                    </td>
                                    <td><?php echo esc_html(get_user_meta($user->ID, 'dealroom_company_name', true)); ?></td>
                                    <td>
                                        <?php
                                        $deals = get_posts(array(
                                            'post_type' => 'deal',
                                            'author' => $user->ID,
                                            'posts_per_page' => -1
                                        ));
                                        echo count($deals);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $verified = get_user_meta($user->ID, 'dealroom_verified', true);
                                        echo $verified ? '<span class="verified">✓</span>' : '—';
                                        ?>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></td>
                                    <td>
                                        <button type="button" class="button-secondary verify-user" data-user-id="<?php echo $user->ID; ?>" data-verified="<?php echo $verified ? '1' : '0'; ?>">
                                            <?php echo $verified ? __('Unverify', 'dealroom') : __('Verify', 'dealroom'); ?>
                                        </button>
                                        <a href="<?php echo get_edit_user_link($user->ID); ?>" class="button-secondary"><?php _e('Edit', 'dealroom'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Investors Tab -->
                <div id="investors" class="tab-content">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('User', 'dealroom'); ?></th>
                                <th><?php _e('Organization', 'dealroom'); ?></th>
                                <th><?php _e('Type', 'dealroom'); ?></th>
                                <th><?php _e('Watchlist', 'dealroom'); ?></th>
                                <th><?php _e('Verified', 'dealroom'); ?></th>
                                <th><?php _e('Registered', 'dealroom'); ?></th>
                                <th><?php _e('Actions', 'dealroom'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($investor_users as $user): ?>
                                <tr>
                                    <td>
                                        <?php echo get_avatar($user->ID, 32); ?>
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <br>
                                        <small><?php echo esc_html($user->user_email); ?></small>
                                    </td>
                                    <td><?php echo esc_html(get_user_meta($user->ID, 'dealroom_company_name', true)); ?></td>
                                    <td><?php echo esc_html(get_user_meta($user->ID, 'dealroom_investor_type', true)); ?></td>
                                    <td>
                                        <?php
                                        global $wpdb;
                                        $watchlist_count = $wpdb->get_var($wpdb->prepare(
                                            "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_watchlist WHERE user_id = %d",
                                            $user->ID
                                        ));
                                        echo $watchlist_count;
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $verified = get_user_meta($user->ID, 'dealroom_verified', true);
                                        echo $verified ? '<span class="verified">✓</span>' : '—';
                                        ?>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></td>
                                    <td>
                                        <button type="button" class="button-secondary verify-user" data-user-id="<?php echo $user->ID; ?>" data-verified="<?php echo $verified ? '1' : '0'; ?>">
                                            <?php echo $verified ? __('Unverify', 'dealroom') : __('Verify', 'dealroom'); ?>
                                        </button>
                                        <a href="<?php echo get_edit_user_link($user->ID); ?>" class="button-secondary"><?php _e('Edit', 'dealroom'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
            .dealroom-users-stats {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
            }
            .stats-box {
                flex: 1;
                background: white;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .stats-box h3 {
                margin: 0 0 10px;
                color: #23282d;
            }
            .stats-number {
                font-size: 36px;
                font-weight: bold;
                color: #0073aa;
            }
            .stats-meta {
                color: #666;
                font-size: 13px;
                margin-top: 5px;
            }
            .dealroom-users-tabs {
                margin-bottom: 20px;
            }
            .tab-button {
                padding: 10px 20px;
                border: none;
                background: none;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                color: #666;
                border-bottom: 2px solid transparent;
            }
            .tab-button.active {
                color: #0073aa;
                border-bottom-color: #0073aa;
            }
            .tab-content {
                display: none;
            }
            .tab-content.active {
                display: block;
            }
            .verified {
                color: #46b450;
                font-size: 16px;
            }
            .wp-list-table td {
                vertical-align: middle;
            }
            .wp-list-table .avatar {
                float: left;
                margin-right: 10px;
            }
            .button-secondary {
                margin-right: 5px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.tab-button').click(function() {
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                
                const tabId = $(this).data('tab');
                $('.tab-content').removeClass('active');
                $('#' + tabId).addClass('active');
            });

            // User verification
            $('.verify-user').click(function() {
                const button = $(this);
                const userId = button.data('user-id');
                const verified = button.data('verified') === '1';

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dealroom_verify_user',
                        nonce: '<?php echo wp_create_nonce('dealroom-admin-nonce'); ?>',
                        user_id: userId,
                        verify: !verified
                    },
                    success: function(response) {
                        if (response.success) {
                            button.data('verified', verified ? '0' : '1');
                            button.text(verified ? '<?php _e('Verify', 'dealroom'); ?>' : '<?php _e('Unverify', 'dealroom'); ?>');
                            
                            const verifiedCell = button.closest('tr').find('td:nth-child(4)');
                            verifiedCell.html(verified ? '—' : '<span class="verified">✓</span>');
                            
                            // Reload the page to update stats
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Add custom profile fields
     */
    public function add_custom_profile_fields($user) {
        // Implementation remains the same as original
    }

    /**
     * Save custom profile fields
     */
    public function save_custom_profile_fields($user_id) {
        // Implementation remains the same as original
    }

    /**
     * Add registration fields
     */
    public function add_registration_fields() {
        // Implementation remains the same as original
    }

    /**
     * Validate registration fields
     */
    public function validate_registration_fields($errors, $sanitized_user_login, $user_email) {
        // Implementation remains the same as original
    }

    /**
     * Save registration fields
     */
    public function save_registration_fields($user_id) {
        // Implementation remains the same as original
    }

    /**
     * Add verification badge to avatar
     */
    public function add_verification_badge($avatar, $id_or_email, $size, $default, $alt) {
        // Implementation remains the same as original
    }

    /**
     * Ajax handler for user verification
     */
    public function verify_user() {
        check_ajax_referer('dealroom-admin-nonce', 'nonce');

        if (!current_user_can('dealroom_verify_users')) {
            wp_send_json_error(array('message' => __('You do not have permission to verify users.', 'dealroom')));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $verify = isset($_POST['verify']) ? (bool)$_POST['verify'] : false;

        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'dealroom')));
        }

        update_user_meta($user_id, 'dealroom_verified', $verify ? '1' : '0');

        wp_send_json_success(array(
            'message' => $verify ? __('User verified successfully.', 'dealroom') : __('User unverified successfully.', 'dealroom')
        ));
    }

    /**
     * Ajax handler for user stats
     */
    public function get_user_stats() {
        check_ajax_referer('dealroom-admin-nonce', 'nonce');

        if (!current_user_can('dealroom_view_reports')) {
            wp_send_json_error(array('message' => __('You do not have permission to view reports.', 'dealroom')));
        }

        global $wpdb;

        $stats = array(
            'total_entrepreneurs' => count(get_users(array('role' => 'dealroom_entrepreneur'))),
            'total_investors' => count(get_users(array('role' => 'dealroom_investor'))),
            'verified_entrepreneurs' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'dealroom_verified' AND meta_value = '1' AND user_id IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$wpdb->prefix}capabilities' AND meta_value LIKE %s)",
                '%dealroom_entrepreneur%'
            )),
            'verified_investors' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'dealroom_verified' AND meta_value = '1' AND user_id IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$wpdb->prefix}capabilities' AND meta_value LIKE %s)",
                '%dealroom_investor%'
            )),
        );

        wp_send_json_success($stats);
    }
}