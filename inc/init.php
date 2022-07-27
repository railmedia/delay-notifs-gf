<?php
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

// Set includes
$files = array(
    'class-gravityforms.php' => array(),
    'updates.php'            => array()
);

//Include files
foreach( $files as $file => $classes ) {

    require_once( __DIR__ . '/' . $file );
    if( $classes ) {
        foreach( $classes as $class ) {
            new $class;
        }
    }

}

?>
