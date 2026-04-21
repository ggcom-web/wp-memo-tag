/* global wcMemoTag, jQuery */
( function ( $ ) {
    'use strict';

    /**
     * Checkout – Gestion de l'inversion Propriétaire (Livraison) / Facturation
     */
    function initCheckoutLogic() {
        if ( $( '.woocommerce-checkout' ).length === 0 ) {
            return;
        }

        // 1. Renommer le titre/toggle de livraison en facturation
        var $shipToDifferent = $( '#ship-to-different-address' );
        if ( $shipToDifferent.length > 0 ) {
            var $label = $shipToDifferent.find( 'span' );
            if ( $label.length === 0 ) {
                // Parfois c'est juste du texte dans le label
                $shipToDifferent.find( 'label' ).contents().filter( function() {
                    return this.nodeType === 3 && $.trim( this.nodeValue ).length > 0;
                } ).first().replaceWith( ' ' + 'L\'adresse de facturation est différente' );
            } else {
                $label.text( 'L\'adresse de facturation est différente' );
            }
        }

        // 2. Déplacer la facturation après la livraison pour que le toggle paraisse logique
        // Note: La facturation est normalement en premier (.col-1) et la livraison en second (.col-2).
        var $billingSection  = $( '.woocommerce-billing-fields' );
        var $shippingSection = $( '.woocommerce-shipping-fields' );
        var $shippingFields  = $( '.shipping_address' ); // Le conteneur qui s'affiche/masque

        if ( $billingSection.length && $shippingSection.length ) {
            // On déplace les champs de facturation à l'intérieur du conteneur de "shipping" 
            // ou on les lie au toggle.
            // Pour faire simple et propre : on masque la facturation et on la montre si le checkbox est coché.
            
            // On ajoute un champ caché pour dire au PHP si on doit synchroniser
            if ( $( '#billing_is_different' ).length === 0 ) {
                $shippingSection.append( '<input type="hidden" name="billing_is_different" id="billing_is_different" value="0">' );
            }

            var $checkbox = $( '#ship-to-different-address-checkbox' );
            
            var updateBillingVisibility = function() {
                if ( $checkbox.is( ':checked' ) ) {
                    $billingSection.slideDown();
                    $( '#billing_is_different' ).val( '1' );
                } else {
                    $billingSection.slideUp();
                    $( '#billing_is_different' ).val( '0' );
                }
            };

            $checkbox.on( 'change', updateBillingVisibility );
            
            // État initial : décoché par défaut pour la facturation (donc masqué)
            // Mais WC force souvent le "ship to different" à true si on veut voir les champs.
            // Ici on a forcé ship_to_different à true en PHP pour voir les "Coordonnées du propriétaire".
            // Donc on veut que notre checkbox "Facturation différente" soit décochée au départ.
            $checkbox.prop( 'checked', false );
            $billingSection.hide();
        }
    }

    /**
     * Page produit – validation description avant ajout au panier
     */
    function initProductValidation() {
        $( 'form.cart' ).on( 'submit', function ( e ) {
            var $input = $( '#memo_tag_description' );
            if ( $input.length === 0 ) {
                return;
            }

            if ( $.trim( $input.val() ) === '' ) {
                e.preventDefault();
                $input.addClass( 'woocommerce-invalid' );

                var $notice = $( '<ul class="woocommerce-error"><li>' + wcMemoTag.required_description + '</li></ul>' );
                $input.closest( '.memo-tag-product-fields' ).find( '.woocommerce-error' ).remove();
                $input.closest( '.memo-tag-product-fields' ).prepend( $notice );

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

    // Init
    $( function () {
        initCheckoutLogic();
        initProductValidation();
    } );

    // Réinit après update du checkout (ajax)
    $( document.body ).on( 'updated_checkout', function () {
        initCheckoutLogic();
    } );

} ( jQuery ) );
