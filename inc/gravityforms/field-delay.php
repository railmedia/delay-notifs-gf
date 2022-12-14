<?php

use Gravity_Forms\Gravity_Forms\Settings\Fields;

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

class GFDN_Settings_Field_Delay extends Gravity_Forms\Gravity_Forms\Settings\Fields\Base {

	/**
	 * Field type.
	 * @var string
	 */
	public $type = 'delay_notif';

	/**
	 * Render field.
	 * @return string
	 */
	public function markup() {

        require_once( GFDNPATH . 'inc/class-service.php' );

        global $wpdb;

		$form         = $this->settings->get_current_form();
		$notification = $this->settings->get_current_values();
		$type         = rgars( $notification, 'delayType', 'none' );

		ob_start();
		?>

		<tr id="gfdn-settings" valign="top">
			<td>
				<div id="gfdn-delay-types">
                    <?php
                        foreach( GF_Delay_Notifs\gfdn_gravityforms()->get_types() as $value => $label ) {

                            printf(
                                '<input type="radio" value="%1$s" name="_gform_setting_delayType" id="gfdn-delayType-%1$s" %3$s />' .
                                '<label for="gfdn-delayType-%1$s">%2$s</label>',
                                $value, $label, checked( $type, $value, false )
                            );
                        }
                    ?>
				</div>

				<div id="gfdn-delay-settings" class="gfdn-type-settings" style="<?php echo $type !== 'delay' ? 'display:none;' : ''; ?>"
				>
					<span><?php esc_html_e( 'Send this notification after', 'delay-notifs-gf' ); ?></span>
					<input type="number" placeholder="" name="_gform_setting_delayOffset" value="<?php echo rgar( $notification, 'delayOffset' ); ?>"
					/>
					<select name="_gform_setting_delayOffsetUnit">
						<?php
						foreach ( GF_Delay_Notifs\gfdn_gravityforms()->get_units() as $value => $label ) {
							printf( '<option value="%s" %s>%s</option>', $value, $notification['delayOffsetUnit'] == $value ? 'selected="selected"' : '', $label );
						}
						?>
					</select>
				</div>
				<div id="gfdn-date-settings" class="gfdn-type-settings" style="<?php echo $type !== 'date' ? 'display:none;' : ''; ?>">
					<span><?php _e( 'Send this notification on', 'delay-notifs-gf' ); ?></span>
					<input type="text" placeholder="yyyy-mm-dd" class="gfdn-datepicker" name="_gform_setting_delayDate" value="<?php echo rgar( $notification, 'delayDate' ); ?>" id="gfdn-delayDate"
					/>
					<i id="gfdn-delayDate-icon" class="fa fa-calendar-check-o" aria-hidden="true"></i>
					<span>at</span>
					<select name="_gform_setting_delayHour">
						<?php
						foreach( GF_Delay_Notifs\gfdn_gravityforms()->get_numeric_choices( 1, 12 ) as $choice ) {
							printf( '<option value="%s" %s>%s</option>', $choice['value'], selected( rgar( $notification, 'delayHour', 12 ), $choice['value'], false ), $choice['label'] );
						}
						?>
					</select>
					<select name="_gform_setting_delayMinute">
						<?php
						foreach ( range( 0, 55, 5 ) as $value ) {
							printf( '<option value="%s" %s>%s</option>', $value < 10 ? 0 . $value : $value, selected( rgar( $notification, 'delayMinute', 0 ), $value, false ), str_pad( $value, 2, '0', STR_PAD_LEFT ) );
						}
						?>
					</select>
					<select name="_gform_setting_delayAmPm">
						<?php
						foreach ( array( 'am', 'pm' ) as $value ) {
							printf( '<option value="%s" %s>%s</option>', $value, selected( rgar( $notification, 'delayAmPm', 'pm' ), $value, false ), $value );
						}
						?>
					</select>
				</div>

                <!-- Add recurring/repeat here -->
                <div id="gfdn-repeat">

                    <input type="checkbox" id="gfdn-enable-repeat" name="_gform_setting_delayEnableRepeat" value="1" <?php echo $notification['delayEnableRepeat'] ? 'checked="checked"' : ''; ?> />
                    <label for="gfdn-enable-repeat">
                        <?php _e( 'Repeat', 'delay-notifs-gf' ); ?></label> <?php gform_tooltip( 'delay_notif_repeat' ); ?>
                    </label>

                    <div id="gfdn-repeat-section" class="gpns-repeat-setting" style="display:none;">
                        <div id="gfdn-repeat-delay">
                            <label for="gfdn-repeat-times"><?php _e( 'How many times', 'delay-notifs-gf' ); ?></label>
                            <input type="number" id="gfdn-repeat-times" name="_gform_setting_delayRepeatTimes" value="<?php echo rgar( $notification, 'delayRepeatTimes' ); ?>" />
                        </div>
                        <div id="gfdn-repeat-date">

                            <div id="gfdn-repeat-date-entries">
                            <?php
                                if( isset( $notification['delayRepeatDate'] ) && $notification['delayRepeatDate'] ) {
                                    foreach( $notification['delayRepeatHour'] as $i => $repeat ) {
                                        echo GFDN_Service::get_repeat_by_date_fields( $notification, $i );
                                    }
                                }
                            ?>
                            </div>

                            <button id="gfdn-add-repeat-by-date" type="button" class="button button-primary primary large add-repeat-by-date"><?php _e( 'Add', 'delay-notifs-gf' ); ?></button>

                        </div>
                    </div>

                </div>

                <?php
                    $notifs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gfdn_notifs WHERE form_id={$form['id']} AND notification_id='{$notification['id']}'" );

                    if( $notifs ) {
                ?>
                <div id="gfdn-notifications-schedule">
                    <!-- <input type="checkbox" id="gfdn-enable-repeat" name="_gform_setting_delayEnableRepeat" value="1" <?php //echo $notification['delayEnableRepeat'] ? 'checked="checked"' : ''; ?> /> -->
                    <span class="dashicons dashicons-arrow-down-alt2 gfdn-notifications-schedule-section-toggle"></span>
                    <label for="gfdn-notifications-schedule-section-toggle">
                        <?php _e( 'Notifications schedule', 'delay-notifs-gf' ); ?></label> <?php gform_tooltip( 'delay_notif_schedule' ); ?>
                    </label>

                    <div id="gfdn-notifications-schedule-section" class="gpns-notification-schedule-setting" style="display:none;">
                        <p><strong><?php printf( __( 'Current server time: %s', 'delay-notifs-gf' ), date('Y-m-d h:i:s a') ); ?></strong></p>
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th scope="col"><?php _e( 'Type', 'delay-notifs-gf' ); ?></th>
                                    <th scope="col"><?php _e( 'Next send date/time', 'delay-notifs-gf' ); ?></th>
                                    <th scope="col"><?php _e( 'Repeat', 'delay-notifs-gf' ); ?></th>
                                    <th scope="col"><?php _e( 'Sent', 'delay-notifs-gf' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                foreach( $notifs as $notif ) {
                                    $config = $notif->config ? unserialize( $notif->config ) : array();

                                    if( ! $config )
                                        continue;
                            ?>
                            <tr>
                                <th scope="col"><?php echo ucfirst( $notif->notification_type ); ?></th>
                                <th scope="col"><?php echo $config['send']; ?></th>
                                <th scope="col"><?php echo $config['data']['repeats'] ? $config['data']['repeats'] : '-'; ?></th>
                                <th scope="col"><?php echo $config['sent'] ? $config['sent'] : '-'; ?></th>
                            </tr>
                            <?php

                                }
                            ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th scope="col"><?php _e( 'Type', 'delay-notifs-gf' ); ?></th>
                                    <th scope="col"><?php _e( 'Send date/time', 'delay-notifs-gf' ); ?></th>
                                    <th scope="col"><?php _e( 'Repeat', 'delay-notifs-gf' ); ?></th>
                                    <th scope="col"><?php _e( 'Sent', 'delay-notifs-gf' ); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <?php } ?>

			</td>
		</tr>
		<?php

		return ob_get_clean();

    }

}

Fields::register( 'delay_notif', 'GFDN_Settings_Field_Delay' );
