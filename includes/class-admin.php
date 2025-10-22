<?php
/**
 * Admin UI for managing API keys in Simple Page Builder.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SPB_Admin {

    private $auth;

    public function __construct( $auth ) {
        $this->auth = $auth;

        // Add Admin Menu
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
    }

    /**
     * Add menu page under Settings.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Simple Page Builder', 'simple-page-builder' ),
            __( 'Page Builder', 'simple-page-builder' ),
            'manage_options',
            'spb-admin',
            [ $this, 'render_admin_page' ],
            'dashicons-admin-generic',
            65
        );
    }

    /**
     * Render the admin page UI.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle key generation
        if ( isset( $_POST['spb_generate_key'] ) && ! empty( $_POST['key_name'] ) ) {
            check_admin_referer( 'spb_generate_key_action' );
            $result = $this->auth->generate_api_key( sanitize_text_field( $_POST['key_name'] ) );
            echo '<div class="updated"><p><strong>New API Key Created!</strong></p>';
            echo '<p><strong>API Key:</strong> ' . esc_html( $result['api_key'] ) . '<br>';
            echo '<strong>Secret:</strong> ' . esc_html( $result['secret'] ) . '</p>';
            echo '<p><em>' . esc_html( $result['message'] ) . '</em></p></div>';
        }

        // Handle revocation
        if ( isset( $_GET['revoke_key'] ) && is_numeric( $_GET['revoke_key'] ) ) {
            $this->auth->revoke_api_key( intval( $_GET['revoke_key'] ) );
            echo '<div class="updated"><p>API key revoked.</p></div>';
        }

        $keys = $this->auth->get_api_keys();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Simple Page Builder – API Keys', 'simple-page-builder' ); ?></h1>

            <h2>Generate New Key</h2>
            <form method="post">
                <?php wp_nonce_field( 'spb_generate_key_action' ); ?>
                <label for="key_name">Key Name:</label>
                <input type="text" name="key_name" required />
                <button type="submit" name="spb_generate_key" class="button button-primary">Generate</button>
            </form>

            <hr>

            <h2>Existing Keys</h2>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Key Name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Used</th>
                        <th>Requests</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $keys ) ) : ?>
                        <?php foreach ( $keys as $key ) : ?>
                            <tr>
                                <td><?php echo esc_html( $key->id ); ?></td>
                                <td><?php echo esc_html( $key->key_name ); ?></td>
                                <td><?php echo esc_html( ucfirst( $key->status ) ); ?></td>
                                <td><?php echo esc_html( $key->created_at ); ?></td>
                                <td><?php echo esc_html( $key->last_used ?? '-' ); ?></td>
                                <td><?php echo esc_html( $key->request_count ); ?></td>
                                <td>
                                    <?php if ( ! $key->revoked ) : ?>
                                        <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'spb-admin', 'revoke_key' => $key->id ] ) ); ?>" class="button">Revoke</a>
                                    <?php else : ?>
                                        Revoked
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="7">No keys found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
