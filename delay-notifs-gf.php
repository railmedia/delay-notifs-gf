<?php
/*
* Plugin Name: Delay Notifications for Gravity Forms
* Plugin URI: https://www.cloudweb.ch
* Description: Provides extended functionality for Gravity Forms notifications in terms of delaying the notifications sent by the plugin
* Version: 1.1.1
* Author: Adrian Emil Tudorache
* Author URI: https://www.tudorache.me
* Text Domain: delay-notifs-gf
* Domain Path: /languages
* License: GPLv2 or later
*/

/**
* @package Delay Notifications for Gravity Forms
* @author  Adrian Emil Tudorache
* @license GPL-2.0+
* @link    https://www.tudorache.me/
**/

namespace GF_Delay_Notifs;

if ( ! defined( 'ABSPATH' ) ) {
    header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
    exit;
}

if( in_array( 'gravityforms/gravityforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && class_exists( 'GFAddOn' ) ) {

    define( 'GFDNVER', '1.1' );
    define( 'GFDNPATH', plugin_dir_path( __FILE__ ) );
    define( 'GFDNBASE', plugin_basename( __FILE__ ) );
    define( 'GFDNURL', plugin_dir_url( __FILE__ ) );
    define( 'GFDNVERS', '1.0.0' );

    load_plugin_textdomain( 'delay-notifs-gf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    require_once( __DIR__ . '/inc/init.php' );

    require_once( __DIR__ . '/inc/install.php' );

    function gfdn_activate_install() {
        gfdn_install();
        flush_rewrite_rules();
    }
    register_activation_hook( __FILE__, __NAMESPACE__ . '\gfdn_activate_install' );

    require_once( __DIR__ . '/inc/uninstall.php' );

    function gfdn_deactivate_uninstall() {
        gfdn_uninstall();
        flush_rewrite_rules();
    }
    register_uninstall_hook( __FILE__, __NAMESPACE__ . '\gfdn_deactivate_uninstall' );

} else {

    $active_plugins = get_option( 'active_plugins' );

    $deactivate = 0;

    foreach( $active_plugins as $key => $active_plugin ) {

        if( $active_plugin == 'delay-notifs-gf/delay-notifs-gf.php' ) {
            unset( $active_plugins[ $key ] );
            $deactivate = 1;
            break;
        }

    }

    if( $deactivate ) {
        update_option( 'active_plugins', $active_plugins, true );
    }

}
?>
