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

            if( jQuery('input[name="_gform_setting_delayType"]:checked').val() != 'none' ) {
                jQuery('#gfdn-repeat').show();
            } else {
                jQuery('#gfdn-repeat').hide();
            }

        },
        onDelayTypeChange: function( option ) {

            jQuery('.gfdn-type-settings').hide();

            let value = option.val();
            jQuery('#gfdn-' + value + '-settings').show();

            if( value != 'none' ) {
                jQuery('#gfdn-repeat').show();
            } else {
                jQuery('#gfdn-repeat').hide();
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
