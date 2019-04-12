var $ = jQuery.noConflict();

$(window).load(function(){

    var npPCSelector = $('#np_primary_category_id');

    $(npPCSelector).on( 'click', function() {

        var postvars = {
                'action': 'primary_category_selector',
                'value' : $(this).val(),
                'security' : $('#np_cp_selector_nonce').val()
        };

        $.post( ajaxurl, postvars, function( response ) {
            npPCSelector.html( response );
        });
    });
});