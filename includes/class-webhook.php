<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SPB_Webhook {

    protected $logger;

    public function __construct( $logger = null ) {
        $this->logger = $logger;
    }

    /**
     * Send a webhook event to the configured URL.
     *
     * @param string $event Event name, e.g. "pages_created".
     * @param string $api_key_name The name of the API key that triggered the event.
     * @param array  $pages Array of created pages with id, title, url.
     */
    public function send_webhook( $event = '', $api_key_name = '', $pages = [] ) {
        $settings = get_option( 'spb_settings', [] );
        $webhook_url = isset( $settings['webhook_url'] ) ? esc_url_raw( $settings['webhook_url'] ) : '';
        $secret      = isset( $settings['webhook_secret'] ) ? sanitize_text_field( $settings['webhook_secret'] ) : '';

        if ( empty( $webhook_url ) ) {
            if ( $this->logger ) {
                $this->logger->log_webhook_failure( $event, '(no URL set)', 'Missing webhook URL' );
            }
            return false;
        }

        $payload = [
            'event'        => $event,
            'timestamp'    => current_time( 'c' ),
            'request_id'   => uniqid( 'req_', true ),
            'api_key_name' => $api_key_name,
            'total_pages'  => count( $pages ),
            'pages'        => $pages,
        ];

        $signature = ! empty( $secret )
            ? hash_hmac( 'sha256', wp_json_encode( $payload ), $secret )
            : '';

        $headers = [
            'Content-Type'         => 'application/json',
            'X-Webhook-Signature'  => $signature,
        ];

        $max_attempts = 3;
        $success = false;
        $error_message = '';

        for ( $attempt = 1; $attempt <= $max_attempts; $attempt++ ) {
            $response = wp_remote_post( $webhook_url, [
                'headers' => $headers,
                'body'    => wp_json_encode( $payload ),
                'timeout' => 10,
            ] );

            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
            } else {
                $status_code = wp_remote_retrieve_response_code( $response );
                if ( $status_code >= 200 && $status_code < 300 ) {
                    $success = true;
                    break;
                } else {
                    $error_message = 'HTTP ' . $status_code;
                }
            }

            // exponential backoff: 2s → 4s → 8s
            sleep( pow( 2, $attempt ) );
        }

        if ( $this->logger ) {
            if ( $success ) {
                $this->logger->log_webhook_success( $event, $webhook_url );
            } else {
                $this->logger->log_webhook_failure( $event, $webhook_url, $error_message );
            }
        }

        return $success;
    }

    /**
     * 🔹 Send a simple test webhook manually (used by admin page)
     */
    public function send_test_webhook() {
        $settings = get_option( 'spb_settings', [] );
        $webhook_url = isset( $settings['webhook_url'] ) ? esc_url_raw( $settings['webhook_url'] ) : '';
        $secret      = isset( $settings['webhook_secret'] ) ? sanitize_text_field( $settings['webhook_secret'] ) : '';

        if ( empty( $webhook_url ) ) {
            return new WP_Error( 'missing_webhook_url', __( 'Webhook URL not set.', 'simple-page-builder' ) );
        }

        $payload = [
            'event'     => 'test_event',
            'timestamp' => current_time( 'c' ),
            'message'   => '✅ This is a test webhook from Simple Page Builder.',
            'site'      => get_bloginfo( 'name' ),
            'admin'     => wp_get_current_user()->user_email,
        ];

        $signature = ! empty( $secret )
            ? hash_hmac( 'sha256', wp_json_encode( $payload ), $secret )
            : '';

        $headers = [
            'Content-Type'        => 'application/json',
            'X-Webhook-Signature' => $signature,
        ];

        $response = wp_remote_post( $webhook_url, [
            'headers' => $headers,
            'body'    => wp_json_encode( $payload ),
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 200 && $code < 300 ) {
            return true;
        }

        return new WP_Error( 'webhook_failed', 'HTTP ' . $code, [ 'response' => $response ] );
    }
}
