<?php
/**
 * Chargement des assets CSS et JS.
 *
 * @package WC_Memo_Tag
 */

defined( 'ABSPATH' ) || exit;

class WC_Memo_Tag_Assets {

    public static function init() {
        add_action( 'wp_enqueue_scripts',    [ __CLASS__, 'enqueue_frontend' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin' ] );
    }

    /**
     * Scripts / styles frontend.
     */
    public static function enqueue_frontend() {
        if ( ! is_product() && ! is_checkout() ) {
            return;
        }

        wp_enqueue_style(
            'wc-memo-tag-frontend',
            WC_MEMO_TAG_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            WC_MEMO_TAG_VERSION
        );

        wp_enqueue_script(
            'wc-memo-tag-frontend',
            WC_MEMO_TAG_PLUGIN_URL . 'assets/js/frontend.js',
            [ 'jquery' ],
            WC_MEMO_TAG_VERSION,
            true
        );

        wp_localize_script( 'wc-memo-tag-frontend', 'wcMemoTag', [
            'required_description' => __( 'La description du Memo Tag est obligatoire.', 'wc-memo-tag' ),
        ] );
    }

    /**
     * Scripts / styles admin (éditeur produit).
     */
    public static function enqueue_admin( string $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        wp_enqueue_style(
            'wc-memo-tag-admin',
            WC_MEMO_TAG_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WC_MEMO_TAG_VERSION
        );

        wp_enqueue_script(
            'wc-memo-tag-admin',
            WC_MEMO_TAG_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            WC_MEMO_TAG_VERSION,
            true
        );
    }
}
