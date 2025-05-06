<?php
/**
 * Main dashboard template
 */
defined('ABSPATH') || exit;

$user_role = DealRoom_Utilities::get_user_role();
?>

<div class="dealroom-dashboard">
    <?php 
    // Load role-specific dashboard content
    $template_path = DEALROOM_PATH . 'templates/dashboard-' . $user_role . '.php';
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        // Fallback content
        ?>
        <div class="dealroom-message">
            <p><?php _e('Welcome to DealRoom', 'dealroom'); ?></p>
            <p><?php _e('Please complete your profile to get started.', 'dealroom'); ?></p>
        </div>
        <?php
    }
    ?>
</div>