<?php
/**
 * DealRoom Communication Class
 * 
 * Handles messaging between entrepreneurs and investors.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_Communication {
    /**
     * Initialize the class
     */
    public function init() {
        // Add Ajax handlers
        add_action('wp_ajax_dealroom_send_message', array($this, 'send_message'));
        add_action('wp_ajax_dealroom_get_messages', array($this, 'get_messages'));
        add_action('wp_ajax_dealroom_mark_read', array($this, 'mark_read'));
        
        // Add shortcodes
        add_shortcode('dealroom_messaging', array($this, 'render_messaging_shortcode'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Check for unread messages
        add_action('wp_footer', array($this, 'check_unread_messages'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on messaging pages
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dealroom_messaging')) {
            // Styles
            wp_enqueue_style(
                'dealroom-messaging',
                DEALROOM_URL . 'assets/css/dealroom-messaging.css',
                array(),
                DEALROOM_VERSION
            );
            
            // Scripts
            wp_enqueue_script(
                'dealroom-messaging',
                DEALROOM_URL . 'assets/js/dealroom-messaging.js',
                array('jquery'),
                DEALROOM_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('dealroom-messaging', 'dealroomMessaging', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dealroom-messaging-nonce'),
                'refresh_interval' => 30000, // 30 seconds
                'i18n' => array(
                    'sending' => __('Sending...', 'dealroom'),
                    'sent' => __('Sent', 'dealroom'),
                    'error' => __('Error', 'dealroom'),
                    'noMessages' => __('No messages found', 'dealroom'),
                    'loading' => __('Loading...', 'dealroom'),
                ),
            ));
        }
    }

    /**
     * Render messaging shortcode
     */
    public function render_messaging_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dealroom-message">' . 
                   __('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access messaging.', 'dealroom') . 
                   '</div>';
        }

        $user_id = get_current_user_id();
        
        // Get conversations
        $conversations = $this->get_user_conversations($user_id);
        
        ob_start();
        include DEALROOM_PATH . 'templates/messaging.php';
        return ob_get_clean();
    }

    /**
     * Get user conversations
     */
    private function get_user_conversations($user_id) {
        global $wpdb;
        
        // Get all users this user has messaged or received messages from
        $query = $wpdb->prepare(
            "SELECT 
                IF(sender_id = %d, recipient_id, sender_id) as other_user_id,
                MAX(created_at) as last_message_time
            FROM {$wpdb->prefix}dealroom_messages
            WHERE sender_id = %d OR recipient_id = %d
            GROUP BY IF(sender_id = %d, recipient_id, sender_id)
            ORDER BY last_message_time DESC",
            $user_id, $user_id, $user_id, $user_id
        );
        
        $results = $wpdb->get_results($query);
        
        $conversations = array();
        
        foreach ($results as $result) {
            $other_user = get_userdata($result->other_user_id);
            
            if (!$other_user) {
                continue;
            }
            
            // Get last message
            $last_message_query = $wpdb->prepare(
                "SELECT *
                FROM {$wpdb->prefix}dealroom_messages
                WHERE (sender_id = %d AND recipient_id = %d) OR (sender_id = %d AND recipient_id = %d)
                ORDER BY created_at DESC
                LIMIT 1",
                $user_id, $result->other_user_id, $result->other_user_id, $user_id
            );
            
            $last_message = $wpdb->get_row($last_message_query);
            
            // Get unread count
            $unread_query = $wpdb->prepare(
                "SELECT COUNT(*) 
                FROM {$wpdb->prefix}dealroom_messages
                WHERE recipient_id = %d AND sender_id = %d AND is_read = 0",
                $user_id, $result->other_user_id
            );
            
            $unread_count = $wpdb->get_var($unread_query);
            
            // Get deal info if applicable
            $deal_info = null;
            
            if ($last_message && $last_message->deal_id) {
                $deal = get_post($last_message->deal_id);
                
                if ($deal && $deal->post_type === 'deal') {
                    $deal_info = array(
                        'id' => $deal->ID,
                        'title' => $deal->post_title,
                        'permalink' => get_permalink($deal->ID),
                    );
                }
            }
            
            $conversations[] = array(
                'user_id' => $result->other_user_id,
                'display_name' => $other_user->display_name,
                'avatar' => get_avatar_url($result->other_user_id, array('size' => 50)),
                'last_message' => $last_message ? $last_message->message : '',
                'last_message_time' => $last_message ? $last_message->created_at : '',
                'unread_count' => $unread_count,
                'deal' => $deal_info,
            );
        }
        
        return $conversations;
    }

    /**
     * Send message (Ajax handler)
     */
    public function send_message() {
        check_ajax_referer('dealroom-messaging-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to send messages.', 'dealroom')));
        }
        
        $recipient_id = isset($_POST['recipient_id']) ? intval($_POST['recipient_id']) : 0;
        $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
        $message_text = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $user_id = get_current_user_id();
        
        if (!$recipient_id || !$message_text) {
            wp_send_json_error(array('message' => __('Invalid message data.', 'dealroom')));
        }
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'dealroom_messages',
            array(
                'sender_id' => $user_id,
                'recipient_id' => $recipient_id,
                'deal_id' => $deal_id ?: null,
                'message' => $message_text,
                'created_at' => current_time('mysql'),
                'is_read' => 0,
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d')
        );
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to send message.', 'dealroom')));
        }
        
        $message_id = $wpdb->insert_id;
        
        // Get sender info
        $sender = get_userdata($user_id);
        
        wp_send_json_success(array(
            'message' => __('Message sent successfully.', 'dealroom'),
            'message_data' => array(
                'id' => $message_id,
                'sender_id' => $user_id,
                'recipient_id' => $recipient_id,
                'deal_id' => $deal_id,
                'message' => $message_text,
                'created_at' => current_time('mysql'),
                'is_read' => false,
                'sender_name' => $sender->display_name,
                'sender_avatar' => get_avatar_url($user_id, array('size' => 50)),
                'is_mine' => true,
            ),
        ));
    }

    /**
     * Get messages (Ajax handler)
     */
    public function get_messages() {
        check_ajax_referer('dealroom-messaging-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view messages.', 'dealroom')));
        }
        
        $other_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
        $last_id = isset($_POST['last_id']) ? intval($_POST['last_id']) : 0;
        $user_id = get_current_user_id();
        
        if (!$other_user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'dealroom')));
        }
        
        global $wpdb;
        
        $conditions = array(
            "((sender_id = %d AND recipient_id = %d) OR (sender_id = %d AND recipient_id = %d))"
        );
        
        $params = array($user_id, $other_user_id, $other_user_id, $user_id);
        
        if ($deal_id) {
            $conditions[] = "deal_id = %d";
            $params[] = $deal_id;
        }
        
        if ($last_id) {
            $conditions[] = "id > %d";
            $params[] = $last_id;
        }
        
        $condition_string = implode(' AND ', $conditions);
        
        $query = $wpdb->prepare(
            "SELECT *
            FROM {$wpdb->prefix}dealroom_messages
            WHERE $condition_string
            ORDER BY created_at ASC",
            $params
        );
        
        $results = $wpdb->get_results($query);
        
        $messages = array();
        $message_ids = array();
        
        foreach ($results as $row) {
            $sender = get_userdata($row->sender_id);
            
            if (!$sender) {
                continue;
            }
            
            $messages[] = array(
                'id' => $row->id,
                'sender_id' => $row->sender_id,
                'recipient_id' => $row->recipient_id,
                'deal_id' => $row->deal_id,
                'message' => $row->message,
                'created_at' => $row->created_at,
                'is_read' => (bool)$row->is_read,
                'sender_name' => $sender->display_name,
                'sender_avatar' => get_avatar_url($row->sender_id, array('size' => 50)),
                'is_mine' => $row->sender_id === $user_id,
            );
            
            if (!$row->is_read && $row->recipient_id === $user_id) {
                $message_ids[] = $row->id;
            }
        }
        
        // Mark messages as read
        if (!empty($message_ids)) {
            $placeholders = implode(',', array_fill(0, count($message_ids), '%d'));
            
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}dealroom_messages
                SET is_read = 1
                WHERE id IN ($placeholders)",
                $message_ids
            ));
        }
        
        wp_send_json_success(array('messages' => $messages));
    }

    /**
     * Mark messages as read (Ajax handler)
     */
    public function mark_read() {
        check_ajax_referer('dealroom-messaging-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to mark messages as read.', 'dealroom')));
        }
        
        $message_ids = isset($_POST['message_ids']) ? array_map('intval', $_POST['message_ids']) : array();
        $user_id = get_current_user_id();
        
        if (empty($message_ids)) {
            wp_send_json_error(array('message' => __('No messages specified.', 'dealroom')));
        }
        
        global $wpdb;
        
        $placeholders = implode(',', array_fill(0, count($message_ids), '%d'));
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}dealroom_messages
            SET is_read = 1
            WHERE id IN ($placeholders)
            AND recipient_id = %d",
            array_merge($message_ids, array($user_id))
        ));
        
        wp_send_json_success(array(
            'message' => __('Messages marked as read.', 'dealroom'),
            'count' => $result,
        ));
    }

    /**
     * Check for unread messages
     */
    public function check_unread_messages() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        
        $unread_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_messages
            WHERE recipient_id = %d AND is_read = 0",
            $user_id
        ));
        
        if ($unread_count > 0) {
            ?>
            <div id="dealroom-unread-notification" style="display: none;" data-count="<?php echo esc_attr($unread_count); ?>"></div>
            <script>
            (function() {
                var notificationEl = document.getElementById('dealroom-unread-notification');
                if (notificationEl) {
                    var count = parseInt(notificationEl.getAttribute('data-count'), 10);
                    if (count > 0) {
                        // Check if notification already shown
                        if (!localStorage.getItem('dealroom_notification_shown_' + count)) {
                            // Show notification
                            if ("Notification" in window) {
                                if (Notification.permission === "granted") {
                                    var notification = new Notification("DealRoom", {
                                        body: "You have " + count + " unread " + (count === 1 ? "message" : "messages"),
                                        icon: "<?php echo esc_url(DEALROOM_URL . 'assets/img/icon.png'); ?>"
                                    });
                                    
                                    notification.onclick = function() {
                                        window.open("<?php echo esc_url(home_url('/messages/')); ?>");
                                    };
                                    
                                    // Mark as shown
                                    localStorage.setItem('dealroom_notification_shown_' + count, 'true');
                                }
                            }
                        }
                    }
                }
            })();
            </script>
            <?php
        }
    }
}