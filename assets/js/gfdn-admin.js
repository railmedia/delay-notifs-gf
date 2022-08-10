(function($){

    const GFDNAdmin = {

        init: function() {

            jQuery('.gfdn-datepicker').each(function(i, dp){
                jQuery(this).datepicker({
                    dateFormat: 'yy-mm-dd'
    		    });
            });

            if( jQuery('#gfdn-enable-repeat').is(':checked') ) {
                jQuery('#gfdn-repeat-section').show();
            } else {
                jQuery('#gfdn-repeat-section').hide();
            }

            if( jQuery('input[name="_gform_setting_delayType"]:checked').val() == 'delay' ) {
                jQuery('#gfdn-repeat-delay').show();
            } else {
                jQuery('#gfdn-repeat-delay').hide();
            }

            if( jQuery('input[name="_gform_setting_delayType"]:checked').val() == 'date' ) {
                jQuery('#gfdn-repeat-date').show();
            } else {
                jQuery('#gfdn-repeat-date').hide();
            }

        },
        onDelayTypeChange: function( option ) {

            jQuery('.gfdn-type-settings').hide();

            let value = option.val();
            jQuery('#gfdn-' + value + '-settings').show();

            if( value == 'delay' ) {
                jQuery('#gfdn-repeat-delay').show();
            } else {
                jQuery('#gfdn-repeat-delay').hide();
            }

            if( value == 'date' ) {
                jQuery('#gfdn-repeat-date').show();
            } else {
                jQuery('#gfdn-repeat-date').hide();
            }

        },
        onEnableRepeatChange: function( option ) {

            if( option.is(':checked') ) {
                jQuery('#gfdn-repeat-section').show();
            } else {
                jQuery('#gfdn-repeat-section').hide();
            }

        }

    }

    jQuery(document).ready(function() {

        GFDNAdmin.init();

        jQuery('#gfdn-delay-types input[name="_gform_setting_delayType"]').on('change', function(){
            GFDNAdmin.onDelayTypeChange( jQuery(this) );
        });

        jQuery('#gfdn-enable-repeat').on('change', function(){
            GFDNAdmin.onEnableRepeatChange( jQuery(this) );
        });

    });

})(jQuery);
