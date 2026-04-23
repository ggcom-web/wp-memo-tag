<?php
// Source - https://stackoverflow.com/a/49440642
// Posted by LoicTheAztec, modified by community. See post 'Timeline' for change history
// Retrieved 2026-04-23, License - CC BY-SA 3.0

// Replace add to cart button by a linked button to the product in Shop and archives pages
add_filter( 'woocommerce_loop_add_to_cart_link', 'replace_loop_add_to_cart_button', 10, 2 );
function replace_loop_add_to_cart_button( $button, $product  ) {
    // Not needed for variable products
    if( $product->is_type( 'variable' ) ) return $button;

    // Button text here
    $button_text = __( "Voir le produit", "wc-memo-tag" );
    $button_container = '<div data-block-name="woocommerce/product-button" data-font-size="small" data-is-descendent-of-query-loop="true" data-is-inherited="1" data-style="{&quot;spacing&quot;:{&quot;margin&quot;:{&quot;bottom&quot;:&quot;1rem&quot;}}}" data-text-align="center" class="wp-block-button wc-block-components-product-button   align-center wp-block-woocommerce-product-button has-small-font-size" data-wp-context="{&quot;quantityToAdd&quot;:1,&quot;productId&quot;:16,&quot;productType&quot;:&quot;simple&quot;,&quot;addToCartText&quot;:&quot;Lire la suite&quot;,&quot;tempQuantity&quot;:0,&quot;animationStatus&quot;:&quot;IDLE&quot;,&quot;inTheCartText&quot;:&quot;### dans le panier&quot;,&quot;noticeId&quot;:&quot;&quot;,&quot;hasPressedButton&quot;:false}">';
    $button_container .= '<a class="wp-block-button__link wp-element-button wc-block-components-product-button__button product_type_simple has-font-size has-small-font-size
    has-text-align-center wc-interactive" style="margin-bottom:1rem;" href="' . $product->get_permalink() . '" rel="nofollow" 
    data-product_id="' . $product->get_id() . '" 
    aria-label="' . $button_text . ' “' . $product->get_title() . '”" data-wp-on--click="woocommerce/product-collection::actions.viewProduct">
    <span>' . $button_text . '</span>
    </a>';
    $button_container .= '</div>';

    return $button_container;
    
}