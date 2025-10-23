<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SPB_Admin {

    private $auth;

    public function __construct( $auth ) {
        $this->auth = $auth;

        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_post_spb_generate_key', [ $this, 'handle_generate_key' ] );
        add_action( 'admin_post_spb_revoke_key', [ $this, 'handle_revoke_key' ] );
        add_action( 'admin_post_spb_save_webhook', [ $this, 'handle_save_webhook' ] );
    }

    /**
     * ✅ Add the plugin menu directly in the main sidebar
     */
    public function add_menu_page() {
        add_menu_page(
            'Simple Page Builder',                // Page title
            'Page Builder',                      // Menu title
            'manage_options',                    // Capability
            'spb-settings',                      // Menu slug
            [ $this, 'render_admin_page' ],      // Callback
            'dashicons-admin-page',              // Icon (WordPress dashicon)
            25                                   // Position in sidebar
        );
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $active_tab = $_GET['tab'] ?? 'keys';
        $keys = $this->auth->get_api_keys();
        ?>
        <div class="wrap">
            <h1>Simple Page Builder</h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=spb-settings&tab=keys" class="nav-tab <?php echo $active_tab === 'keys' ? 'nav-tab-active' : ''; ?>">API Keys</a>
                <a href="?page=spb-settings&tab=webhook" class="nav-tab <?php echo $active_tab === 'webhook' ? 'nav-tab-active' : ''; ?>">Webhook Settings</a>
            </h2>

            <?php if ( $active_tab === 'keys' ) : ?>

                <h2>Generate New API Key</h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'spb_generate_key' ); ?>
                    <input type="hidden" name="action" value="spb_generate_key" />
                    <label for="key_name">Key Name:</label>
                    <input type="text" name="key_name" id="key_name" required />
                    <button class="button button-primary" type="submit">Generate</button>
                </form>

                <?php if ( isset( $_GET['new_key'] ) ) : ?>
                    <div class="notice notice-success is-dismissible">
                        <p><strong>API Key Created!</strong></p>
                        <p><strong>API Key:</strong> <?php echo esc_html( $_GET['api_key'] ); ?></p>
                        <p><strong>Secret:</strong> <?php echo esc_html( $_GET['secret'] ); ?></p>
                        <p>Save these credentials securely — they won’t be shown again.</p>
                    </div>
                <?php endif; ?>

                <hr />

                <h2>Existing API Keys</h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Used</th>
                            <th>Requests</th>
                            <th>Actions</th>
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
                                    <td><?php echo esc_html( $key->request_count ?? 0 ); ?></td>
                                    <td>
                                        <?php if ( $key->status !== 'revoked' ) : ?>
                                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                                <?php wp_nonce_field( 'spb_revoke_key' ); ?>
                                                <input type="hidden" name="action" value="spb_revoke_key" />
                                                <input type="hidden" name="key_id" value="<?php echo intval( $key->id ); ?>" />
                                                <button class="button" type="submit" onclick="return confirm('Revoke this API key?');">Revoke</button>
                                            </form>
                                        <?php else : ?>
                                            <em>Revoked</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="7">No API keys found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ( $active_tab === 'webhook' ) : ?>

                <?php
                $settings = get_option( 'spb_settings', [] );
                $url = $settings['webhook_url'] ?? '';
                $secret = $settings['webhook_secret'] ?? '';
                ?>
                <h2>Webhook Settings</h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'spb_save_webhook' ); ?>
                    <input type="hidden" name="action" value="spb_save_webhook" />

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="webhook_url">Webhook URL</label></th>
                            <td><input type="url" name="webhook_url" id="webhook_url" class="regular-text" value="<?php echo esc_attr( $url ); ?>" placeholder="https://webhook.site/your-id" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="webhook_secret">Webhook Secret</label></th>
                            <td><input type="text" name="webhook_secret" id="webhook_secret" class="regular-text" value="<?php echo esc_attr( $secret ); ?>" placeholder="mysecret123" /></td>
                        </tr>
                    </table>

                    <p>
                        <button class="button button-primary" type="submit" name="save_webhook">Save Webhook Settings</button>
                        <button class="button" type="submit" name="test_webhook" value="1">Send Test Webhook</button>
                    </p>
                </form>

                <?php if ( isset( $_GET['saved'] ) ) : ?>
                    <div class="notice notice-success is-dismissible"><p>Webhook settings saved successfully.</p></div>
                <?php elseif ( isset( $_GET['test_sent'] ) ) : ?>
                    <div class="notice notice-success is-dismissible"><p>✅ Test webhook sent successfully! Check your Webhook.site page.</p></div>
                <?php elseif ( isset( $_GET['test_failed'] ) ) : ?>
                    <div class="notice notice-error is-dismissible"><p>❌ Test webhook failed to send. Please verify your Webhook URL.</p></div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_generate_key() {
        check_admin_referer( 'spb_generate_key' );
        $key_name = sanitize_text_field( $_POST['key_name'] ?? 'Unnamed Key' );
        $new_key  = $this->auth->generate_api_key( $key_name );

        $url = add_query_arg( [
            'page'     => 'spb-settings',
            'new_key'  => 1,
            'api_key'  => urlencode( $new_key['api_key'] ),
            'secret'   => urlencode( $new_key['secret'] ),
        ], admin_url( 'admin.php' ) );

        wp_redirect( $url );
        exit;
    }

    public function handle_revoke_key() {
        check_admin_referer( 'spb_revoke_key' );
        $key_id = intval( $_POST['key_id'] ?? 0 );
        $this->auth->revoke_api_key( $key_id );

        wp_redirect( admin_url( 'admin.php?page=spb-settings&tab=keys' ) );
        exit;
    }

    public function handle_save_webhook() {
        check_admin_referer( 'spb_save_webhook' );

        $settings = get_option( 'spb_settings', [] );
        $settings['webhook_url']    = esc_url_raw( $_POST['webhook_url'] ?? '' );
        $settings['webhook_secret'] = sanitize_text_field( $_POST['webhook_secret'] ?? '' );
        update_option( 'spb_settings', $settings );

        // ✅ Send test webhook if requested
        if ( isset( $_POST['test_webhook'] ) ) {
            $webhook = new SPB_Webhook();
            $result = $webhook->send_test_webhook();

            if ( is_wp_error( $result ) ) {
                wp_redirect( admin_url( 'admin.php?page=spb-settings&tab=webhook&test_failed=1' ) );
            } else {
                wp_redirect( admin_url( 'admin.php?page=spb-settings&tab=webhook&test_sent=1' ) );
            }
            exit;
        }

        wp_redirect( admin_url( 'admin.php?page=spb-settings&tab=webhook&saved=1' ) );
        exit;
    }
}
