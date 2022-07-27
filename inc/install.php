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

function gfdn_install() {

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $gfdn_table = "CREATE TABLE {$wpdb->prefix}gfdn_notifs (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
		form_id INT(10),
        entry_id INT(10),
        notification_id VARCHAR(200),
        config LONGTEXT,
        datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    dbDelta( $gfdn_table );

}
?>
