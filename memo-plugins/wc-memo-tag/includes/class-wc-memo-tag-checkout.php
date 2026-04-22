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

    // ----------------------------------------------------------------
    // Adresse du détenteur (checkout)
    // ----------------------------------------------------------------

    // ----------------------------------------------------------------
    // Personnalisation du checkout
    // ----------------------------------------------------------------

    /**
     * Personnalise les champs de livraison (Propriétaire) et facturation.
     */
    public static function customize_checkout_fields( array $fields ): array {
        $has_memo_tag = false;
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( isset( $cart_item['data'] ) && 'memo_tag' === $cart_item['data']->get_type() ) {
                $has_memo_tag = true;
                break;
            }
        }

        if ( ! $has_memo_tag ) {
            return $fields;
        }

        // 1. Renommer Shipping -> Coordonnées du propriétaire
        // WC utilise 'shipping' pour les libellés de section dans certains thèmes, 
        // mais ici on change les labels des champs individuels.
        $shipping_fields = [
            'shipping_first_name' => __( 'Prénom du propriétaire', 'wc-memo-tag' ),
            'shipping_last_name'  => __( 'Nom du propriétaire', 'wc-memo-tag' ),
            'shipping_company'    => __( 'Société (optionnel)', 'wc-memo-tag' ),
            'shipping_address_1'  => __( 'Adresse', 'wc-memo-tag' ),
            'shipping_address_2'  => __( 'Complément d\'adresse', 'wc-memo-tag' ),
            'shipping_city'       => __( 'Ville', 'wc-memo-tag' ),
            'shipping_postcode'   => __( 'Code postal', 'wc-memo-tag' ),
            'shipping_country'    => __( 'Pays', 'wc-memo-tag' ),
            'shipping_state'      => __( 'Département', 'wc-memo-tag' ),
        ];

        foreach ( $shipping_fields as $key => $label ) {
            if ( isset( $fields['shipping'][ $key ] ) ) {
                $fields['shipping'][ $key ]['label'] = $label;
                $fields['shipping'][ $key ]['placeholder'] = '';
                $fields['shipping'][ $key ]['required'] = true;
                // On augmente la priorité pour qu'ils passent devant la facturation si le thème le permet
                // ou on gérera l'affichage en JS.
                $fields['shipping'][ $key ]['priority'] -= 500; 
            }
        }

        // Note: WC_Checkout::get_checkout_fields() fusionne billing et shipping.
        // On va changer l'intitulé du toggle "Expédier à une adresse différente ?" via JS.
        
        return $fields;
    }

    /**
     * Synchronise la facturation si "ship to different address" n'est pas utilisé 
     * (ici inversé : ship to different est forcé, on veut savoir si billing est différente).
     */
    public static function sync_billing_fields() {
        // Si la case "L'adresse de facturation est différente" n'est PAS cochée,
        // on copie les valeurs de livraison (propriétaire) dans la facturation.
        // Note: On utilisera un champ champ custom 'billing_is_different' en JS.
        if ( ! isset( $_POST['billing_is_different'] ) ) {
            $shipping_to_billing = [
                'first_name', 'last_name', 'company', 'address_1', 'address_2', 
                'city', 'postcode', 'country', 'state'
            ];

            foreach ( $shipping_to_billing as $field ) {
                if ( isset( $_POST[ 'shipping_' . $field ] ) ) {
                    $_POST[ 'billing_' . $field ] = $_POST[ 'shipping_' . $field ];
                }
            }
        }
    }
}
