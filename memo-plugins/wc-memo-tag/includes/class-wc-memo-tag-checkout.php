<?php
/**
 * Champs personnalisés côté client : description obligatoire + adresse du détenteur.
 *
 * @package WC_Memo_Tag
 */

defined( 'ABSPATH' ) || exit;

class WC_Memo_Tag_Checkout {

    public static function init() {
        // Champs sur la page produit (avant "Ajouter au panier")
        add_action( 'woocommerce_before_add_to_cart_button', [ __CLASS__, 'render_product_fields' ] );

        // Validation avant ajout au panier
        add_filter( 'woocommerce_add_to_cart_validation',    [ __CLASS__, 'validate_add_to_cart' ], 10, 3 );

        // Stockage des données dans l'item du panier
        add_filter( 'woocommerce_add_cart_item_data',        [ __CLASS__, 'save_cart_item_data' ],  10, 3 );

        // Affichage dans le panier et le checkout
        add_filter( 'woocommerce_get_item_data',             [ __CLASS__, 'display_cart_item_data' ], 10, 2 );

        // Personnalisation des champs du checkout
        add_filter( 'woocommerce_checkout_fields',           [ __CLASS__, 'customize_checkout_fields' ], 999 );
        
        // Forcer l'affichage de la section "livraison" (Coordonnées du propriétaire)
        add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );

        // Synchronisation de la facturation si non cochée
        add_action( 'woocommerce_checkout_process',          [ __CLASS__, 'sync_billing_fields' ] );
    }

    // ----------------------------------------------------------------
    // Page produit
    // ----------------------------------------------------------------

    /**
     * Affiche le champ "description du Memo Tag" sur la fiche produit.
     */
    public static function render_product_fields() {
        global $product;

        if ( ! $product ) {
            return;
        }

        $placeholder = get_post_meta( $product->get_id(), '_memo_tag_description', true );
        ?>
        <div class="memo-tag-product-fields">
            <p class="form-row form-row-wide validate-required">
                <label for="memo_tag_description">
                    <?php esc_html_e( 'Description courte du memo-tag', 'wc-memo-tag' ); ?>
                    <abbr class="required" title="<?php esc_attr_e( 'obligatoire', 'wc-memo-tag' ); ?>">*</abbr>
                </label>
                <input
                    type="text"
                    id="memo_tag_description"
                    name="memo_tag_description"
                    class="input-text"
                    placeholder="<?php echo esc_attr( $placeholder ?: __( 'Ex: nom du chien, message court...', 'wc-memo-tag' ) ); ?>"
                    value="<?php echo esc_attr( isset( $_POST['memo_tag_description'] ) ? sanitize_text_field( wp_unslash( $_POST['memo_tag_description'] ) ) : '' ); ?>"
                    required
                />
            </p>
        </div>
        <?php
    }

    // ----------------------------------------------------------------
    // Validation & stockage panier
    // ----------------------------------------------------------------

    /**
     * Valide la description avant ajout au panier.
     */
    public static function validate_add_to_cart( bool $passed, int $product_id, int $quantity ): bool {
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return $passed;
        }

        $description = isset( $_POST['memo_tag_description'] )
            ? trim( sanitize_text_field( wp_unslash( $_POST['memo_tag_description'] ) ) )
            : '';

        if ( empty( $description ) ) {
            wc_add_notice(
                __( 'La description courte du Memo Tag est obligatoire.', 'wc-memo-tag' ),
                'error'
            );
            return false;
        }

        return $passed;
    }

    /**
     * Stocke les données saisies dans l'item du panier.
     */
    public static function save_cart_item_data( array $cart_item_data, int $product_id ): array {
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return $cart_item_data;
        }

        if ( isset( $_POST['memo_tag_description'] ) ) {
            $cart_item_data['memo_tag_description'] = sanitize_text_field(
                wp_unslash( $_POST['memo_tag_description'] )
            );
            // Force un hash unique pour éviter la fusion d'items
            $cart_item_data['unique_key'] = md5( microtime() . rand() );
        }

        return $cart_item_data;
    }

    /**
     * Affiche la description dans le récapitulatif panier / checkout.
     */
    public static function display_cart_item_data( array $item_data, array $cart_item ): array {
        if ( ! empty( $cart_item['memo_tag_description'] ) ) {
            $item_data[] = [
                'key'   => __( 'Description Memo Tag', 'wc-memo-tag' ),
                'value' => wp_kses_post( $cart_item['memo_tag_description'] ),
            ];
        }
        return $item_data;
    }


}
