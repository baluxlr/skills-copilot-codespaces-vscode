<?php
/**
 * Plugin Name: DealRoom
 * Description: A comprehensive investment marketplace platform for managing deals, investors, and entrepreneurs
 * Version: 2.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dealroom
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Define plugin constants
define('DEALROOM_VERSION', '2.0.0');
define('DEALROOM_PATH', plugin_dir_path(__FILE__));
define('DEALROOM_URL', plugin_dir_url(__FILE__));
define('DEALROOM_BASENAME', plugin_basename(__FILE__));

// Load required files
require_once DEALROOM_PATH . 'includes/class-dealroom.php';

/**
 * Returns the main instance of DealRoom
 */
function dealroom() {
    global $dealroom;
    
    if (!isset($dealroom)) {
        $dealroom = DealRoom::instance();
    }
    
    return $dealroom;
}

// Initialize the plugin
$GLOBALS['dealroom'] = dealroom();

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('DealRoom', 'activate'));
register_deactivation_hook(__FILE__, array('DealRoom', 'deactivate'));