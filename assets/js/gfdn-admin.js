(function($){

    const GFDNAdmin = {

        init: function() {

            this.initDatepickers();
            this.onCronJobTypeChange( jQuery('#gform_setting_type select#type') );

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
        initDatepickers: function() {

            jQuery('.gfdn-datepicker').each(function(i, dp){
                jQuery(this).datepicker({
                    dateFormat: 'yy-mm-dd'
    		    });
            });

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

        },
        addNewRepeatByDateFieldsSet: function() {
            if( gfdnAdm.ndbd ) {
                jQuery('#gfdn-repeat-date-entries').append( gfdnAdm.ndbd );
                this.initDatepickers();
            }
        },
        removeRepeatByDateFieldsSet: function( row ) {

            if( confirm( gfdnAdm.s.cdde ) === true ) {
                row.parents('.gfdn-repeat-date-entry').remove();
            }

        },
        onCronJobTypeChange: function( select ) {

            console.log(select.val());

            switch( select.val() ) {
                case 'local':
                    jQuery('#gform_setting_interval').show();
                break;
                case 'remote':
                    jQuery('#gform_setting_interval').hide();
                break;
            }

        },
        notificationsScheduleSectionToggle: function() {

            jQuery('.dashicons.gfdn-notifications-schedule-section-toggle').toggleClass('dashicons-arrow-up-alt2');
            jQuery('#gfdn-notifications-schedule-section').slideToggle();

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

        jQuery('#gfdn-add-repeat-by-date').on('click', function(e){
            e.preventDefault();
            GFDNAdmin.addNewRepeatByDateFieldsSet( jQuery(this) );
        });

        jQuery('body').on('click', '.remove-repeat-by-date', function(){
            GFDNAdmin.removeRepeatByDateFieldsSet( jQuery(this) );
        });

        jQuery('body').on('change', '#gform_setting_type select#type', function(){
            GFDNAdmin.onCronJobTypeChange( jQuery(this) );
        });

        jQuery('body').on('click', '.gfdn-notifications-schedule-section-toggle, label[for="gfdn-notifications-schedule-section-toggle"]', function(){
            GFDNAdmin.notificationsScheduleSectionToggle( jQuery(this) );
        });

    });

})(jQuery);
