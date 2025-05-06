<?php
/**
 * Messaging template
 */
defined('ABSPATH') || exit;

$user_id = get_current_user_id();
?>

<div class="dealroom-messaging">
    <!-- Messaging Interface -->
    <div class="messaging-container">
        <!-- Conversation List -->
        <div class="conversation-list">
            <div class="conversation-list-header">
                <h3><?php _e('Conversations', 'dealroom-extension'); ?></h3>
                <div class="search-box">
                    <input type="text" id="conversation-search" placeholder="<?php _e('Search', 'dealroom-extension'); ?>">
                    <span class="dashicons dashicons-search"></span>
                </div>
            </div>
            
            <div class="conversation-list-content">
                <?php if (empty($conversations)) : ?>
                    <div class="empty-conversations">
                        <div class="empty-state-icon">
                            <span class="dashicons dashicons-email"></span>
                        </div>
                        <p><?php _e('No conversations yet', 'dealroom-extension'); ?></p>
                    </div>
                <?php else : ?>
                    <ul class="conversations">
                        <?php foreach ($conversations as $conversation) : ?>
                            <li class="conversation-item" data-user-id="<?php echo esc_attr($conversation['user_id']); ?>" data-deal-id="<?php echo esc_attr($conversation['deal'] ? $conversation['deal']['id'] : ''); ?>">
                                <div class="conversation-avatar">
                                    <img src="<?php echo esc_url($conversation['avatar']); ?>" alt="<?php echo esc_attr($conversation['display_name']); ?>">
                                    <?php if ($conversation['unread_count'] > 0) : ?>
                                        <span class="unread-badge"><?php echo esc_html($conversation['unread_count']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-content">
                                    <div class="conversation-header">
                                        <h4 class="user-name"><?php echo esc_html($conversation['display_name']); ?></h4>
                                        <span class="conversation-time"><?php echo DealRoom_Utilities::time_ago($conversation['last_message_time']); ?></span>
                                    </div>
                                    <div class="conversation-preview">
                                        <?php echo esc_html(DealRoom_Utilities::truncate($conversation['last_message'], 40)); ?>
                                    </div>
                                    <?php if ($conversation['deal']) : ?>
                                        <div class="conversation-deal">
                                            <span class="dashicons dashicons-portfolio"></span>
                                            <span class="deal-name"><?php echo esc_html($conversation['deal']['title']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Message Pane -->
        <div class="message-pane">
            <!-- Initial state or empty state -->
            <div class="empty-message-pane" id="empty-message-pane">
                <div class="empty-state-icon">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <h3><?php _e('Select a conversation', 'dealroom-extension'); ?></h3>
                <p><?php _e('Choose a conversation from the list to start messaging.', 'dealroom-extension'); ?></p>
            </div>
            
            <!-- Active conversation interface (initially hidden) -->
            <div class="active-conversation" id="active-conversation" style="display: none;">
                <div class="conversation-header">
                    <div class="conversation-user">
                        <img src="" alt="" id="active-user-avatar" class="user-avatar">
                        <div class="user-info">
                            <h3 id="active-user-name"></h3>
                            <span id="active-deal-info"></span>
                        </div>
                    </div>
                </div>
                
                <div class="message-list" id="message-list">
                    <!-- Messages will be loaded here dynamically -->
                    <div class="loading-messages">
                        <div class="spinner"></div>
                        <p><?php _e('Loading messages...', 'dealroom-extension'); ?></p>
                    </div>
                </div>
                
                <div class="message-compose">
                    <form id="message-form">
                        <textarea id="message-text" placeholder="<?php _e('Type your message...', 'dealroom-extension'); ?>"></textarea>
                        <button type="submit" id="send-message">
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>