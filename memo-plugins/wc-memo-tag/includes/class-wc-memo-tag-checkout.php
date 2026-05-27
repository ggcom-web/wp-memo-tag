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

            <div class="memo-tag-share-options-container">
                <h4 class="memo-tag-share-options-title">
                    <?php esc_html_e( 'Options de partage actives', 'wc-memo-tag' ); ?>
                </h4>
                <p class="memo-tag-share-options-subtitle">
                    <?php esc_html_e( 'Cochez les fonctionnalités de partage que vous souhaitez activer pour ce tag :', 'wc-memo-tag' ); ?>
                </p>

                <div class="memo-tag-share-options-grid">
                    <?php
                    $options = [
                        'share_audio'    => [
                            'label' => __( 'Audio', 'wc-memo-tag' ),
                            'icon'  => '🎵',
                            'desc'  => __( 'Activer le partage audio', 'wc-memo-tag' )
                        ],
                        'share_pdf'      => [
                            'label' => __( 'PDF', 'wc-memo-tag' ),
                            'icon'  => '📄',
                            'desc'  => __( 'Activer le partage PDF', 'wc-memo-tag' )
                        ],
                        'share_link'     => [
                            'label' => __( 'Lien Web', 'wc-memo-tag' ),
                            'icon'  => '🔗',
                            'desc'  => __( 'Activer le partage de lien', 'wc-memo-tag' )
                        ],
                        'share_travel'   => [
                            'label' => __( 'Voyage', 'wc-memo-tag' ),
                            'icon'  => '✈️',
                            'desc'  => __( 'Activer les infos de voyage', 'wc-memo-tag' )
                        ],
                        'share_vcard'    => [
                            'label' => __( 'VCard', 'wc-memo-tag' ),
                            'icon'  => '📇',
                            'desc'  => __( 'Activer la fiche contact VCard', 'wc-memo-tag' )
                        ],
                        'share_video'    => [
                            'label' => __( 'Vidéo', 'wc-memo-tag' ),
                            'icon'  => '🎥',
                            'desc'  => __( 'Activer le partage de vidéo', 'wc-memo-tag' )
                        ],
                        'share_calendly' => [
                            'label' => __( 'Calendly', 'wc-memo-tag' ),
                            'icon'  => '📅',
                            'desc'  => __( 'Activer la prise de RDV Calendly', 'wc-memo-tag' )
                        ],
                    ];

                    foreach ( $options as $key => $data ) {
                        ?>
                        <label class="memo-tag-share-option-card" for="<?php echo esc_attr( $key ); ?>">
                            <div class="memo-tag-share-option-card-inner">
                                <input 
                                    type="checkbox" 
                                    name="<?php echo esc_attr( $key ); ?>" 
                                    id="<?php echo esc_attr( $key ); ?>" 
                                    value="1" 
                                    checked 
                                />
                                <div class="memo-tag-share-option-info">
                                    <span class="memo-tag-share-option-icon"><?php echo esc_html( $data['icon'] ); ?></span>
                                    <div class="memo-tag-share-option-text">
                                        <span class="memo-tag-share-option-label"><?php echo esc_html( $data['label'] ); ?></span>
                                        <span class="memo-tag-share-option-desc"><?php echo esc_html( $data['desc'] ); ?></span>
                                    </div>
                                </div>
                            </div>
                        </label>
                        <?php
                    }
                    ?>
                </div>
            </div>
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
            
            // Sauvegarde des options de partage (true si coché, false sinon)
            $share_options = [
                'share_audio'    => isset( $_REQUEST['share_audio'] ) ? true : false,
                'share_pdf'      => isset( $_REQUEST['share_pdf'] ) ? true : false,
                'share_link'     => isset( $_REQUEST['share_link'] ) ? true : false,
                'share_travel'   => isset( $_REQUEST['share_travel'] ) ? true : false,
                'share_vcard'    => isset( $_REQUEST['share_vcard'] ) ? true : false,
                'share_video'    => isset( $_REQUEST['share_video'] ) ? true : false,
                'share_calendly' => isset( $_REQUEST['share_calendly'] ) ? true : false,
            ];
            $cart_item_data['memo_tag_share_options'] = $share_options;
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

        if ( ! empty( $cart_item['memo_tag_share_options'] ) ) {
            $active_options = [];
            $labels = [
                'share_audio'    => 'Audio',
                'share_pdf'      => 'PDF',
                'share_link'     => 'Lien',
                'share_travel'   => 'Voyage',
                'share_vcard'    => 'VCard',
                'share_video'    => 'Vidéo',
                'share_calendly' => 'Calendly',
            ];
            foreach ( $cart_item['memo_tag_share_options'] as $key => $is_active ) {
                if ( $is_active && isset( $labels[ $key ] ) ) {
                    $active_options[] = $labels[ $key ];
                }
            }
            $val = ! empty( $active_options ) ? implode( ', ', $active_options ) : __( 'Aucune', 'wc-memo-tag' );
            $item_data[] = [
                'name'  => __( 'Options de partage', 'wc-memo-tag' ),
                'value' => $val,
            ];
        }

        return $item_data;
    }
}
