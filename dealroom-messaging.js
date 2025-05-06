/**
 * DealRoom Messaging JavaScript
 * 
 * Handles messaging functionality including loading conversations,
 * sending messages, and real-time updates.
 */
document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const conversationList = document.querySelector('.conversation-list-content');
    const emptyMessagePane = document.getElementById('empty-message-pane');
    const activeConversation = document.getElementById('active-conversation');
    const messageList = document.getElementById('message-list');
    const messageForm = document.getElementById('message-form');
    const messageText = document.getElementById('message-text');
    const activeUserName = document.getElementById('active-user-name');
    const activeUserAvatar = document.getElementById('active-user-avatar');
    const activeDealInfo = document.getElementById('active-deal-info');
    const conversationSearch = document.getElementById('conversation-search');
    
    // Active conversation state
    let activeUserId = null;
    let activeDealId = null;
    let lastMessageId = 0;
    let refreshInterval = null;
    let isLoadingMessages = false;
    
    // Check for URL parameters for direct messaging
    const urlParams = new URLSearchParams(window.location.search);
    const urlUserId = urlParams.get('user');
    const urlDealId = urlParams.get('deal');
    
    // Initialize 
    init();
    
    /**
     * Initialize the messaging functionality
     */
    function init() {
        // Set up event listeners for conversation list
        if (conversationList) {
            const conversationItems = conversationList.querySelectorAll('.conversation-item');
            conversationItems.forEach(item => {
                item.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const dealId = this.getAttribute('data-deal-id') || null;
                    loadConversation(userId, dealId);
                    
                    // Update URL but don't reload the page
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('user', userId);
                    if (dealId) {
                        newUrl.searchParams.set('deal', dealId);
                    } else {
                        newUrl.searchParams.delete('deal');
                    }
                    history.pushState({}, '', newUrl);
                    
                    // Highlight the active conversation
                    conversationItems.forEach(conv => conv.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Remove unread badge
                    const unreadBadge = this.querySelector('.unread-badge');
                    if (unreadBadge) {
                        unreadBadge.remove();
                    }
                });
            });
        }
        
        // Set up message form
        if (messageForm) {
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });
        }
        
        // Set up conversation search
        if (conversationSearch) {
            conversationSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const conversations = document.querySelectorAll('.conversation-item');
                
                conversations.forEach(conversation => {
                    const userName = conversation.querySelector('.user-name').innerText.toLowerCase();
                    const preview = conversation.querySelector('.conversation-preview').innerText.toLowerCase();
                    const dealName = conversation.querySelector('.deal-name')?.innerText.toLowerCase() || '';
                    
                    if (userName.includes(query) || preview.includes(query) || dealName.includes(query)) {
                        conversation.style.display = 'flex';
                    } else {
                        conversation.style.display = 'none';
                    }
                });
            });
        }
        
        // Load conversation from URL params if present
        if (urlUserId) {
            const conversationItem = document.querySelector(`.conversation-item[data-user-id="${urlUserId}"]`);
            if (conversationItem) {
                conversationItem.click();
            } else {
                // If no conversation exists but user ID is provided, create a new conversation
                loadConversation(urlUserId, urlDealId);
            }
        }
        
        // Set up auto-refresh for messages
        if (dealroomMessaging.refresh_interval) {
            setInterval(refreshMessages, dealroomMessaging.refresh_interval);
        }
    }
    
    /**
     * Load a conversation
     */
    function loadConversation(userId, dealId = null) {
        // Set active conversation
        activeUserId = userId;
        activeDealId = dealId;
        lastMessageId = 0;
        
        // Show loading state
        emptyMessagePane.style.display = 'none';
        activeConversation.style.display = 'block';
        messageList.innerHTML = `
            <div class="loading-messages">
                <div class="spinner"></div>
                <p>${dealroomMessaging.i18n.loading}</p>
            </div>
        `;
        
        // Clear message input
        messageText.value = '';
        
        // Get user info from the conversation list
        const conversationItem = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
        if (conversationItem) {
            const userName = conversationItem.querySelector('.user-name').innerText;
            const userAvatar = conversationItem.querySelector('img').src;
            const dealName = conversationItem.querySelector('.deal-name')?.innerText || '';
            
            // Update header
            activeUserName.innerText = userName;
            activeUserAvatar.src = userAvatar;
            activeUserAvatar.alt = userName;
            
            if (dealId && dealName) {
                activeDealInfo.innerHTML = `<span class="dashicons dashicons-portfolio"></span> ${dealName}`;
                activeDealInfo.style.display = 'block';
            } else {
                activeDealInfo.style.display = 'none';
            }
        } else {
            // If no conversation exists, get user info from server
            // For now, use a placeholder
            activeUserName.innerText = 'User';
            activeDealInfo.style.display = 'none';
        }
        
        // Load messages
        loadMessages();
        
        // Set up refresh interval
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(refreshMessages, dealroomMessaging.refresh_interval);
    }
    
    /**
     * Load messages for the active conversation
     */
    function loadMessages() {
        if (!activeUserId || isLoadingMessages) return;
        
        isLoadingMessages = true;
        
        const data = new FormData();
        data.append('action', 'dealroom_get_messages');
        data.append('nonce', dealroomMessaging.nonce);
        data.append('user_id', activeUserId);
        if (activeDealId) {
            data.append('deal_id', activeDealId);
        }
        if (lastMessageId) {
            data.append('last_id', lastMessageId);
        }
        
        fetch(dealroomMessaging.ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            isLoadingMessages = false;
            
            if (data.success) {
                displayMessages(data.data.messages);
            } else {
                console.error('Error loading messages:', data.data.message);
                messageList.innerHTML = `
                    <div class="error-message">
                        <p>${dealroomMessaging.i18n.error}: ${data.data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            isLoadingMessages = false;
            console.error('Error loading messages:', error);
            messageList.innerHTML = `
                <div class="error-message">
                    <p>${dealroomMessaging.i18n.error}: ${error.message}</p>
                </div>
            `;
        });
    }
    
    /**
     * Display messages in the message list
     */
    function displayMessages(messages) {
        if (messages.length === 0) {
            if (lastMessageId === 0) {
                // No messages yet
                messageList.innerHTML = `
                    <div class="no-messages">
                        <p>${dealroomMessaging.i18n.noMessages}</p>
                    </div>
                `;
            }
            return;
        }
        
        // If this is the first load, clear the message list
        if (lastMessageId === 0) {
            messageList.innerHTML = '';
        }
        
        // Add messages to the list
        messages.forEach(message => {
            // Update last message ID
            if (message.id > lastMessageId) {
                lastMessageId = message.id;
            }
            
            // Create message element
            const messageElement = document.createElement('div');
            messageElement.className = `message ${message.is_mine ? 'message-outgoing' : 'message-incoming'}`;
            messageElement.setAttribute('data-id', message.id);
            
            messageElement.innerHTML = `
                <div class="message-avatar">
                    <img src="${message.sender_avatar}" alt="${message.sender_name}">
                </div>
                <div class="message-content">
                    <div class="message-sender">${message.sender_name}</div>
                    <div class="message-text">${formatMessageText(message.message)}</div>
                    <div class="message-time">${formatTime(message.created_at)}</div>
                </div>
            `;
            
            messageList.appendChild(messageElement);
        });
        
        // Scroll to bottom
        messageList.scrollTop = messageList.scrollHeight;
        
        // Mark messages as read
        const unreadMessages = messages.filter(message => !message.is_mine && !message.is_read)
            .map(message => message.id);
            
        if (unreadMessages.length > 0) {
            markMessagesAsRead(unreadMessages);
        }
    }
    
    /**
     * Send a message
     */
    function sendMessage() {
        const message = messageText.value.trim();
        
        if (!message || !activeUserId) return;
        
        // Disable the form
        messageText.disabled = true;
        const sendButton = messageForm.querySelector('button[type="submit"]');
        const originalButtonHtml = sendButton.innerHTML;
        sendButton.innerHTML = `<span class="sending-indicator">${dealroomMessaging.i18n.sending}</span>`;
        
        const data = new FormData();
        data.append('action', 'dealroom_send_message');
        data.append('nonce', dealroomMessaging.nonce);
        data.append('recipient_id', activeUserId);
        data.append('message', message);
        if (activeDealId) {
            data.append('deal_id', activeDealId);
        }
        
        fetch(dealroomMessaging.ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the message to the conversation
                displayMessages([data.data.message_data]);
                
                // Update the conversation list
                updateConversationList(data.data.message_data);
                
                // Clear the input
                messageText.value = '';
            } else {
                console.error('Error sending message:', data.data.message);
                alert(dealroomMessaging.i18n.error + ': ' + data.data.message);
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            alert(dealroomMessaging.i18n.error + ': ' + error.message);
        })
        .finally(() => {
            // Re-enable the form
            messageText.disabled = false;
            messageText.focus();
            sendButton.innerHTML = originalButtonHtml;
        });
    }
    
    /**
     * Mark messages as read
     */
    function markMessagesAsRead(messageIds) {
        if (!messageIds.length) return;
        
        const data = new FormData();
        data.append('action', 'dealroom_mark_read');
        data.append('nonce', dealroomMessaging.nonce);
        messageIds.forEach(id => data.append('message_ids[]', id));
        
        fetch(dealroomMessaging.ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error marking messages as read:', data.data.message);
            }
        })
        .catch(error => {
            console.error('Error marking messages as read:', error);
        });
    }
    
    /**
     * Refresh messages for the active conversation
     */
    function refreshMessages() {
        if (activeUserId && lastMessageId) {
            loadMessages();
        }
    }
    
    /**
     * Update the conversation list with a new message
     */
    function updateConversationList(message) {
        // Find the conversation item for this user
        const conversationItem = document.querySelector(`.conversation-item[data-user-id="${message.recipient_id}"]`);
        
        if (conversationItem) {
            // Update the preview text
            const previewElement = conversationItem.querySelector('.conversation-preview');
            previewElement.innerText = message.message.length > 40 ? 
                message.message.substring(0, 40) + '...' : message.message;
            
            // Update the time
            const timeElement = conversationItem.querySelector('.conversation-time');
            timeElement.innerText = 'Just now';
            
            // Move to top of list
            const conversationsList = conversationItem.parentNode;
            conversationsList.insertBefore(conversationItem, conversationsList.firstChild);
        } else {
            // Create a new conversation item
            const newConversation = document.createElement('li');
            newConversation.className = 'conversation-item';
            newConversation.setAttribute('data-user-id', message.recipient_id);
            if (message.deal_id) {
                newConversation.setAttribute('data-deal-id', message.deal_id);
            }
            
            newConversation.innerHTML = `
                <div class="conversation-avatar">
                    <img src="${message.sender_avatar}" alt="${message.sender_name}">
                </div>
                <div class="conversation-content">
                    <div class="conversation-header">
                        <h4 class="user-name">${message.sender_name}</h4>
                        <span class="conversation-time">Just now</span>
                    </div>
                    <div class="conversation-preview">
                        ${message.message.length > 40 ? message.message.substring(0, 40) + '...' : message.message}
                    </div>
                </div>
            `;
            
            // Add to the conversation list
            const conversationsList = document.querySelector('.conversations');
            if (conversationsList) {
                // Remove empty state if present
                const emptyState = conversationsList.querySelector('.empty-conversations');
                if (emptyState) {
                    emptyState.remove();
                }
                
                conversationsList.insertBefore(newConversation, conversationsList.firstChild);
                
                // Add event listener
                newConversation.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const dealId = this.getAttribute('data-deal-id') || null;
                    loadConversation(userId, dealId);
                    
                    // Update URL but don't reload the page
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('user', userId);
                    if (dealId) {
                        newUrl.searchParams.set('deal', dealId);
                    } else {
                        newUrl.searchParams.delete('deal');
                    }
                    history.pushState({}, '', newUrl);
                    
                    // Highlight the active conversation
                    document.querySelectorAll('.conversation-item').forEach(conv => conv.classList.remove('active'));
                    this.classList.add('active');
                });
            }
        }
    }
    
    /**
     * Format message text with links and line breaks
     */
    function formatMessageText(text) {
        // Replace URLs with links
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        const formattedText = text.replace(urlRegex, url => `<a href="${url}" target="_blank">${url}</a>`);
        
        // Replace line breaks with <br>
        return formattedText.replace(/\n/g, '<br>');
    }
    
    /**
     * Format time for messages
     */
    function formatTime(timeStr) {
        const date = new Date(timeStr);
        const now = new Date();
        const diff = now - date;
        
        // Less than a minute
        if (diff < 60000) {
            return 'Just now';
        }
        
        // Less than an hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;
        }
        
        // Less than a day
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
        }
        
        // Less than a week
        if (diff < 604800000) {
            const days = Math.floor(diff / 86400000);
            return `${days} ${days === 1 ? 'day' : 'days'} ago`;
        }
        
        // Format date
        const options = { month: 'short', day: 'numeric', year: now.getFullYear() !== date.getFullYear() ? 'numeric' : undefined };
        return date.toLocaleDateString(undefined, options);
    }
});