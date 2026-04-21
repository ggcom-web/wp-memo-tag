<?php
/**
 * Plugin Name:       WooCommerce Memo Tag
 * Plugin URI:        https://memo-tag.com
 * Description:       Ajoute un type de produit "Memo Tag" à WooCommerce, avec description obligatoire et gestion d'adresse du détenteur.
 * Version:           1.0.0
 * Author:            Memo Tag
 * Author URI:        https://memo-tag.com
 * Text Domain:       wc-memo-tag
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:   8.x
 */

defined( 'ABSPATH' ) || exit;

// Constantes du plugin
define( 'WC_MEMO_TAG_VERSION',     '1.0.0' );
define( 'WC_MEMO_TAG_PLUGIN_FILE', __FILE__ );
define( 'WC_MEMO_TAG_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WC_MEMO_TAG_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Vérification que WooCommerce est actif.
 */
function wc_memo_tag_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="error"><p>'
                . esc_html__( 'WooCommerce Memo Tag nécessite WooCommerce. Veuillez l\'installer et l\'activer.', 'wc-memo-tag' )
                . '</p></div>';
        } );
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}
add_action( 'admin_init', 'wc_memo_tag_check_woocommerce' );

/**
 * Chargement principal du plugin après que WooCommerce soit prêt.
 */
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    // Chargement des fichiers
    require_once WC_MEMO_TAG_PLUGIN_DIR . 'includes/class-wc-product-memo-tag.php';
    require_once WC_MEMO_TAG_PLUGIN_DIR . 'includes/class-wc-memo-tag-product-type.php';
    require_once WC_MEMO_TAG_PLUGIN_DIR . 'includes/class-wc-memo-tag-checkout.php';
    require_once WC_MEMO_TAG_PLUGIN_DIR . 'includes/class-wc-memo-tag-order.php';
    require_once WC_MEMO_TAG_PLUGIN_DIR . 'includes/class-wc-memo-tag-assets.php';

    // Initialisation des modules
    WC_Memo_Tag_Checkout::init();
    WC_Memo_Tag_Order::init();
    WC_Memo_Tag_Assets::init();
} );

/**
 * Activation : flush des règles de réécriture.
 */
register_activation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );

/**
 * Désactivation.
 */
register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );
