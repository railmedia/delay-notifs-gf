<?php
/**
* @package Delay Notifications for Gravity Forms
* @author  Adrian Emil Tudorache
* @license GPL-2.0+
* @link    https://www.tudorache.me/
**/

namespace GF_Delay_Notifs;

class GFDN_REST {

    function __construct() {
        add_action( 'rest_api_init', array( $this, 'routes' ) );
        add_action( 'wp', array( $this, 'test' ) );
    }

    function test() {
        \GFDN_Service::send_notifications_cron();
    }

    function routes() {

        $namespace = 'gfdn/v1';

        register_rest_route( $namespace, '/rcj', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array( $this, 'run_cron_job' ),
			// 'permission_callback' => function() {
            //     return current_user_can( 'manage_options' );
            // },
			// 'permission_callback' => '__return_true',
            // 'args' => array(
            //     'channels' => array(
			// 		'type'     => 'array',
			// 		'required' => true,
			// 		'validate_callback' => function( $value, $request, $param ) {
            //             return is_array( $value );
            //         }
            //         // 'sanitize_callback' => function( $value, $request, $param ) {
            //         //     return sanitize_text_field( $request['amount'] );
            //         // }
            //     )
            // )
        ) );

    }

    function run_cron_job( $request ) {

        $sent = \GFDN_Service::send_notifications_cron();

        return new \WP_REST_Response( array(
            'msg' => 'Sent',
            'sent' => $sent
        ) );

    }

}
?>
