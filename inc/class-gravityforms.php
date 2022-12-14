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

function gfdn_gravityforms() {

	return GFDN_GravityForms::get_instance();

}

$gfdn_gravityforms = new GFDN_GravityForms;
$gfdn_gravityforms->init();

class GFDN_GravityForms {

    /**
	 * Contains an instance of the current class, if available.
	 *
	 * @access private
	 * @var GFDN_GravityForms $_instance If available, contains an instance of this class
	 */
	private static $_instance = null;

    /**
	 * Attach required filters and actions needed to run the plugin in relation to Gravity Forms.
     *
     * @return void
	 */
    public function init() {

        add_filter( 'gform_notification_settings_fields', array( $this, 'notification_settings' ), 10, 3 );

        add_filter( 'gform_tooltips', array( $this, 'tooltips' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

        add_filter( 'gform_pre_send_email', array( $this, 'prevent_email_send_schedule_notif' ), 10, 4 );

        // add_action( 'wp', array( $this, 'send_notifications_cron' ) );

    }

    /**
	 * If the next cron job is due, get the existing notifications and send them
	 * afterwards reschedule or delete them
     *
	 * @return void
	 */
    function send_notifications_cron() {

        \GFDN_Service::send_notifications_cron();

    }

    /**
	 * Check if the entry notification is configured to be delayed and schedule it or send it.
	 *
	 * @param array $email An array containing all the email details, such as email to address, subject, message, headers, attachments and abort email flag.
	 *          'to', 'subject', 'message', 'headers', 'attachments', 'abort_email'
	 * @param string $message_format The message format: html or text.
	 * @param array $notification The notification object.
	 * @param array $entry The entry object.
	 *
	 * @return array
	 */
    function prevent_email_send_schedule_notif( $email, $message_format, $notification, $entry ) {

        //If there is not delay configured for the current notification, send it
        if( ( isset( $notification['delayType'] ) && $notification['delayType'] == 'none' ) || ! $notification['delayType'] ) {
            return $email;
        }

        global $wpdb;

        $get_delayed_notifications = $this->get_scheduled_delayed_notification( $notification, $entry, 'all' );

        if( $get_delayed_notifications ) {

            //Check notif, send it and reschedule if conditions are met.

            $send_notif_delay = $this->get_notification_delay( $notification );

            if( $send_notif_delay ) {

                switch( $send_notif_delay['delay_data']['type'] ) {

                    case 'delay':

                        if(
                            isset( $send_notif_delay['delay_data']['repeats'] ) &&
                            $send_notif_delay['delay_data']['repeats'] &&
                            isset( $notification['delayRepeatTimes'] ) &&
                            $notification['delayRepeatTimes']
                        ) {

                            $get_delayed_notification = $this->get_scheduled_delayed_notification( $notification, $entry, 'delay' );

                            foreach( $get_delayed_notification as $notif ) {

                                $notif_config = $notif->config ? unserialize( $notif->config ) : array();

                                if( $notif_config && $notif_config['sent'] + 1 >= $send_notif_delay['delay_data']['repeats'] ) {

                                    //Delete notif as repeat times has been fulfilled

                                    $wpdb->delete(
                                        $wpdb->prefix . 'gfdn_notifs',
                                        array(
                                            'id' => $notif->id
                                        )
                                    );

                                } else {

                                    //Update notif and reschedule delay

                                    $wpdb->update(
                                        $wpdb->prefix . 'gfdn_notifs',
                                        array(
                                            'config' => serialize( array(
                                                'send' => $send_notif_delay['delay'],
                                                'data' => $send_notif_delay['delay_data'],
                                                'sent' => $notif_config ? $notif_config['sent'] + 1 : 1
                                            ) )
                                        ),
                                        array(
                                            'id'              => $notif->id,
                                            'form_id'         => $entry['form_id'],
                                            'entry_id'        => $entry['id'],
                                            'notification_id' => $notification['id'],
                                        )
                                    );

                                }

                            }

                            //Send email
                            return $email;

                        }

                    break;
                    case 'date':

                        $get_delayed_notification = $this->get_scheduled_delayed_notification( $notification, $entry, 'date' );

                        foreach( $get_delayed_notification as $notif ) {

                            $notif_config = $notif->config ? unserialize( $notif->config ) : array();


                            if( $notif_config && strtotime( $notif_config['send'] ) <= time() ) {

                                $wpdb->delete(
                                    $wpdb->prefix . 'gfdn_notifs',
                                    array(
                                        'id' => $notif->id
                                    )
                                );

                            }

                        }

                        //Send email
                        return $email;

                    break;

                }

            }

        } else {

            //Schedule delayed notif

            global $wpdb;

            $send_notif_delay = $this->get_notification_delay( $notification );

            if( $send_notif_delay ) {

                switch( $send_notif_delay['delay_data']['type'] ) {

                    case 'delay':

                        $wpdb->insert(
                            $wpdb->prefix . 'gfdn_notifs',
                            array(
                                'form_id'         => $entry['form_id'],
                                'entry_id'        => $entry['id'],
                                'notification_id' => $notification['id'],
                                'notification_type' => 'delay',
                                'config'          => serialize( array(
                                    'send' => $send_notif_delay['delay'],
                                    'data' => $send_notif_delay['delay_data'],
                                    'sent' => 0
                                ) )
                            )
                        );

                    break;

                    case 'date':

                        //Add main notif

                        foreach( $send_notif_delay['notifications'] as $send ) {

                            $wpdb->insert(
                                $wpdb->prefix . 'gfdn_notifs',
                                array(
                                    'form_id'         => $entry['form_id'],
                                    'entry_id'        => $entry['id'],
                                    'notification_id' => $notification['id'],
                                    'notification_type' => 'date',
                                    'config'          => serialize( array(
                                        'send' => $send,
                                        'data' => array( 'type' => 'date' )
                                    ) )
                                )
                            );

                        }

                    break;

                }

            }

        }

        //By default, abort

        $email['abort_email'] = true;
		return $email;

    }

    /**
	 * Get the corresponding notification data
	 *
	 * @param array $notification The notification object.
	 *
	 * @return array
	 */
    function get_notification_delay( $notification ) {

        $delay_data = array();
        $delay = 'none';

        $repeat = isset( $notification['delayEnableRepeat'] ) && $notification['delayEnableRepeat'] ? 1 : 0;

        switch( $notification['delayType'] ) {

            case 'delay':

                $offset = isset( $notification['delayOffset'] ) && $notification['delayOffset'] ? $notification['delayOffset'] : 0;
                $offset_unit = isset( $notification['delayOffsetUnit'] ) && $notification['delayOffsetUnit'] ? $notification['delayOffsetUnit'] : 0;

                if( $offset && $offset_unit ) {

                    $delay = date('Y-m-d h:i:s a', strtotime( '+' . $offset . ' ' . $offset_unit ) );

                    $delay_data = array(
                        'type'        => 'delay',
                        'offset'      => $offset,
                        'offset_unit' => $offset_unit
                    );

                }

                $repeat_times = isset( $notification['delayRepeatTimes'] ) && $notification['delayRepeatTimes'] ? $notification['delayRepeatTimes'] : 0;

                if( $repeat && $repeat_times ) {
                    $delay_data['repeats'] = $repeat_times;
                }

                return array(
                    'delay'      => $delay,
                    'delay_data' => $delay_data
                );

            break;
            case 'date':

                $notifications = array();

                $delay_date = isset( $notification['delayDate'] ) && $notification['delayDate'] ? $notification['delayDate'] : 0;
                $delay_hour = isset( $notification['delayHour'] ) && $notification['delayHour'] ? $notification['delayHour'] : '12';
                $delay_min  = isset( $notification['delayMinute'] ) && $notification['delayMinute'] ? $notification['delayMinute'] : '00';
                $delay_ampm = isset( $notification['delayAmPm'] ) && $notification['delayAmPm'] ? $notification['delayAmPm'] : '00';

                if( $delay_date ) {

                    $send = sprintf( '%s %s:%s:00 %s', $delay_date, $delay_hour, $delay_min, $delay_ampm );

                    $notifications[] = $send;
                    // $delay_data = array(
                    // $notifications[] = array(
                    //     'type' => 'date',
                    //     'send' => $send,
                        // 'date' => $delay_date,
                        // 'hour' => $delay_hour,
                        // 'min'  => $delay_min,
                        // 'ampm' => $delay_ampm
                    // );

                }

                if( isset( $notification['delayRepeatDate'] ) && $notification['delayRepeatDate'] ) {

                    // $repeats = array();
                    foreach( $notification['delayRepeatDate'] as $i => $date ) {

                        $delay_date = $date;
                        $delay_hour = isset( $notification['delayRepeatHour'][$i] ) && $notification['delayRepeatHour'][$i] ? $notification['delayRepeatHour'][$i] : '12';
                        $delay_min  = isset( $notification['delayRepeatMinute'][$i] ) && $notification['delayRepeatMinute'][$i] ? $notification['delayRepeatMinute'][$i] : '00';
                        $delay_ampm = isset( $notification['delayRepeatAmPm'][$i] ) && $notification['delayRepeatAmPm'][$i] ? $notification['delayRepeatAmPm'][$i] : 'am';

                        $send = sprintf( '%s %s:%s:00 %s', $delay_date, $delay_hour, $delay_min, $delay_ampm );

                        $notifications[] = $send;

                        // $repeats[] = array(
                        // $notifications[] = array(
                        //     'type' => 'date',
                        //     'send' => $send
                            // 'date'   => $date,
                            // 'hour'   => isset( $notification['delayRepeatHour'][$i] ) && $notification['delayRepeatHour'][$i] ? $notification['delayRepeatHour'][$i] : '12',
                            // 'minute' => isset( $notification['delayRepeatMinute'][$i] ) && $notification['delayRepeatMinute'][$i] ? $notification['delayRepeatMinute'][$i] : '00',
                            // 'ampm'   => isset( $notification['delayRepeatAmPm'][$i] ) && $notification['delayRepeatAmPm'][$i] ? $notification['delayRepeatAmPm'][$i] : 'am'
                        // );

                    }

                    // if( $repeat && $repeats ) {
                    //     $delay_data['repeats'] = $repeats;
                    // }

                }

                return array(
                    'delay_data'    => array( 'type' => 'date' ),
                    'notifications' => $notifications
                );

            break;

        }

    }

    /**
	 * Get the queried delayed notification database data
	 *
	 * @return array
	 */
    function get_scheduled_delayed_notification( $notification, $entry, $type = 'all' ) {

        global $wpdb;

        $suffix = '';

        if( $type != 'all' ) {
            $suffix = " AND notification_type='{$type}'";
        }

        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gfdn_notifs WHERE form_id={$entry['form_id']} AND entry_id={$entry['id']} AND notification_id='{$notification['id']}'" . $suffix );

    }

    /**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @return GFDN_GravityForms $_instance An instance of the GFDN_GravityForms class
	 * @access public
	 * @static
	 */
	public static function get_instance() {

		if ( self::$_instance === null ) {
			self::$_instance = new GFDN_GravityForms();
		}

		return self::$_instance;

	}

    /**
	 * Enqueue required assets
	 *
	 * @return void
	 */
    public function scripts() {

        //Check if user is on the correspondig page. Eg.: /admin.php?page=gf_edit_forms&view=settings&subview=notification&id=<id>&nid=<hash>

        $pages = array(
            'gf_edit_forms' => array(
                // 'page'      => 'gf_edit_forms',
                'view'      => 'settings',
                'subview'   => 'notification',
                'id'        => 'exists'
            ),
            'gf_settings' => array(
                // 'page'      => 'gf_settings',
                'subview'   => 'delay-notifs-gf'
            )
        );

        if( isset( $_GET['page'] ) && isset( $pages[ $_GET['page'] ] ) ) {

            $errors = 0;

            $conditions = $pages[ $_GET['page'] ];

            foreach( $conditions as $get => $value ) {

                if( $value == 'exists' ) {
                    if( ! isset( $_GET[ $get ] ) ) {
                        $errors = 1;
                        break;
                    }
                } elseif( ! isset( $_GET[ $get ] ) || $_GET[ $get ] != $value ) {
                    $errors = 1;
                    break;
                }

            }

        }

        if( isset( $errors ) && ! $errors ) {

            wp_enqueue_style( 'gfdn-admin', GFDNURL . 'assets/css/gfdn-admin.css' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_script( 'gfdn-admin', GFDNURL . 'assets/js/gfdn-admin.js', array( 'jquery' ), null, true );
            wp_localize_script( 'gfdn-admin', 'gfdnAdm', array(
                'ndbd' => \GFDN_Service::get_repeat_by_date_fields(),
                's'    => array(
                    'cdde' => __( 'Are you sure you wish to delete?', 'delay-notifs-gf' )
                )
            ));
        }

    }

    /**
	 * Get admin settings delay types
	 *
	 * @return array
	 */
    public function get_types() {

        return apply_filters( 'gfdn_types', array(
            'none'  => __( 'None', 'delay-notifs-gf' ),
            'delay' => __( 'Delay', 'delay-notifs-gf' ),
            'date'  => __( 'Specific date', 'delay-notifs-gf' )
        ) );

    }

    /**
	 * Get admin settings units
	 *
	 * @return array
	 */
    public function get_units() {

        return apply_filters( 'gfdn_units', array(
			'minutes' => __( 'minute(s)', 'delay-notifs-gf' ),
			'hours'   => __( 'hour(s)', 'delay-notifs-gf' ),
			'days'    => __( 'day(s)', 'delay-notifs-gf' ),
			'weeks'   => __( 'week(s)', 'delay-notifs-gf' ),
			'months'  => __( 'month(s)', 'delay-notifs-gf' ),
			'years'   => __( 'year(s)', 'delay-notifs-gf' ),
		) );

    }

    /**
	 * Get admin settings numeric choices
	 *
	 * @return array
	 */
    public function get_numeric_choices( $min, $max ) {

		$choices = array();

		for ( $i = $min; $i <= $max; $i ++ ) {
			$choices[] = array(
				'label' => $i,
				'value' => $i,
			);
		}

		return $choices;

	}

    /**
	 * Add specific delayer settings to individual notifications.
	 *
	 * @param array $fields Form settings
	 * @param array $notification The notification
	 * @param array $form   The form
	 */
	public function notification_settings( $settings, $notification, $form ) {

		require_once( GFDNPATH . 'inc/gravityforms/field-delay.php' );

		array_splice( $settings[0]['fields'], count( $settings[0]['fields'] ) - 2, 0, array(
			array(
				'name'    => 'delay',
				'label'   => esc_html__( 'Delay send', 'delay-notifs-gf' ),
				'type'    => 'delay_notif',
				'tooltip' => 'delay_notif',
			),
		) );

		return $settings;

	}

    /**
	 * @param $tooltips
	 *
	 * @return array
	 */
	public function tooltips( $tooltips ) {

		$tooltips['delay_notif'] = sprintf( '<h6>%s</h6> %s', __( 'Delay notification', 'delay-notifs-gf' ), __( 'Configure a delay for this notification instead of sending it as soon as the form gets submitted', 'delay-notifs-gf' ) );

        $tooltips['delay_notif_repeat'] = sprintf( '<h6>%s</h6> %s', __( 'Repeat delayed notification', 'delay-notifs-gf' ), __( 'Set how many times you would like to send the same notification. It will be sent at the same interval as per the configuration above.', 'delay-notifs-gf' ) );

		return $tooltips;

	}

}

$gfdn_gravityforms_addon = new GFDN_GravityForms_AddOn;

class GFDN_GravityForms_AddOn extends \GFAddOn {

    /**
	 * Contains an instance of this class, if available.
	 *
	 * @since 0.9
	 * @access private
	 * @var GFDN_GravityForms_AddOn $_instance If available, contains an instance of this class
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Delay Notifications Gravity Forms
	 *
	 * @since 0.9
	 * @access protected
	 * @var string $_version Contains the version.
	 */
	protected $_version = GFDNVERS;

	/**
	 * Defines the minimum Gravity Forms version required.
	 * @since 0.9
	 * @access protected
	 * @var string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.5';

	/**
	 * Defines the plugin slug.
	 *
	 * @since 0.9
	 * @access protected
	 * @var string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'delay-notifs-gf';

	/**
	 * Defines the main plugin file.
	 *
	 * @since 0.9
	 * @access protected
	 * @var string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'delay-notifs-gf/delay-notifs-gf.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since 0.9
	 * @access protected
	 * @var string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this add-on can be found.
	 *
	 * @since 0.9
	 * @access protected
	 * @var string
	 */
	protected $_url = 'https://www.tudorache.me';

	/**
	 * Defines the title of this add-on.
	 *
	 * @since 0.9
	 * @access protected
	 * @var string $_title The title of the add-on.
	 */
	protected $_title = 'Gravity Forms Notification Delay Add-On';

	/**
	 * Defines the short title of the add-on.
	 *
	 * @since 0.9
	 * @access protected
	 * @var string $_short_title The short title.
	 */
	protected $_short_title = 'Notifications Delay';

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @return GFDN_GravityForms_AddOn $_instance An instance of the GP_Notification_Scheduler class
	 * @since 0.9
	 * @access public
	 * @static
	 */
	public static function get_instance() {
		if ( self::$_instance === null ) {
			self::$_instance = new GFDN_GravityForms_AddOn();
		}

		return self::$_instance;
	}

    /**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return 'dashicons-clock';
	}

    /**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
    public function plugin_settings_fields() {

        $settings = get_option( 'gravityformsaddon_delay-notifs-gf_settings' );
        $next_run = get_option( 'gfdn_cron_next' );
        $desc     = '';
        if( $settings['type'] == 'local' && $next_run ) {
            $desc = sprintf( __( 'The next cron will run at %s', 'delay-notifs-gf'), '<strong>' . date('d.m.Y - H:i:s', strtotime( $next_run ) ) . '</strong>' );
        }
        if( $settings['type'] == 'remote' ) {
            $desc = sprintf( __( 'Send a GET request to %s/wp-json/gfdn/v1/rcj', 'delay-notifs-gf' ), site_url() );
        }

		return array(
			array(
				'title'         => esc_html__( 'Notifications delay settings', 'delay-notifs-gf' ),
				'save_callback' => function () {},
				'description'   => $desc,
				'fields'        => array(
                    array(
                        'name'                => 'type',
                        'tooltip'             => esc_html__( 'Set the type of cron job you would like the notifications cron job to run at.', 'delay-notifs-gf' ),
                        'label'               => esc_html__( 'Cron job type', 'delay-notifs-gf' ),
                        'type'                => 'select',
                        'class'               => 'medium',
                        'choices'             => array(
							array(
								'label'   => esc_html__( 'Local site cron', 'delay-notifs-gf' ),
								'value'   => 'local'
							),
                            array(
								'label'   => esc_html__( 'Remote site cron', 'delay-notifs-gf' ),
								'value'   => 'remote'
							),
                        )
                    ),
                    array(
                        'name'                => 'interval',
                        'tooltip'             => esc_html__( 'Set the time interval you would like the notifications cron job to run at.', 'delay-notifs-gf' ),
                        'label'               => esc_html__( 'Cron time interval', 'delay-notifs-gf' ),
                        'type'                => 'select',
                        'class'               => 'medium',
                        'choices'             => array(
							array(
								'label'   => esc_html__( 'One second', 'delay-notifs-gf' ),
								'value'   => 'one_second'
							),
                            array(
								'label'   => esc_html__( 'One minute', 'delay-notifs-gf' ),
								'value'   => 'one_minute'
							),
                            array(
								'label'   => esc_html__( 'Five minutes', 'delay-notifs-gf' ),
								'value'   => 'five_minutes'
							),
                            array(
								'label'   => esc_html__( 'Ten minutes', 'delay-notifs-gf' ),
								'value'   => 'ten_minutes'
							),
                            array(
								'label'   => esc_html__( 'Half Hour', 'delay-notifs-gf' ),
								'value'   => 'half_hour'
							),
                            array(
								'label'   => esc_html__( 'Hourly', 'delay-notifs-gf' ),
								'value'   => 'hourly'
							),
                            array(
								'label'   => esc_html__( 'Twice daily', 'delay-notifs-gf' ),
								'value'   => 'twicedaily'
							),
                            array(
								'label'   => esc_html__( 'Daily', 'delay-notifs-gf' ),
								'value'   => 'daily'
							),
                            array(
								'label'   => esc_html__( 'Weekly', 'delay-notifs-gf' ),
								'value'   => 'weekly'
							)
                        )
                    ),
					array(
						'id'       => 'save_button',
						'type'     => 'save',
						'value'    => esc_attr__( 'Update Notification Delay Settings', 'delay-notifs-gf' ),
						'messages' => array(
							'save'  => esc_html__( 'Saved successfully', 'delay-notifs-gf' ),
							'error' => esc_html__( 'There was an unexpected error saving the settings', 'delay-notifs-gf' )
						)
					)
				)
			)
		);

	}

}
?>
