<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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

    public function register_routes() {
        register_rest_route( 'pagebuilder/v1', '/create-pages', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_pages' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function create_pages( WP_REST_Request $request ) {

        $pages = $request->get_param( 'pages' );
        if ( empty( $pages ) || ! is_array( $pages ) ) {
            return rest_ensure_response([ 'error' => 'Invalid or missing pages array.' ], 400);
        }

        $created_pages = [];
        foreach ( $pages as $page ) {
            $title   = sanitize_text_field( $page['title'] ?? '' );
            $content = wp_kses_post( $page['content'] ?? '' );
            if ( ! $title ) continue;

            $page_id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);

            if ( ! is_wp_error( $page_id ) ) {
                $created_pages[] = [
                    'id'    => $page_id,
                    'title' => $title,
                    'url'   => get_permalink( $page_id ),
                ];
            }
        }

        $response = [
            'status'  => 'success',
            'message' => 'Pages created successfully.',
            'created' => count( $created_pages ),
            'pages'   => $created_pages,
        ];

        // 🔔 send webhook if pages created
        if ( ! empty( $created_pages ) && method_exists( $this->webhook, 'send_webhook' ) ) {
            try {
                $this->webhook->send_webhook( 'pages_created', 'default-key', $created_pages );
            } catch ( Exception $e ) {
                error_log( 'Webhook error: ' . $e->getMessage() );
            }
        }

        return rest_ensure_response( $response, 200 );
    }
}
