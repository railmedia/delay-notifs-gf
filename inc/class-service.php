<?php
/**
* @package Delay Notifications for Gravity Forms
* @author  Adrian Emil Tudorache
* @license GPL-2.0+
* @link    https://www.tudorache.me/
**/

// namespace GF_Delay_Notifs;

class GFDN_Service {

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
                printf( '<option value="%s" %s>%s</option>', $value, isset( $notification['delayRepeatMinute'][$i] ) && $notification['delayRepeatMinute'][$i] == $value ? 'selected="selected"' : '', str_pad( $value, 2, '0', STR_PAD_LEFT ) );
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
