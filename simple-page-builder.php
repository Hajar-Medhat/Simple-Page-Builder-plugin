<?php
/**
 * Plugin Name: Simple Page Builder
 * Description: Create bulk pages via secure REST API with API key authentication and webhook notifications.
 * Version: 1.0.0
 * Author: Hajar Medhat
 * Text Domain: simple-page-builder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Define constants
 */
define( 'SPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload required files
 */
require_once SPB_PLUGIN_DIR . 'includes/class-auth.php';
require_once SPB_PLUGIN_DIR . 'includes/class-api-handler.php';
require_once SPB_PLUGIN_DIR . 'includes/class-admin.php';
require_once SPB_PLUGIN_DIR . 'includes/class-logger.php';
require_once SPB_PLUGIN_DIR . 'includes/class-webhook.php';
require_once SPB_PLUGIN_DIR . 'includes/class-rate-limit.php';
require_once SPB_PLUGIN_DIR . 'includes/helpers.php';
require_once SPB_PLUGIN_DIR . 'includes/class-admin.php';

/**
 * Initialize the plugin
 */
function spb_init_plugin() {
    $auth       = new SPB_Auth();
    $rate_limit = new SPB_Rate_Limit();
    $logger     = new SPB_Logger();
    $webhook    = new SPB_Webhook( $logger );
    $api        = new SPB_API_Handler( $auth, $rate_limit, $logger, $webhook );
    $admin      = new SPB_Admin( $auth, $logger, $rate_limit, $webhook );

    // Do NOT call register_routes() manually
    $admin->init_admin_menu();
}
add_action( 'plugins_loaded', 'spb_init_plugin', 20 );
