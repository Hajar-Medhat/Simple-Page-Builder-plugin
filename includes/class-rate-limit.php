<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SPB_Rate_Limit {
    public function check_limit( $key_id ) {
        return true; // disable limit for now
    }
}
