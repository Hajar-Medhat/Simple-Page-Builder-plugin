<?php
/**
 * Handles REST API endpoint for bulk page creation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SPB_API_Handler {

    private $auth;
    private $rate_limit;
    private $logger;
    private $webhook;

    public function __construct( $auth, $rate_limit, $logger, $webhook ) {
        $this->auth       = $auth;
        $this->rate_limit = $rate_limit;
        $this->logger     = $logger;
        $this->webhook    = $webhook;

        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register the REST route.
     */
    public function register_routes() {
        register_rest_route( 'pagebuilder/v1', '/create-pages', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_pages' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * Handle bulk page creation.
     */
    public function create_pages( WP_REST_Request $request ) {

        // Capture and log all headers (for debugging)
        $headers = $request->get_headers();
        error_log( print_r( $headers, true ) );

        // Support multiple possible header names
        $api_key = $headers['x-api-key'][0]
            ?? $headers['x_api_key'][0]
            ?? $headers['X-Api-Key'][0]
            ?? $headers['x-api-key:'][0]
            ?? '';

        $secret  = $headers['x-api-secret'][0]
            ?? $headers['x_api_secret'][0]
            ?? $headers['X-Api-Secret'][0]
            ?? $headers['x-api-secret:'][0]
            ?? '';

        if ( empty( $api_key ) || empty( $secret ) ) {
            return new WP_REST_Response( [ 'error' => 'Missing authentication headers.' ], 401 );
        }

        // Validate API credentials
        $auth_result = $this->auth->validate_request( $api_key, $secret );

        if ( is_wp_error( $auth_result ) ) {
            $this->logger->log_api_request( 'create-pages', $api_key, 'failed', 0, $auth_result->get_error_message() );
            return new WP_REST_Response(
                [ 'error' => $auth_result->get_error_message() ],
                $auth_result->get_error_data()['status']
            );
        }

        // Optional: Rate limit check (skip if placeholder)
        if ( method_exists( $this->rate_limit, 'check_limit' ) && ! $this->rate_limit->check_limit( $auth_result['key_id'] ) ) {
            $this->logger->log_api_request( 'create-pages', $auth_result['key_name'], 'failed', 0, 'Rate limit exceeded.' );
            return new WP_REST_Response( [ 'error' => 'Rate limit exceeded. Try again later.' ], 429 );
        }

        // Get pages parameter
        $pages = $request->get_param( 'pages' );

        if ( empty( $pages ) || ! is_array( $pages ) ) {
            return new WP_REST_Response( [ 'error' => 'Invalid or missing pages array.' ], 400 );
        }

        $created_pages = [];

        foreach ( $pages as $page ) {
            $title   = sanitize_text_field( $page['title'] ?? '' );
            $content = wp_kses_post( $page['content'] ?? '' );

            if ( empty( $title ) ) {
                continue;
            }

            $new_page = [
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ];

            $page_id = wp_insert_post( $new_page );

            if ( ! is_wp_error( $page_id ) ) {
                $created_pages[] = [
                    'id'    => $page_id,
                    'title' => $title,
                    'url'   => get_permalink( $page_id ),
                ];
            }
        }

        // Prepare the response
        $response = [
            'status'  => 'success',
            'message' => 'Pages created successfully.',
            'created' => count( $created_pages ),
            'pages'   => $created_pages,
        ];

        // Log + webhook
        if ( method_exists( $this->logger, 'log_api_request' ) ) {
            $this->logger->log_api_request( 'create-pages', $auth_result['key_name'], 'success', count( $created_pages ) );
        }

        if ( method_exists( $this->webhook, 'send_webhook' ) ) {
            $this->webhook->send_webhook( 'pages_created', $auth_result['key_name'], $created_pages );
        }

        return new WP_REST_Response( $response, 200 );
    }
}
