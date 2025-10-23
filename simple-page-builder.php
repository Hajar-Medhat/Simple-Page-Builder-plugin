<?php
/**
 * Plugin Name: Simple Page Builder
 * Description: Creates bulk pages through a secure REST API with API-key management, rate limiting, logging, and webhook notifications.
 * Version: 1.0.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create required database tables when plugin activates.
 */
function spb_create_tables() {
    global $wpdb;

    // === API Keys Table ===
    $table_name = $wpdb->prefix . 'spb_api_keys';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        key_name VARCHAR(100) NOT NULL,
        api_key_hash TEXT NOT NULL,
        secret_hash TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        revoked TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME DEFAULT NULL,
        last_used DATETIME DEFAULT NULL,
        request_count BIGINT(20) DEFAULT 0,
        permissions TEXT DEFAULT 'create_pages',
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // === API Logs Table (optional, for activity tracking) ===
    $logs_table = $wpdb->prefix . 'spb_api_logs';
    $sql2 = "CREATE TABLE IF NOT EXISTS $logs_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        api_key_id BIGINT(20) UNSIGNED DEFAULT NULL,
        api_key_name VARCHAR(100) DEFAULT NULL,
        endpoint VARCHAR(191) DEFAULT NULL,
        status VARCHAR(20) DEFAULT NULL,
        pages_created INT DEFAULT 0,
        response_time FLOAT DEFAULT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        message TEXT DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta( $sql2 );
}
register_activation_hook( __FILE__, 'spb_create_tables' );

/**
 * Include all plugin files.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-auth.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-api-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-rate-limit.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-webhook.php';

/**
 * Initialize plugin components.
 */
function spb_init_plugin() {
    $auth       = new SPB_Auth();
    $rate_limit = new SPB_Rate_Limit();
    $logger     = new SPB_Logger();
    $webhook    = new SPB_Webhook();

    new SPB_API_Handler( $auth, $rate_limit, $logger, $webhook );
    new SPB_Admin( $auth );
}
add_action( 'plugins_loaded', 'spb_init_plugin' );
