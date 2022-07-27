<?php
/**
* @package Delay Notifications for Gravity Forms
* @author  Adrian Emil Tudorache
* @license GPL-2.0+
* @link    https://www.tudorache.me/
**/

if ( ! defined( 'ABSPATH' ) ) {
    header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
    exit;
}

function gfdn_uninstall() {

    global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gfdn_notifs" );

    $options = array();

    foreach( $options as $option ) {
        delete_option( $option );
    }

}
?>
