<?php
/**
 * Simple Auth class for local testing.
 * Accepts a fixed API key and secret to allow REST API testing
 * without needing the database key system.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SPB_Auth {

    /**
     * Hardcoded credentials for testing
     */
    private $valid_key    = 'test_key';
    private $valid_secret = 'test_secret';

    /**
     * Validate the provided API credentials.
     *
     * @param string $api_key  The provided API key from the request header.
     * @param string $secret   The provided secret from the request header.
     *
     * @return array|WP_Error
     */
    public function validate_request( $api_key, $secret ) {

        // Basic validation: ensure headers were passed
        if ( empty( $api_key ) || empty( $secret ) ) {
            return new WP_Error(
                'spb_missing_headers',
                __( 'Missing authentication headers.', 'simple-page-builder' ),
                [ 'status' => 401 ]
            );
        }

        // Check if the credentials match the valid test pair
        if ( $api_key === $this->valid_key && $secret === $this->valid_secret ) {
            return [
                'valid'     => true,
                'key_id'    => 1,
                'key_name'  => 'Local Test Key',
            ];
        }

        // Invalid credentials
        return new WP_Error(
            'spb_auth_failed',
            __( 'Invalid API credentials.', 'simple-page-builder' ),
            [ 'status' => 401 ]
        );
    }
}
