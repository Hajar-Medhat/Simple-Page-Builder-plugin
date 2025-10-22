<?php
/**
 * Plugin Name: Simple Page Builder
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-auth.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-api-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-rate-limit.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-webhook.php';

function spb_init_plugin() {
    $auth       = new SPB_Auth();
    $rate_limit = new SPB_Rate_Limit();
    $logger     = new SPB_Logger();
    $webhook    = new SPB_Webhook();

    // Initialize API and Admin
    new SPB_API_Handler( $auth, $rate_limit, $logger, $webhook );
    new SPB_Admin( $auth );
}
add_action( 'plugins_loaded', 'spb_init_plugin' );
