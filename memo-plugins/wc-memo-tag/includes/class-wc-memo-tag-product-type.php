<?php
/**
 * Enregistrement du type de produit Memo Tag et de son onglet d'édition.
 *
 * @package WC_Memo_Tag
 */

defined( 'ABSPATH' ) || exit;

class WC_Memo_Tag_Product_Type {

    public static function init() {
        // Déclarer le type auprès de WooCommerce
        add_filter( 'product_type_selector',          [ __CLASS__, 'add_product_type' ] );
        add_filter( 'woocommerce_product_class',      [ __CLASS__, 'map_product_class' ], 10, 2 );

        // Onglet dans l'éditeur produit
        add_filter( 'woocommerce_product_data_tabs',   [ __CLASS__, 'add_product_tab' ] );
        add_action( 'woocommerce_product_data_panels', [ __CLASS__, 'render_product_tab' ] );

        // Contrôle des onglets visibles selon le type
        add_filter( 'woocommerce_product_data_tabs',   [ __CLASS__, 'adjust_tabs_visibility' ], 99 );

        // Sauvegarde des champs custom
        add_action( 'woocommerce_process_product_meta_memo_tag', [ __CLASS__, 'save_product_meta' ] );
    }

    // ----------------------------------------------------------------
    // Enregistrement du type
    // ----------------------------------------------------------------

    /**
     * Ajoute "Memo Tag" dans le sélecteur de type.
     */
    public static function add_product_type( array $types ): array {
        $types['memo_tag'] = __( 'Memo Tag', 'wc-memo-tag' );
        return $types;
    }

    /**
     * Lie le type à la classe produit.
     */
    public static function map_product_class( string $classname, string $product_type ): string {
        if ( 'memo_tag' === $product_type ) {
            return 'WC_Product_Memo_Tag';
        }
        return $classname;
    }

    // ----------------------------------------------------------------
    // Onglet d'édition
    // ----------------------------------------------------------------

    /**
     * Ajoute un onglet dédié au type Memo Tag.
     */
    public static function add_product_tab( array $tabs ): array {
        $tabs['memo_tag'] = [
            'label'    => __( 'Memo Tag', 'wc-memo-tag' ),
            'target'   => 'memo_tag_product_data',
            'class'    => [ 'show_if_memo_tag' ],
            'priority' => 60,
        ];
        return $tabs;
    }

    /**
     * Masque les onglets non pertinents pour ce type de produit.
     */
    public static function adjust_tabs_visibility( array $tabs ): array {
        $hide_for_memo_tag = [ 'shipping', 'linked_product', 'variations', 'advanced' ];

        foreach ( $hide_for_memo_tag as $tab_key ) {
            if ( isset( $tabs[ $tab_key ] ) ) {
                $tabs[ $tab_key ]['class'][] = 'hide_if_memo_tag';
            }
        }
        return $tabs;
    }

    /**
     * Contenu du panneau Memo Tag dans l'éditeur produit.
     */
    public static function render_product_tab() {
        global $post;
        $product_id = $post->ID;
        ?>
        <div id="memo_tag_product_data" class="panel woocommerce_options_panel show_if_memo_tag" style="display:none;">

            <div class="options_group">
                <h4 style="padding-left:12px;"><?php esc_html_e( 'Paramètres du Memo Tag', 'wc-memo-tag' ); ?></h4>

                <?php
                // Description interne (admin) – à ne pas confondre avec celle demandée au client
                woocommerce_wp_textarea_input( [
                    'id'          => '_memo_tag_description',
                    'label'       => __( 'Description par défaut (admin)', 'wc-memo-tag' ),
                    'desc_tip'    => true,
                    'description' => __( 'Texte d'exemple ou instruction visible côté admin. Le client saisira sa propre description lors de la commande.', 'wc-memo-tag' ),
                    'value'       => get_post_meta( $product_id, '_memo_tag_description', true ),
                ] );

                // Autoriser une adresse différente pour le détenteur
                woocommerce_wp_checkbox( [
                    'id'          => '_memo_tag_different_address',
                    'label'       => __( 'Adresse du détenteur', 'wc-memo-tag' ),
                    'desc_tip'    => true,
                    'description' => __( 'Si coché, le client pourra saisir une adresse différente pour le détenteur du Memo Tag.', 'wc-memo-tag' ),
                    'value'       => get_post_meta( $product_id, '_memo_tag_different_address', true ),
                ] );
                ?>
            </div>

            <div class="options_group">
                <?php
                // Prix
                woocommerce_wp_text_input( [
                    'id'        => '_regular_price',
                    'label'     => __( 'Prix régulier', 'wc-memo-tag' ) . ' (' . get_woocommerce_currency_symbol() . ')',
                    'data_type' => 'price',
                ] );

                woocommerce_wp_text_input( [
                    'id'        => '_sale_price',
                    'label'     => __( 'Prix promo', 'wc-memo-tag' ) . ' (' . get_woocommerce_currency_symbol() . ')',
                    'data_type' => 'price',
                ] );
                ?>
            </div>

        </div>
        <?php
    }

    // ----------------------------------------------------------------
    // Sauvegarde
    // ----------------------------------------------------------------

    /**
     * Sauvegarde les meta lors de l'enregistrement du produit.
     *
     * @param int $post_id
     */
    public static function save_product_meta( int $post_id ) {
        // Description
        $description = isset( $_POST['_memo_tag_description'] )
            ? sanitize_textarea_field( wp_unslash( $_POST['_memo_tag_description'] ) )
            : '';
        update_post_meta( $post_id, '_memo_tag_description', $description );

        // Case à cocher adresse différente
        $different_address = isset( $_POST['_memo_tag_different_address'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_memo_tag_different_address', $different_address );

        // Prix
        if ( isset( $_POST['_regular_price'] ) ) {
            update_post_meta( $post_id, '_regular_price', wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_regular_price'] ) ) ) );
            update_post_meta( $post_id, '_price', wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_regular_price'] ) ) ) );
        }

        $sale_price = isset( $_POST['_sale_price'] )
            ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_sale_price'] ) ) )
            : '';
        update_post_meta( $post_id, '_sale_price', $sale_price );

        if ( '' !== $sale_price ) {
            update_post_meta( $post_id, '_price', $sale_price );
        }
    }
}
