/* WooCommerce Memo Tag – Admin */
( function ( $ ) {
    'use strict';

    $( function () {
        // Afficher/masquer l'onglet Memo Tag selon le type sélectionné
        $( '#product-type' ).on( 'change', function () {
            if ( $( this ).val() === 'memo_tag' ) {
                $( '.show_if_memo_tag' ).show();
                $( '.hide_if_memo_tag' ).hide();
            }
        } ).trigger( 'change' );
    } );

} ( jQuery ) );
