var $ = jQuery.noConflict();

$(window).load(function(){
    $('#np_primary_category_id').on( 'click', function() {

        var el = $(this),
            postvars = {
                'action': 'primary_category_selector',
                'value' : el.val(),
                'ids' : el.attr("data-ids"),
                'security' : $('#np_pc_selector_nonce').val()
            };

        $.post( ajaxurl, postvars, function( response ) {
            if ( response.html ) {
                el.html( response.html );
                el.attr( 'data-ids', response.ids );
            }
        });
    });
});