<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SPB_Webhook {
    public function __construct( $logger = null ) {}
    public function send_webhook( $event = '', $api_key_name = '', $pages = [] ) {}
}
