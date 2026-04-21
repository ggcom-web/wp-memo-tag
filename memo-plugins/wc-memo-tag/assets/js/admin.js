/* global jQuery */
( function ( $ ) {
    'use strict';

    $( function () {

        /**
         * Ajouter show_if_memo_tag aux champs de prix (et leur options_group).
         * WooCommerce vérifie la visibilité des .options_group pour décider
         * si le panel Général (et donc son onglet) doit être masqué.
         * Doit être fait avant le re-trigger ci-dessous.
         */
        $(
            '._regular_price_field,' +
            '._sale_price_field,' +
            '.pricing_product_data,' +
            '._virtual_field,' +
            '._downloadable_field'
        ).addClass( 'show_if_memo_tag' );

        /**
         * Si le type courant est memo_tag, re-déclencher le change WooCommerce
         * (avec le sélecteur exact utilisé par WooCommerce : select#product_type).
         * WooCommerce a déjà calculé la visibilité AVANT notre DOMReady,
         * sans connaître nos classes show_if_memo_tag — on force un recalcul.
         */
        var $typeSelect = $( 'select#product_type' );
        if ( $typeSelect.val() === 'memo_tag' ) {
            $typeSelect.trigger( 'change' );
        }

    } );

} ( jQuery ) );
