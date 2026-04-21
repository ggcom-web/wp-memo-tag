<?php
/**
 * Sauvegarde et affichage des données Memo Tag dans la commande.
 *
 * @package WC_Memo_Tag
 */

defined( 'ABSPATH' ) || exit;

class WC_Memo_Tag_Order {

    public static function init() {
        // Copie des données du panier vers les meta de l'order item
        add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'save_order_item_meta' ], 10, 4 );

        // Sauvegarde de l'adresse du détenteur dans l'order
        add_action( 'woocommerce_checkout_update_order_meta',       [ __CLASS__, 'save_holder_address_meta' ] );

        // Affichage des meta dans l'admin de la commande
        add_action( 'woocommerce_after_order_itemmeta',             [ __CLASS__, 'display_order_item_meta' ], 10, 3 );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', [ __CLASS__, 'display_holder_address_admin' ] );

        // Affichage dans l'email de confirmation
        add_filter( 'woocommerce_order_item_get_formatted_meta_data', [ __CLASS__, 'format_order_item_meta' ], 10, 2 );
    }

    // ----------------------------------------------------------------
    // Sauvegarde
    // ----------------------------------------------------------------

    /**
     * Transfert les données du cart item vers l'order line item.
     *
     * @param WC_Order_Item_Product $item
     * @param string                $cart_item_key
     * @param array                 $values
     * @param WC_Order              $order
     */
    public static function save_order_item_meta(
        WC_Order_Item_Product $item,
        string $cart_item_key,
        array $values,
        WC_Order $order
    ) {
        if ( ! empty( $values['memo_tag_description'] ) ) {
            $item->add_meta_data(
                __( 'Description Memo Tag', 'wc-memo-tag' ),
                sanitize_textarea_field( $values['memo_tag_description'] ),
                true
            );
        }
    }

    /**
     * Sauvegarde l'adresse du détenteur dans les meta de la commande.
     *
     * @param int $order_id
     */
    public static function save_holder_address_meta( int $order_id ) {
        if ( ! isset( $_POST['memo_tag_different_holder'] ) ) {
            return;
        }

        $fields = array_keys( WC_Memo_Tag_Checkout::get_holder_address_fields() );

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta(
                    $order_id,
                    '_' . $field,
                    sanitize_text_field( wp_unslash( $_POST[ $field ] ) )
                );
            }
        }

        update_post_meta( $order_id, '_memo_tag_different_holder', 'yes' );
    }

    // ----------------------------------------------------------------
    // Affichage admin
    // ----------------------------------------------------------------

    /**
     * Affiche les meta de l'order item dans l'admin.
     *
     * @param int            $item_id
     * @param WC_Order_Item  $item
     * @param WC_Product     $product
     */
    public static function display_order_item_meta( int $item_id, $item, $product ) {
        if ( ! $product || 'memo_tag' !== $product->get_type() ) {
            return;
        }
        // WooCommerce affiche automatiquement les meta ajoutées via add_meta_data.
    }

    /**
     * Affiche l'adresse du détenteur dans le bloc "Expédition" de l'admin.
     *
     * @param WC_Order $order
     */
    public static function display_holder_address_admin( WC_Order $order ) {
        $order_id   = $order->get_id();
        $is_diff    = get_post_meta( $order_id, '_memo_tag_different_holder', true );

        if ( 'yes' !== $is_diff ) {
            return;
        }

        $fields = [
            '_memo_tag_holder_first_name' => __( 'Prénom', 'wc-memo-tag' ),
            '_memo_tag_holder_last_name'  => __( 'Nom', 'wc-memo-tag' ),
            '_memo_tag_holder_address_1'  => __( 'Adresse', 'wc-memo-tag' ),
            '_memo_tag_holder_address_2'  => __( 'Complément', 'wc-memo-tag' ),
            '_memo_tag_holder_postcode'   => __( 'Code postal', 'wc-memo-tag' ),
            '_memo_tag_holder_city'       => __( 'Ville', 'wc-memo-tag' ),
            '_memo_tag_holder_country'    => __( 'Pays', 'wc-memo-tag' ),
        ];

        echo '<h3>' . esc_html__( 'Adresse du détenteur Memo Tag', 'wc-memo-tag' ) . '</h3>';
        echo '<address>';
        foreach ( $fields as $meta_key => $label ) {
            $value = get_post_meta( $order_id, $meta_key, true );
            if ( $value ) {
                echo '<strong>' . esc_html( $label ) . ' :</strong> ' . esc_html( $value ) . '<br>';
            }
        }
        echo '</address>';
    }

    // ----------------------------------------------------------------
    // Emails
    // ----------------------------------------------------------------

    /**
     * S'assure que les meta Memo Tag apparaissent dans les emails de commande.
     */
    public static function format_order_item_meta( array $formatted_meta, WC_Order_Item $item ): array {
        // Les meta ajoutées avec add_meta_data sont déjà formatées par WooCommerce.
        return $formatted_meta;
    }
}
