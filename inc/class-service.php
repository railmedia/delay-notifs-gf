<?php
/**
* @package Delay Notifications for Gravity Forms
* @author  Adrian Emil Tudorache
* @license GPL-2.0+
* @link    https://www.tudorache.me/
**/

// namespace GF_Delay_Notifs;

class GFDN_Service {

    /**
	 * Check if the next run date and time matches the current time
	 *
	 * @return boolean
	 */
    public static function decide_run() {

        $settings = get_option( 'gravityformsaddon_delay-notifs-gf_settings' );
        // var_dump($settings);
        if( isset( $settings['type'] ) && $settings['type'] == 'remote' ) {
            return true;
        }

        $interval = $settings && $settings['interval'] ? $settings['interval'] : 'one_second';

        $next_run = get_option( 'gfdn_cron_next' );

        $next_run_schedule = '';

        switch( $interval ) {

            case 'one_second':
                $next_run_schedule = date( 'Y-m-d h:i:s a', strtotime( '+1 second' ) );
            break;
            case 'one_minute':
                $next_run_schedule = date( 'Y-m-d h:i:s a', strtotime( '+1 minute' ) );
            break;
            case 'five_minutes': default:
                $next_run_schedule = date( 'Y-m-d h:i:s a', strtotime( '+5 minutes' ) );
            break;
            case 'ten_minutes':
                $next_run_schedule = date( 'Y-m-d h:i:s a', strtotime( '+10 minutes' ) );
            break;
            case 'half_hour':
                $next_run_schedule = date( 'Y-m-d h:i:s a', strtotime( '+30 minutes' ) );
            break;
            case 'hourly':
                $next_run_schedule = date( 'Y-m-d h:i:s a', strtotime( '+60 minutes' ) );
            break;
            case 'twicedaily':
                $next_run_schedule = date( 'Y-m-d h:i:s a', strtotime( '+12 hours' ) );
            break;
            case 'daily':
                $next_run_schedule = date( 'Y-m-d h:i:s a', strtotime( '+1 day' ) );
            break;
            case 'weekly':
                $next_run_schedule = date( 'Y-m-d h:i:s a', strtotime( '+7 days' ) );
            break;

        }

        if( ! $next_run || strtotime( $next_run ) < time() ) {

            update_option( 'gfdn_cron_next', $next_run_schedule, true );

            return true;

        }

        return false;

    }

    /**
	 * If the next cron job is due, get the existing notifications and send them
	 * afterwards reschedule or delete them
     *
	 * @return void
	 */
    public static function send_notifications_cron() {

        $run = self::decide_run();

        if( $run ) {

            global $wpdb;

            $get_notifs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gfdn_notifs" );
            if( $get_notifs ) {

                $send_notifs = $notifs_ids = array();
                foreach( $get_notifs as $notif ) {

                    $config = $notif->config;
                    $config = $config ? unserialize( $config ) : array();

                    if( $config && $config['send'] && ( strtotime( $config['send'] ) <= time() ) ) {
                        $send_notifs[] = (array) $notif;
                        if( ! in_array( $notif->notification_id, $notifs_ids ) ) {
                            $notifs_ids[] = $notif->notification_id;
                        }
                    }

                }

                if( $send_notifs && $notifs_ids ) {

                    foreach( $send_notifs as $notif ) {

                        $form = \GFAPI::get_form( $notif['form_id'] );
                        $entry= \GFAPI::get_entry( $notif['entry_id'] );
                        \GFCommon::send_notifications( $notifs_ids, $form, $entry, true, 'delay' );
                        // $notifs_sent = $notifs_sent + 1;
                        // $notifs_ids[] = $notifs_ids;

                    }

                }

            }

        }

        return array(
            'run' => $run,
            'send_notifs' => $send_notifs,
            'notifs_ids' => $notifs_ids
        );

    }

    public static function get_repeat_by_date_fields( $notification = null, $i = 0 ) {

        ob_start();
    ?>
    <div class="gfdn-repeat-date-entry" style="display: flex; align-items: center;">

        <span style="margin-right: 10px;">
            <?php _e( 'On', 'delay-notifs-gf' ); ?>
        </span>

        <input type="text" placeholder="yyyy-mm-dd" class="gfdn-datepicker" name="_gform_setting_delayRepeatDate[]" value="<?php echo isset( $notification['delayRepeatDate'] ) ? $notification['delayRepeatDate'][$i] : ''; ?>" />
        <i id="gfdn-delayDate-icon" class="fa fa-calendar-check-o" aria-hidden="true"></i>

        <span style="margin-right: 10px;">
            <?php _e( 'at', 'delay-notifs-gf' ); ?>
        </span>

        <select name="_gform_setting_delayRepeatHour[]">
            <?php
            foreach( GF_Delay_Notifs\gfdn_gravityforms()->get_numeric_choices( 1, 12 ) as $choice ) {
                printf( '<option value="%s" %s>%s</option>', $choice['value'], isset( $notification['delayRepeatHour'][$i] ) && $notification['delayRepeatHour'][$i] == $choice['value'] ? 'selected="selected"' : '', $choice['label'] );
            }
            ?>
        </select>

        <select name="_gform_setting_delayRepeatMinute[]">
            <?php
            foreach ( range( 0, 55, 5 ) as $value ) {
                printf( '<option value="%s" %s>%s</option>', $value < 10 ? 0 . $value : $value, isset( $notification['delayRepeatMinute'][$i] ) && $notification['delayRepeatMinute'][$i] == $value ? 'selected="selected"' : '', str_pad( $value, 2, '0', STR_PAD_LEFT ) );
            }
            ?>
        </select>

        <select name="_gform_setting_delayRepeatAmPm[]">
            <?php
            foreach ( array( 'am', 'pm' ) as $value ) {
                printf( '<option value="%s" %s>%s</option>', $value, isset( $notification['delayRepeatAmPm'][$i] ) && $notification['delayRepeatAmPm'][$i] == $value ? 'selected="selected"' : '', $value );
            }
            ?>
        </select>

        <span class="remove-repeat-by-date dashicons dashicons-trash"></span>

    </div>
    <?php
        return ob_get_clean();

    }

}
