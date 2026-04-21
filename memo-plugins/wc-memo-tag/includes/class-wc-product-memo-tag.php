<?php
/**
 * Classe produit Memo Tag.
 *
 * @package WC_Memo_Tag
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Product_Memo_Tag
 *
 * Type de produit personnalisé qui étend WC_Product.
 * Stocke les meta spécifiques au memo tag.
 */
class WC_Product_Memo_Tag extends WC_Product {

    /**
     * Constructeur — définit le type.
     *
     * @param int|WC_Product|object $product
     */
    public function __construct( $product = 0 ) {
        $this->product_type = 'memo_tag';
        parent::__construct( $product );
    }

    /**
     * Retourne le type du produit.
     *
     * @return string
     */
    public function get_type() {
        return 'memo_tag';
    }

    /**
     * Autorise l'achat même si le prix est nul ou vide.
     *
     * @return bool
     */
    public function is_purchasable() {
        return true;
    }

    // ----------------------------------------------------------------
    // Getters
    // ----------------------------------------------------------------

    /**
     * Description personnalisée du memo tag.
     *
     * @param  string $context view|edit
     * @return string
     */
    public function get_memo_tag_description( $context = 'view' ) {
        return $this->get_prop( 'memo_tag_description', $context );
    }

    /**
     * Le détenteur du memo tag peut-il avoir une adresse différente ?
     *
     * @param  string $context view|edit
     * @return string  'yes' | 'no'
     */
    public function get_memo_tag_different_address( $context = 'view' ) {
        return $this->get_prop( 'memo_tag_different_address', $context );
    }

    // ----------------------------------------------------------------
    // Setters
    // ----------------------------------------------------------------

    /**
     * @param string $value
     */
    public function set_memo_tag_description( $value ) {
        $this->set_prop( 'memo_tag_description', sanitize_textarea_field( $value ) );
    }

    /**
     * @param string $value  'yes' | 'no'
     */
    public function set_memo_tag_different_address( $value ) {
        $this->set_prop( 'memo_tag_different_address', wc_bool_to_string( $value ) );
    }

    // ----------------------------------------------------------------
    // Persistance
    // ----------------------------------------------------------------

    /**
     * Lit les données depuis la base.
     *
     * @param WC_Data_Store_WP $data_store
     */
    protected function read_product_data( &$data_store ) {
        parent::read_product_data( $data_store );

        $id = $this->get_id();
        $this->set_props( [
            'memo_tag_description'       => get_post_meta( $id, '_memo_tag_description', true ),
            'memo_tag_different_address' => get_post_meta( $id, '_memo_tag_different_address', true ) ?: 'no',
        ] );
    }
}
