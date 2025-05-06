<?php
/**
 * DealRoom Submission Class
 * 
 * Handles the deal submission process and multi-step form.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_Submission {
    /**
     * Initialize the class
     */
    public function init() {
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register shortcodes
        add_shortcode('dealroom_submit_deal', array($this, 'render_submission_form'));
        
        // Handle form submission
        add_action('wp_ajax_dealroom_save_draft', array($this, 'save_draft'));
        add_action('wp_ajax_dealroom_submit_deal', array($this, 'submit_deal'));
        
        // Handle file uploads
        add_action('wp_ajax_dealroom_upload_file', array($this, 'handle_file_upload'));
    }

    /**
     * Enqueue scripts and styles for the submission form
     */
    public function enqueue_scripts() {
        // Only enqueue on submission page
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dealroom_submit_deal')) {
            // Styles
            wp_enqueue_style(
                'dealroom-submission-styles',
                DEALROOM_URL . 'assets/css/dealroom-submission.css',
                array(),
                DEALROOM_VERSION
            );
            
            // Scripts
            wp_enqueue_script(
                'dealroom-submission-scripts',
                DEALROOM_URL . 'assets/js/dealroom-submission.js',
                array('jquery'),
                DEALROOM_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('dealroom-submission-scripts', 'dealroomSubmission', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dealroom-submission-nonce'),
                'max_file_size' => wp_max_upload_size(),
                'i18n' => array(
                    'saving' => __('Saving...', 'dealroom'),
                    'saved' => __('Draft Saved', 'dealroom'),
                    'error' => __('Error', 'dealroom'),
                    'submitting' => __('Submitting...', 'dealroom'),
                    'file_too_large' => __('File is too large. Maximum size is', 'dealroom'),
                    'invalid_file_type' => __('Invalid file type.', 'dealroom'),
                ),
            ));
        }
    }

    /**
     * Save a draft of the deal
     */
    public function save_draft() {
        check_ajax_referer('dealroom-submission-nonce', 'nonce');
        
        if (!current_user_can('dealroom_submit_deal')) {
            wp_send_json_error(array('message' => __('You do not have permission to submit deals.', 'dealroom')));
        }
        
        // Get post data
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $user_id = get_current_user_id();
        
        // Prepare post data
        $post_data = array(
            'post_title' => sanitize_text_field($_POST['title']),
            'post_content' => wp_kses_post($_POST['content']),
            'post_type' => 'deal',
            'post_status' => 'draft',
            'post_author' => $user_id,
        );
        
        // If post ID is provided, update existing post
        if ($post_id > 0) {
            // Verify post belongs to current user
            $post = get_post($post_id);
            if ($post && $post->post_author != $user_id) {
                wp_send_json_error(array('message' => __('You do not have permission to edit this deal.', 'dealroom')));
            }
            
            $post_data['ID'] = $post_id;
        }
        
        // Insert or update post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }
        
        // Save meta fields
        $meta_fields = array(
            'sector', 'organization_name', 'company_description', 'traction', 'funding_ask',
            'funding_stage', 'equity_offered', 'minimum_investment', 'video_url', 'tags',
            'location'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Handle arrays
        if (isset($_POST['team_members']) && is_array($_POST['team_members'])) {
            update_post_meta($post_id, 'team_members', array_map('sanitize_text_field', $_POST['team_members']));
        }
        
        if (isset($_POST['milestones']) && is_array($_POST['milestones'])) {
            update_post_meta($post_id, 'milestones', array_map('sanitize_text_field', $_POST['milestones']));
        }
        
        // Log activity
        dealroom()->log_activity($user_id, 'draft_saved', 'deal', $post_id);
        
        wp_send_json_success(array(
            'message' => __('Draft saved successfully.', 'dealroom'),
            'post_id' => $post_id,
        ));
    }

    /**
     * Submit the deal for review
     */
    public function submit_deal() {
        check_ajax_referer('dealroom-submission-nonce', 'nonce');
        
        if (!current_user_can('dealroom_submit_deal')) {
            wp_send_json_error(array('message' => __('You do not have permission to submit deals.', 'dealroom')));
        }
        
        // Get post data
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $user_id = get_current_user_id();
        
        // Verify required fields
        $required_fields = array('title', 'content', 'sector', 'organization_name', 'funding_ask');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => __('Please fill in all required fields.', 'dealroom')));
            }
        }
        
        // Prepare post data
        $post_data = array(
            'post_title' => sanitize_text_field($_POST['title']),
            'post_content' => wp_kses_post($_POST['content']),
            'post_type' => 'deal',
            'post_status' => 'pending',
            'post_author' => $user_id,
        );
        
        // If post ID is provided, update existing post
        if ($post_id > 0) {
            // Verify post belongs to current user
            $post = get_post($post_id);
            if ($post && $post->post_author != $user_id) {
                wp_send_json_error(array('message' => __('You do not have permission to edit this deal.', 'dealroom')));
            }
            
            $post_data['ID'] = $post_id;
        }
        
        // Insert or update post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }
        
        // Save meta fields
        $meta_fields = array(
            'sector', 'organization_name', 'company_description', 'traction', 'funding_ask',
            'funding_stage', 'equity_offered', 'minimum_investment', 'video_url', 'tags',
            'location'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Handle arrays
        if (isset($_POST['team_members']) && is_array($_POST['team_members'])) {
            update_post_meta($post_id, 'team_members', array_map('sanitize_text_field', $_POST['team_members']));
        }
        
        if (isset($_POST['milestones']) && is_array($_POST['milestones'])) {
            update_post_meta($post_id, 'milestones', array_map('sanitize_text_field', $_POST['milestones']));
        }
        
        // Log activity
        dealroom()->log_activity($user_id, 'deal_submitted', 'deal', $post_id);
        
        // Notify admin
        $this->notify_admin_of_submission($post_id);
        
        wp_send_json_success(array(
            'message' => __('Deal submitted for review.', 'dealroom'),
            'post_id' => $post_id,
            'redirect' => get_permalink($post_id),
        ));
    }

    /**
     * Handle file upload
     */
    public function handle_file_upload() {
        check_ajax_referer('dealroom-submission-nonce', 'nonce');
        
        if (!current_user_can('dealroom_submit_deal')) {
            wp_send_json_error(array('message' => __('You do not have permission to upload files.', 'dealroom')));
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file was uploaded.', 'dealroom')));
        }
        
        $file = $_FILES['file'];
        $field = sanitize_text_field($_POST['field']);
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $user_id = get_current_user_id();
        
        // Verify post belongs to current user if post ID is provided
        if ($post_id > 0) {
            $post = get_post($post_id);
            if ($post && $post->post_author != $user_id) {
                wp_send_json_error(array('message' => __('You do not have permission to edit this deal.', 'dealroom')));
            }
        }
        
        // Handle featured image and logo
        if ($field == 'featured_image' || $field == 'company_logo') {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('file', $post_id);
            
            if (is_wp_error($attachment_id)) {
                wp_send_json_error(array('message' => $attachment_id->get_error_message()));
            }
            
            if ($field == 'featured_image' && $post_id > 0) {
                set_post_thumbnail($post_id, $attachment_id);
            } elseif ($field == 'company_logo' && $post_id > 0) {
                update_post_meta($post_id, 'company_logo_id', $attachment_id);
            }
            
            $attachment_url = wp_get_attachment_url($attachment_id);
            
            wp_send_json_success(array(
                'message' => __('File uploaded successfully.', 'dealroom'),
                'attachment_id' => $attachment_id,
                'attachment_url' => $attachment_url,
            ));
        }
        // Handle document uploads
        else {
            $upload = wp_handle_upload($file, array('test_form' => false));
            
            if (isset($upload['error'])) {
                wp_send_json_error(array('message' => $upload['error']));
            }
            
            if ($post_id > 0) {
                update_post_meta($post_id, $field, $upload['url']);
            }
            
            wp_send_json_success(array(
                'message' => __('File uploaded successfully.', 'dealroom'),
                'file_url' => $upload['url'],
                'file_name' => basename($upload['url']),
            ));
        }
    }

    /**
     * Notify admin of new submission
     */
    private function notify_admin_of_submission($post_id) {
        $post = get_post($post_id);
        $user = get_userdata($post->post_author);
        $settings = get_option('dealroom_settings');
        $to = isset($settings['notification_email']) ? $settings['notification_email'] : get_option('admin_email');
        
        $subject = sprintf(__('New Deal Submission: %s', 'dealroom'), $post->post_title);
        
        $message = sprintf(__('A new deal has been submitted for review on your DealRoom marketplace.', 'dealroom')) . "\n\n";
        $message .= sprintf(__('Title: %s', 'dealroom'), $post->post_title) . "\n";
        $message .= sprintf(__('Submitted by: %s', 'dealroom'), $user->display_name) . "\n";
        $message .= sprintf(__('Organization: %s', 'dealroom'), get_post_meta($post_id, 'organization_name', true)) . "\n";
        $message .= sprintf(__('Funding Ask: $%s', 'dealroom'), number_format(get_post_meta($post_id, 'funding_ask', true))) . "\n\n";
        $message .= sprintf(__('Review Link: %s', 'dealroom'), admin_url('post.php?post=' . $post_id . '&action=edit')) . "\n";
        
        wp_mail($to, $subject, $message);
    }
}