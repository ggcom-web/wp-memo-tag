<?php
/**
 * Champs personnalisés côté client : description obligatoire + adresse du détenteur.
 *
 * @package WC_Memo_Tag
 */

defined( 'ABSPATH' ) || exit;

class WC_Memo_Tag_Checkout {

    /**
     * Initialisation des hooks.
     */
    public static function init() {
        // Affichage du champ sur la fiche produit
        add_action( 'woocommerce_before_add_to_cart_button', [ __CLASS__, 'display_product_custom_fields' ] );

        // Validation lors de l'ajout au panier
        add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'validate_add_to_cart' ], 10, 3 );

        // Sauvegarde dans les données du panier
        add_filter( 'woocommerce_add_cart_item_data', [ __CLASS__, 'add_cart_item_data' ], 10, 2 );

        // Affichage dans le panier et le checkout
        add_filter( 'woocommerce_get_item_data', [ __CLASS__, 'get_item_data' ], 10, 2 );

        // Nouveaux champs pour le Checkout (Compatibilité Blocks et Classique)
        add_action( 'init', [ __CLASS__, 'register_additional_checkout_fields' ] );
        add_filter( 'woocommerce_shipping_fields', [ __CLASS__, 'add_shipping_fields' ] );
    }

    /**
     * Enregistre les champs additionnels pour le nouveau Checkout Block (WC 8.5+).
     */
    public static function register_additional_checkout_fields() {
        if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
            return;
        }

        woocommerce_register_additional_checkout_field( [
            'id'          => 'wc-memo-tag/shipping-email',
            'label'       => __( 'E-mail du détenteur (Livraison)', 'wc-memo-tag' ),
            'location'    => 'order',
            'type'        => 'text',
            'attributes'  => [
                'autocomplete' => 'email',
            ],
            'required'    => false,
        ] );
    }

    /**
     * Ajoute les champs pour le checkout classique (Shortcode).
     */
    public static function add_shipping_fields( $fields ) {
        $fields['shipping_email'] = [
            'label'       => __( 'E-mail du détenteur (Livraison)', 'wc-memo-tag' ),
            'placeholder' => __( 'L\'e-mail pour Supabase...', 'wc-memo-tag' ),
            'required'    => false,
            'class'       => [ 'form-row-wide' ],
            'validate'    => [ 'email' ],
            'priority'    => 100,
        ];
        return $fields;
    }

    /**
     * Affiche le champ de description sur la page produit.
     */
    public static function display_product_custom_fields() {
        ?>
        <div class="memo-tag-product-fields">
            <p class="form-row form-row-wide">
                <label for="memo_tag_description">
                    <?php esc_html_e( 'Description Memo Tag', 'wc-memo-tag' ); ?> <span class="required">*</span>
                </label>
                <input 
                    type="text"
                    name="memo_tag_description" 
                    id="memo_tag_description" 
                    class="input-text" 
                    placeholder="<?php esc_attr_e( 'Saisissez la description de votre Memo Tag...', 'wc-memo-tag' ); ?>"
                >
            </p>
        </div>
        <?php
    }

    /**
     * Valide que le champ est bien rempli.
     */
    public static function validate_add_to_cart( $passed, $product_id, $quantity ) {
        if ( empty( $_REQUEST['memo_tag_description'] ) ) {
            $passed = false;
            wc_add_notice( __( 'La description du Memo Tag est obligatoire.', 'wc-memo-tag' ), 'error' );
        }
        return $passed;
    }

    /**
     * Ajoute la description aux données de l'item du panier.
     */
    public static function add_cart_item_data( $cart_item_data, $product_id ) {
        if ( ! empty( $_REQUEST['memo_tag_description'] ) ) {
            $cart_item_data['memo_tag_description'] = sanitize_textarea_field( $_REQUEST['memo_tag_description'] );
        }
        return $cart_item_data;
    }

    /**
     * Affiche la description dans le panier et le récapitulatif de commande.
     */
    public static function get_item_data( $item_data, $cart_item ) {
        if ( ! empty( $cart_item['memo_tag_description'] ) ) {
            $item_data[] = [
                'name'  => __( 'Description Memo Tag', 'wc-memo-tag' ),
                'value' => $cart_item['memo_tag_description'],
            ];
        }
        return $item_data;
    }
}
