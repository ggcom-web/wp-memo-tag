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

        // Champs d'adresse du détenteur dans le checkout
        add_action( 'woocommerce_after_order_notes',         [ __CLASS__, 'render_holder_address_fields' ] );

        // Validation checkout
        add_action( 'woocommerce_checkout_process',          [ __CLASS__, 'validate_checkout_fields' ] );
    }

    // ----------------------------------------------------------------
    // Page produit
    // ----------------------------------------------------------------

    /**
     * Affiche le champ "description du Memo Tag" sur la fiche produit.
     */
    public static function render_product_fields() {
        global $product;

        if ( ! $product || 'memo_tag' !== $product->get_type() ) {
            return;
        }

        $placeholder = get_post_meta( $product->get_id(), '_memo_tag_description', true );
        ?>
        <div class="memo-tag-product-fields">
            <p class="form-row form-row-wide validate-required">
                <label for="memo_tag_description">
                    <?php esc_html_e( 'Description du Memo Tag', 'wc-memo-tag' ); ?>
                    <abbr class="required" title="<?php esc_attr_e( 'obligatoire', 'wc-memo-tag' ); ?>">*</abbr>
                </label>
                <textarea
                    id="memo_tag_description"
                    name="memo_tag_description"
                    class="input-text"
                    rows="4"
                    placeholder="<?php echo esc_attr( $placeholder ?: __( 'Décrivez votre Memo Tag…', 'wc-memo-tag' ) ); ?>"
                    required
                ><?php echo esc_textarea( isset( $_POST['memo_tag_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['memo_tag_description'] ) ) : '' ); ?></textarea>
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

        if ( ! $product || 'memo_tag' !== $product->get_type() ) {
            return $passed;
        }

        $description = isset( $_POST['memo_tag_description'] )
            ? trim( sanitize_textarea_field( wp_unslash( $_POST['memo_tag_description'] ) ) )
            : '';

        if ( empty( $description ) ) {
            wc_add_notice(
                __( 'La description du Memo Tag est obligatoire.', 'wc-memo-tag' ),
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

        if ( ! $product || 'memo_tag' !== $product->get_type() ) {
            return $cart_item_data;
        }

        if ( isset( $_POST['memo_tag_description'] ) ) {
            $cart_item_data['memo_tag_description'] = sanitize_textarea_field(
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

    /**
     * Affiche les champs d'adresse du détenteur si le produit le requiert.
     */
    public static function render_holder_address_fields( WC_Checkout $checkout ) {
        $show = false;

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'] ?? null;
            if ( $product && 'memo_tag' === $product->get_type() ) {
                $val = get_post_meta( $product->get_id(), '_memo_tag_different_address', true );
                if ( 'yes' === $val ) {
                    $show = true;
                    break;
                }
            }
        }

        if ( ! $show ) {
            return;
        }

        echo '<div id="memo-tag-holder-address">';
        echo '<h3>' . esc_html__( 'Adresse du détenteur du Memo Tag', 'wc-memo-tag' ) . '</h3>';
        echo '<p class="memo-tag-address-notice">' . esc_html__( 'Si le détenteur du Memo Tag est différent de l\'acheteur, veuillez renseigner son adresse.', 'wc-memo-tag' ) . '</p>';

        echo '<p class="form-row form-row-wide">';
        echo '<label for="memo_tag_different_holder">';
        echo '<input type="checkbox" id="memo_tag_different_holder" name="memo_tag_different_holder" value="1" '
            . checked( 1, (int) $checkout->get_value( 'memo_tag_different_holder' ), false )
            . ' />';
        echo ' ' . esc_html__( 'Le détenteur a une adresse différente', 'wc-memo-tag' );
        echo '</label>';
        echo '</p>';

        echo '<div id="memo-tag-holder-address-fields" style="display:none;">';

        $fields = self::get_holder_address_fields();
        foreach ( $fields as $key => $field ) {
            woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Définition des champs d'adresse du détenteur.
     */
    public static function get_holder_address_fields(): array {
        return [
            'memo_tag_holder_first_name' => [
                'type'        => 'text',
                'label'       => __( 'Prénom du détenteur', 'wc-memo-tag' ),
                'placeholder' => __( 'Prénom', 'wc-memo-tag' ),
                'class'       => [ 'form-row-first' ],
                'required'    => false,
            ],
            'memo_tag_holder_last_name'  => [
                'type'        => 'text',
                'label'       => __( 'Nom du détenteur', 'wc-memo-tag' ),
                'placeholder' => __( 'Nom', 'wc-memo-tag' ),
                'class'       => [ 'form-row-last' ],
                'required'    => false,
            ],
            'memo_tag_holder_address_1'  => [
                'type'        => 'text',
                'label'       => __( 'Adresse', 'wc-memo-tag' ),
                'placeholder' => __( 'Numéro et nom de rue', 'wc-memo-tag' ),
                'class'       => [ 'form-row-wide' ],
                'required'    => false,
            ],
            'memo_tag_holder_address_2'  => [
                'type'        => 'text',
                'label'       => __( 'Complément d\'adresse', 'wc-memo-tag' ),
                'class'       => [ 'form-row-wide' ],
                'required'    => false,
            ],
            'memo_tag_holder_postcode'   => [
                'type'        => 'text',
                'label'       => __( 'Code postal', 'wc-memo-tag' ),
                'class'       => [ 'form-row-first' ],
                'required'    => false,
            ],
            'memo_tag_holder_city'       => [
                'type'        => 'text',
                'label'       => __( 'Ville', 'wc-memo-tag' ),
                'class'       => [ 'form-row-last' ],
                'required'    => false,
            ],
            'memo_tag_holder_country'    => [
                'type'        => 'country',
                'label'       => __( 'Pays', 'wc-memo-tag' ),
                'class'       => [ 'form-row-wide', 'update_totals_on_change' ],
                'required'    => false,
            ],
        ];
    }

    /**
     * Validation des champs checkout.
     */
    public static function validate_checkout_fields() {
        if ( ! isset( $_POST['memo_tag_different_holder'] ) ) {
            return;
        }

        $required_when_different = [
            'memo_tag_holder_first_name' => __( 'Prénom du détenteur', 'wc-memo-tag' ),
            'memo_tag_holder_last_name'  => __( 'Nom du détenteur', 'wc-memo-tag' ),
            'memo_tag_holder_address_1'  => __( 'Adresse du détenteur', 'wc-memo-tag' ),
            'memo_tag_holder_postcode'   => __( 'Code postal du détenteur', 'wc-memo-tag' ),
            'memo_tag_holder_city'       => __( 'Ville du détenteur', 'wc-memo-tag' ),
        ];

        foreach ( $required_when_different as $field => $label ) {
            $value = isset( $_POST[ $field ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) ) : '';
            if ( empty( $value ) ) {
                /* translators: %s: Field label */
                wc_add_notice( sprintf( __( '%s est obligatoire pour l\'adresse du détenteur.', 'wc-memo-tag' ), '<strong>' . $label . '</strong>' ), 'error' );
            }
        }
    }
}
