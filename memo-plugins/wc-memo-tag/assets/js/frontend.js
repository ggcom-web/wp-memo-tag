/* global wcMemoTag, jQuery */
( function ( $ ) {
    'use strict';

    // ----------------------------------------------------------------
    // Checkout – toggle adresse du détenteur
    // ----------------------------------------------------------------
    function toggleHolderAddress() {
        var $checkbox = $( '#memo_tag_different_holder' );
        var $fields   = $( '#memo-tag-holder-address-fields' );

        if ( $checkbox.length === 0 ) {
            return;
        }

        $checkbox.on( 'change', function () {
            if ( $( this ).is( ':checked' ) ) {
                $fields.slideDown( 300 );
            } else {
                $fields.slideUp( 300 );
            }
        } );

        // État initial
        if ( $checkbox.is( ':checked' ) ) {
            $fields.show();
        }
    }

    // ----------------------------------------------------------------
    // Page produit – validation description avant ajout au panier
    // ----------------------------------------------------------------
    function initProductValidation() {
        $( 'form.cart' ).on( 'submit', function ( e ) {
            var $textarea = $( '#memo_tag_description' );
            if ( $textarea.length === 0 ) {
                return; // Pas un produit Memo Tag
            }

            if ( $.trim( $textarea.val() ) === '' ) {
                e.preventDefault();
                $textarea.addClass( 'woocommerce-invalid' );

                var $notice = $( '<ul class="woocommerce-error"><li>' + wcMemoTag.required_description + '</li></ul>' );
                $textarea.closest( '.memo-tag-product-fields' ).prepend( $notice );

                $( 'html, body' ).animate( {
                    scrollTop: $notice.offset().top - 100
                }, 400 );
            }
        } );

        $( '#memo_tag_description' ).on( 'input', function () {
            if ( $.trim( $( this ).val() ) !== '' ) {
                $( this ).removeClass( 'woocommerce-invalid' );
                $( '.memo-tag-product-fields .woocommerce-error' ).remove();
            }
        } );
    }

    // ----------------------------------------------------------------
    // Init
    // ----------------------------------------------------------------
    $( function () {
        toggleHolderAddress();
        initProductValidation();
    } );

    // Réinit après update du checkout (ajax)
    $( document.body ).on( 'updated_checkout', function () {
        toggleHolderAddress();
    } );

} ( jQuery ) );
