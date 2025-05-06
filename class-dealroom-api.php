<?php
/**
 * DealRoom API Class
 * 
 * Handles REST API endpoints for the DealRoom extension.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_API {
    /**
     * Initialize the class
     */
    public function init() {
        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Register deals endpoint
        register_rest_route('dealroom/v1', '/deals', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_deals'),
            'permission_callback' => array($this, 'check_deals_permission'),
            'args' => array(
                'per_page' => array(
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ),
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
                'sector' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'funding_stage' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'min_funding' => array(
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ),
                'max_funding' => array(
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ),
                'search' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        register_rest_route('dealroom/v1', '/deals/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_deal'),
            'permission_callback' => array($this, 'check_deals_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
        
        // Register watchlist endpoints
        register_rest_route('dealroom/v1', '/watchlist', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_watchlist'),
            'permission_callback' => array($this, 'check_watchlist_permission'),
        ));
        
        register_rest_route('dealroom/v1', '/watchlist/add', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_to_watchlist'),
            'permission_callback' => array($this, 'check_watchlist_permission'),
            'args' => array(
                'deal_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
        
        register_rest_route('dealroom/v1', '/watchlist/remove', array(
            'methods' => 'POST',
            'callback' => array($this, 'remove_from_watchlist'),
            'permission_callback' => array($this, 'check_watchlist_permission'),
            'args' => array(
                'deal_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
        
        // Register user endpoints
        register_rest_route('dealroom/v1', '/user/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_profile'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));
        
        register_rest_route('dealroom/v1', '/user/profile', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_user_profile'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));
        
        // Register messaging endpoints
        register_rest_route('dealroom/v1', '/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_messages'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
            'args' => array(
                'user_id' => array(
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ),
                'deal_id' => array(
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
        
        register_rest_route('dealroom/v1', '/messages/send', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_message'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
            'args' => array(
                'recipient_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
                'message' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'deal_id' => array(
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
    }

    /**
     * Check permission for deals endpoints
     */
    public function check_deals_permission() {
        // Public deals can be viewed by anyone
        return true;
    }

    /**
     * Check permission for watchlist endpoints
     */
    public function check_watchlist_permission() {
        // Only logged in users with proper capability can access watchlist
        return is_user_logged_in() && current_user_can('dealroom_add_to_watchlist');
    }

    /**
     * Get deals
     */
    public function get_deals($request) {
        $params = $request->get_params();
        
        $args = array(
            'post_type' => 'deal',
            'post_status' => 'publish',
            'posts_per_page' => $params['per_page'],
            'paged' => $params['page'],
            'meta_query' => array(),
        );
        
        // Add search
        if (!empty($params['search'])) {
            $args['s'] = $params['search'];
        }
        
        // Add sector filter
        if (!empty($params['sector'])) {
            $args['meta_query'][] = array(
                'key' => 'sector',
                'value' => $params['sector'],
                'compare' => '=',
            );
        }
        
        // Add funding stage filter
        if (!empty($params['funding_stage'])) {
            $args['meta_query'][] = array(
                'key' => 'funding_stage',
                'value' => $params['funding_stage'],
                'compare' => '=',
            );
        }
        
        // Add min funding filter
        if (!empty($params['min_funding']) && $params['min_funding'] > 0) {
            $args['meta_query'][] = array(
                'key' => 'funding_ask',
                'value' => $params['min_funding'],
                'compare' => '>=',
                'type' => 'NUMERIC',
            );
        }
        
        // Add max funding filter
        if (!empty($params['max_funding']) && $params['max_funding'] > 0) {
            $args['meta_query'][] = array(
                'key' => 'funding_ask',
                'value' => $params['max_funding'],
                'compare' => '<=',
                'type' => 'NUMERIC',
            );
        }
        
        $query = new WP_Query($args);
        $posts = $query->posts;
        
        $data = array();
        
        foreach ($posts as $post) {
            $data[] = $this->prepare_deal_data($post);
        }
        
        $response = rest_ensure_response($data);
        
        // Add pagination headers
        $total_posts = $query->found_posts;
        $total_pages = ceil($total_posts / $params['per_page']);
        
        $response->header('X-WP-Total', $total_posts);
        $response->header('X-WP-TotalPages', $total_pages);
        
        return $response;
    }

    /**
     * Get single deal
     */
    public function get_deal($request) {
        $id = $request['id'];
        $post = get_post($id);
        
        if (empty($post) || $post->post_type !== 'deal' || $post->post_status !== 'publish') {
            return new WP_Error('deal_not_found', __('Deal not found', 'dealroom-extension'), array('status' => 404));
        }
        
        return rest_ensure_response($this->prepare_deal_data($post, true));
    }

    /**
     * Get watchlist
     */
    public function get_watchlist() {
        $user_id = get_current_user_id();
        
        // Get watchlist items
        global $wpdb;
        
        $watchlist = $wpdb->get_results($wpdb->prepare(
            "SELECT w.*, p.post_title
            FROM {$wpdb->prefix}dealroom_watchlist w
            JOIN {$wpdb->posts} p ON w.deal_id = p.ID
            WHERE w.user_id = %d
            AND p.post_type = 'deal'
            AND p.post_status = 'publish'
            ORDER BY w.created_at DESC",
            $user_id
        ));
        
        $data = array();
        
        foreach ($watchlist as $item) {
            $post = get_post($item->deal_id);
            
            if ($post) {
                $data[] = $this->prepare_deal_data($post);
            }
        }
        
        return rest_ensure_response($data);
    }

    /**
     * Add to watchlist
     */
    public function add_to_watchlist($request) {
        $user_id = get_current_user_id();
        $deal_id = $request['deal_id'];
        
        // Verify deal exists
        $post = get_post($deal_id);
        
        if (empty($post) || $post->post_type !== 'deal') {
            return new WP_Error('deal_not_found', __('Deal not found', 'dealroom-extension'), array('status' => 404));
        }
        
        global $wpdb;
        
        // Check if already in watchlist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dealroom_watchlist
            WHERE user_id = %d AND deal_id = %d",
            $user_id, $deal_id
        ));
        
        if ($existing) {
            return new WP_Error('already_in_watchlist', __('Deal is already in watchlist', 'dealroom-extension'), array('status' => 400));
        }
        
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
        
        if (!$result) {
            return new WP_Error('watchlist_error', __('Failed to add to watchlist', 'dealroom-extension'), array('status' => 500));
        }
        
        // Log activity
        $this->log_activity($user_id, 'add_to_watchlist', 'deal', $deal_id);
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Deal added to watchlist', 'dealroom-extension'),
        ));
    }

    /**
     * Remove from watchlist
     */
    public function remove_from_watchlist($request) {
        $user_id = get_current_user_id();
        $deal_id = $request['deal_id'];
        
        global $wpdb;
        
        // Remove from watchlist
        $result = $wpdb->delete(
            $wpdb->prefix . 'dealroom_watchlist',
            array(
                'user_id' => $user_id,
                'deal_id' => $deal_id,
            ),
            array('%d', '%d')
        );
        
        if (!$result) {
            return new WP_Error('watchlist_error', __('Failed to remove from watchlist', 'dealroom-extension'), array('status' => 500));
        }
        
        // Log activity
        $this->log_activity($user_id, 'remove_from_watchlist', 'deal', $deal_id);
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Deal removed from watchlist', 'dealroom-extension'),
        ));
    }

    /**
     * Get user profile
     */
    public function get_user_profile() {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'dealroom-extension'), array('status' => 404));
        }
        
        $data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $user->roles,
            'avatar' => get_avatar_url($user->ID),
            'company_name' => get_user_meta($user->ID, 'dealroom_company_name', true),
            'position' => get_user_meta($user->ID, 'dealroom_position', true),
            'linkedin' => get_user_meta($user->ID, 'dealroom_linkedin', true),
            'bio' => get_user_meta($user->ID, 'dealroom_bio', true),
            'verified' => get_user_meta($user->ID, 'dealroom_verified', true) === '1',
        );
        
        // Add investor-specific fields
        if (in_array('dealroom_investor', $user->roles)) {
            $data['investor_type'] = get_user_meta($user->ID, 'dealroom_investor_type', true);
            $data['investment_focus'] = get_user_meta($user->ID, 'dealroom_investment_focus', true);
        }
        
        return rest_ensure_response($data);
    }

    /**
     * Update user profile
     */
    public function update_user_profile($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'dealroom-extension'), array('status' => 404));
        }
        
        $params = $request->get_params();
        
        // Update user data
        $userdata = array(
            'ID' => $user_id,
        );
        
        if (isset($params['display_name'])) {
            $userdata['display_name'] = sanitize_text_field($params['display_name']);
        }
        
        if (isset($params['first_name'])) {
            $userdata['first_name'] = sanitize_text_field($params['first_name']);
        }
        
        if (isset($params['last_name'])) {
            $userdata['last_name'] = sanitize_text_field($params['last_name']);
        }
        
        if (!empty($userdata)) {
            wp_update_user($userdata);
        }
        
        // Update user meta
        if (isset($params['company_name'])) {
            update_user_meta($user_id, 'dealroom_company_name', sanitize_text_field($params['company_name']));
        }
        
        if (isset($params['position'])) {
            update_user_meta($user_id, 'dealroom_position', sanitize_text_field($params['position']));
        }
        
        if (isset($params['linkedin'])) {
            update_user_meta($user_id, 'dealroom_linkedin', esc_url_raw($params['linkedin']));
        }
        
        if (isset($params['bio'])) {
            update_user_meta($user_id, 'dealroom_bio', sanitize_textarea_field($params['bio']));
        }
        
        // Update investor-specific fields
        if (in_array('dealroom_investor', $user->roles)) {
            if (isset($params['investor_type'])) {
                update_user_meta($user_id, 'dealroom_investor_type', sanitize_text_field($params['investor_type']));
            }
            
            if (isset($params['investment_focus']) && is_array($params['investment_focus'])) {
                update_user_meta($user_id, 'dealroom_investment_focus', array_map('sanitize_text_field', $params['investment_focus']));
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Profile updated successfully', 'dealroom-extension'),
        ));
    }

    /**
     * Get messages
     */
    public function get_messages($request) {
        $user_id = get_current_user_id();
        $other_user_id = $request['user_id'];
        $deal_id = $request['deal_id'];
        
        global $wpdb;
        
        // Get conversations if no specific user is provided
        if ($other_user_id <= 0) {
            // Get all conversations
            $query = $wpdb->prepare(
                "SELECT 
                    IF(sender_id = %d, recipient_id, sender_id) as other_user_id,
                    MAX(id) as last_message_id,
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
                
                // Get last message details
                $last_message = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}dealroom_messages WHERE id = %d",
                    $result->last_message_id
                ));
                
                // Get unread count
                $unread_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_messages
                    WHERE recipient_id = %d AND sender_id = %d AND is_read = 0",
                    $user_id, $result->other_user_id
                ));
                
                $conversations[] = array(
                    'user_id' => $result->other_user_id,
                    'display_name' => $other_user->display_name,
                    'avatar' => get_avatar_url($result->other_user_id, array('size' => 50)),
                    'last_message' => $last_message ? $last_message->message : '',
                    'last_message_time' => $last_message ? $last_message->created_at : '',
                    'unread_count' => (int) $unread_count,
                    'deal_id' => $last_message && $last_message->deal_id ? $last_message->deal_id : null,
                );
            }
            
            return rest_ensure_response($conversations);
        }
        
        // Otherwise, get specific conversation
        $conditions = array(
            "((sender_id = %d AND recipient_id = %d) OR (sender_id = %d AND recipient_id = %d))"
        );
        
        $params = array($user_id, $other_user_id, $other_user_id, $user_id);
        
        if ($deal_id > 0) {
            $conditions[] = "deal_id = %d";
            $params[] = $deal_id;
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
                'is_read' => (bool) $row->is_read,
                'sender_name' => $sender->display_name,
                'sender_avatar' => get_avatar_url($row->sender_id, array('size' => 50)),
                'is_mine' => $row->sender_id === $user_id,
            );
            
            // Collect IDs of unread messages received by current user
            if (!$row->is_read && $row->recipient_id === $user_id) {
                $message_ids[] = $row->id;
            }
        }
        
        // Mark messages as read
        if (!empty($message_ids)) {
            $placeholders = implode(', ', array_fill(0, count($message_ids), '%d'));
            
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}dealroom_messages
                SET is_read = 1
                WHERE id IN ($placeholders)",
                $message_ids
            ));
        }
        
        return rest_ensure_response($messages);
    }

    /**
     * Send message
     */
    public function send_message($request) {
        $user_id = get_current_user_id();
        $recipient_id = $request['recipient_id'];
        $message_text = $request['message'];
        $deal_id = $request['deal_id'];
        
        // Validate recipient
        $recipient = get_userdata($recipient_id);
        
        if (!$recipient) {
            return new WP_Error('invalid_recipient', __('Invalid recipient', 'dealroom-extension'), array('status' => 400));
        }
        
        // Validate message
        if (empty($message_text)) {
            return new WP_Error('empty_message', __('Message cannot be empty', 'dealroom-extension'), array('status' => 400));
        }
        
        // Check permissions
        $sender_role = $this->get_user_role($user_id);
        $recipient_role = $this->get_user_role($recipient_id);
        
        // Only allow entrepreneurs to message investors and vice versa
        // Admins can message anyone
        if ($sender_role !== 'admin' && 
            !(($sender_role === 'investor' && $recipient_role === 'entrepreneur') || 
              ($sender_role === 'entrepreneur' && $recipient_role === 'investor'))) {
            return new WP_Error('permission_denied', __('You cannot message this user', 'dealroom-extension'), array('status' => 403));
        }
        
        // Save message
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'dealroom_messages',
            array(
                'sender_id' => $user_id,
                'recipient_id' => $recipient_id,
                'deal_id' => $deal_id > 0 ? $deal_id : null,
                'message' => $message_text,
                'created_at' => current_time('mysql'),
                'is_read' => 0,
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d')
        );
        
        if (!$result) {
            return new WP_Error('message_error', __('Failed to send message', 'dealroom-extension'), array('status' => 500));
        }
        
        $message_id = $wpdb->insert_id;
        
        // Log activity
        $this->log_activity($user_id, 'send_message', 'message', $message_id, array(
            'recipient_id' => $recipient_id,
            'deal_id' => $deal_id,
        ));
        
        // Notify recipient
        $this->notify_new_message($recipient_id, $user_id, $message_text, $deal_id);
        
        // Get sender info
        $sender = get_userdata($user_id);
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Message sent successfully', 'dealroom-extension'),
            'message_data' => array(
                'id' => $message_id,
                'sender_id' => $user_id,
                'recipient_id' => $recipient_id,
                'deal_id' => $deal_id > 0 ? $deal_id : null,
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
     * Prepare deal data for API response
     */
    private function prepare_deal_data($post, $include_content = false) {
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'permalink' => get_permalink($post->ID),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
            'meta' => array(
                'organization_name' => get_post_meta($post->ID, 'organization_name', true),
                'sector' => get_post_meta($post->ID, 'sector', true),
                'funding_ask' => get_post_meta($post->ID, 'funding_ask', true),
                'funding_stage' => get_post_meta($post->ID, 'funding_stage', true),
                'equity_offered' => get_post_meta($post->ID, 'equity_offered', true),
                'minimum_investment' => get_post_meta($post->ID, 'minimum_investment', true),
                'location' => get_post_meta($post->ID, 'location', true),
                'custom_status' => get_post_meta($post->ID, 'dealroom_custom_status', true),
                'featured' => get_post_meta($post->ID, 'dealroom_featured', true) === '1',
            ),
        );
        
        // Include content if requested
        if ($include_content) {
            $data['content'] = apply_filters('the_content', $post->post_content);
            $data['excerpt'] = get_the_excerpt($post->ID);
            
            // Include additional meta fields
            $data['meta']['company_description'] = get_post_meta($post->ID, 'company_description', true);
            $data['meta']['traction'] = get_post_meta($post->ID, 'traction', true);
            $data['meta']['video_url'] = get_post_meta($post->ID, 'video_url', true);
            $data['meta']['pitchdeck'] = get_post_meta($post->ID, 'pitchdeck', true);
            $data['meta']['financial_model'] = get_post_meta($post->ID, 'financial_model', true);
            $data['meta']['additional_docs'] = get_post_meta($post->ID, 'additional_docs', true);
            $data['meta']['tags'] = get_post_meta($post->ID, 'tags', true);
            
            // Include company logo
            $company_logo_id = get_post_meta($post->ID, 'company_logo_id', true);
            if ($company_logo_id) {
                $data['meta']['company_logo'] = wp_get_attachment_url($company_logo_id);
            }
            
            // Include author info
            $author_id = $post->post_author;
            $author = get_userdata($author_id);
            
            if ($author) {
                $data['author'] = array(
                    'id' => $author->ID,
                    'name' => $author->display_name,
                    'avatar' => get_avatar_url($author->ID, array('size' => 100)),
                    'company_name' => get_user_meta($author->ID, 'dealroom_company_name', true),
                    'position' => get_user_meta($author->ID, 'dealroom_position', true),
                    'bio' => get_user_meta($author->ID, 'dealroom_bio', true),
                    'verified' => get_user_meta($author->ID, 'dealroom_verified', true) === '1',
                );
            }
            
            // Check if deal is in user's watchlist
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                
                global $wpdb;
                
                $in_watchlist = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}dealroom_watchlist
                    WHERE user_id = %d AND deal_id = %d",
                    $user_id, $post->ID
                ));
                
                $data['in_watchlist'] = $in_watchlist > 0;
                
                // Get user's notes
                $notes = get_user_meta($user_id, 'dealroom_notes_' . $post->ID, true);
                if ($notes) {
                    $data['user_notes'] = $notes;
                }
                
                // Log view
                $this->log_activity($user_id, 'view_deal', 'deal', $post->ID);
                
                // Increment view count
                $view_count = get_post_meta($post->ID, 'dealroom_view_count', true);
                update_post_meta($post->ID, 'dealroom_view_count', $view_count ? $view_count + 1 : 1);
            }
        }
        
        return $data;
    }

    /**
     * Log activity
     */
    private function log_activity($user_id, $action, $object_type, $object_id, $details = null) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'dealroom_activity_log',
            array(
                'user_id' => $user_id,
                'action' => $action,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'details' => $details ? json_encode($details) : null,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s')
        );
    }

    /**
     * Notify user of new message
     */
    private function notify_new_message($recipient_id, $sender_id, $message_text, $deal_id = 0) {
        $recipient = get_userdata($recipient_id);
        $sender = get_userdata($sender_id);
        
        if (!$recipient || !$sender) {
            return false;
        }
        
        $subject = sprintf(__('New Message from %s', 'dealroom-extension'), $sender->display_name);
        
        $message = sprintf(__('You have received a new message from %s on DealRoom.', 'dealroom-extension'), $sender->display_name) . "\n\n";
        
        if ($deal_id > 0) {
            $deal = get_post($deal_id);
            if ($deal) {
                $message .= sprintf(__('Regarding: %s', 'dealroom-extension'), $deal->post_title) . "\n\n";
            }
        }
        
        $message .= __('Message:', 'dealroom-extension') . "\n";
        $message .= $message_text . "\n\n";
        $message .= __('To reply, please visit:', 'dealroom-extension') . "\n";
        $message .= home_url('/dealroom-messaging/') . "?user={$sender_id}" . ($deal_id > 0 ? "&deal={$deal_id}" : '') . "\n";
        
        return wp_mail($recipient->user_email, $subject, $message);
    }

    /**
     * Get user role
     */
    private function get_user_role($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return 'guest';
        }
        
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
}