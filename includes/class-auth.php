<?php
/**
 * SPB_Auth class for local testing and production use.
 * Handles API key generation, validation, revocation, and listing.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SPB_Auth {

    private $valid_key    = 'test_key';
    private $valid_secret = 'test_secret';
    private $is_local     = true;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'spb_api_keys';

        // Detect if the table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;
        $this->is_local = !$table_exists; // If no table, assume local
    }

    /**
     * Generate a new API key (DB-backed for production)
     */
    public function generate_api_key( $key_name ) {
        if ( $this->is_local ) {
            return [
                'api_key' => $this->valid_key,
                'secret'  => $this->valid_secret,
                'message' => __( 'Local test environment: fixed credentials.', 'simple-page-builder' ),
            ];
        }

        global $wpdb;

        $api_key = wp_generate_password( 32, false, false );
        $secret  = wp_generate_password( 64, false, false );

        $wpdb->insert(
            $this->table_name,
            [
                'key_name'     => sanitize_text_field( $key_name ),
                'api_key_hash' => wp_hash_password( $api_key ),
                'secret_hash'  => wp_hash_password( $secret ),
                'status'       => 'active',
                'created_at'   => current_time( 'mysql' ),
            ],
            [ '%s','%s','%s','%s','%s' ]
        );

        return [
            'api_key' => $api_key,
            'secret'  => $secret,
            'message' => __( 'API key generated. Save it securely; you cannot view it again.', 'simple-page-builder' ),
        ];
    }

    /**
     * Validate API credentials
     */
    public function validate_request( $api_key, $secret ) {
        if ( empty( $api_key ) || empty( $secret ) ) {
            return new WP_Error(
                'spb_missing_headers',
                __( 'Missing authentication headers.', 'simple-page-builder' ),
                [ 'status' => 401 ]
            );
        }

        if ( $this->is_local ) {
            if ( $api_key === $this->valid_key && $secret === $this->valid_secret ) {
                return [
                    'valid'    => true,
                    'key_id'   => 1,
                    'key_name' => 'Local Test Key',
                ];
            }
            return new WP_Error(
                'spb_auth_failed',
                __( 'Invalid API credentials.', 'simple-page-builder' ),
                [ 'status' => 401 ]
            );
        }

        // Production validation: check DB
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE status='active' AND revoked=0" );

        foreach ( $rows as $row ) {
            if ( wp_check_password( $api_key, $row->api_key_hash ) &&
                 wp_check_password( $secret, $row->secret_hash ) ) {
                return [
                    'valid'    => true,
                    'key_id'   => $row->id,
                    'key_name' => $row->key_name,
                ];
            }
        }

        return new WP_Error(
            'spb_auth_failed',
            __( 'Invalid API credentials.', 'simple-page-builder' ),
            [ 'status' => 401 ]
        );
    }

    /**
     * Revoke an API key
     */
    public function revoke_api_key( $id ) {
        global $wpdb;

        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;
        if ( ! $table_exists ) {
            return false;
        }

        return $wpdb->update(
            $this->table_name,
            [
                'revoked' => 1,
                'status'  => 'revoked',
            ],
            ['id' => intval($id)],
            ['%d','%s'],
            ['%d']
        );
    }

    /**
     * Get all API keys
     */
    public function get_api_keys() {
        global $wpdb;

        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;
        if ( $table_exists ) {
            return $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY id DESC" );
        }

        // Local fallback key
        return [
            (object)[
                'id' => 1,
                'key_name' => 'Local Test Key',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'last_used' => null,
                'request_count' => 0,
                'revoked' => 0,
            ]
        ];
    }
}
