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

        // Affichage des meta dans l'admin de la commande
        add_action( 'woocommerce_after_order_itemmeta',             [ __CLASS__, 'display_order_item_meta' ], 10, 3 );

        // Affichage dans l'email de confirmation
        add_filter( 'woocommerce_order_item_get_formatted_meta_data', [ __CLASS__, 'format_order_item_meta' ], 10, 2 );

        // Synchronisation vers Supabase lors de la validation (paiement reçu)
        add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'sync_order_to_supabase' ] );
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
        if ( ! $product ) {
            return;
        }
        // WooCommerce affiche automatiquement les meta ajoutées via add_meta_data.
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

    // ----------------------------------------------------------------
    // Synchronisation Supabase
    // ----------------------------------------------------------------

    /**
     * Génère un ID unique de 5 caractères et vérifie sa disponibilité sur Supabase.
     *
     * @return string
     */
    public static function generate_unique_tag_id(): string {
        $supabase_url = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_URL' ) : getenv( 'SUPABASE_URL' );
        $service_role_key = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_SERVICE_ROLE_KEY' ) : getenv( 'SUPABASE_SERVICE_ROLE_KEY' );

        if ( empty( $supabase_url ) || empty( $service_role_key ) ) {
            error_log( 'Supabase generate_unique_tag_id failed: Credentials missing' );
        }

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max_attempts = 10;
        $attempt = 0;

        while ($attempt < $max_attempts) {
            $id = '';
            for ($i = 0; $i < 5; $i++) {
                $id .= $chars[rand(0, strlen($chars) - 1)];
            }

            // Vérifier sur Supabase
            $response = wp_remote_get(
                add_query_arg('id', 'eq.' . $id, $supabase_url . '/rest/v1/tags?select=id'),
                [
                    'headers' => [
                        'apikey'        => $service_role_key,
                        'Authorization' => 'Bearer ' . $service_role_key,
                    ],
                ]
            );

            if ( ! is_wp_error( $response ) ) {
                $status = wp_remote_retrieve_response_code( $response );
                $body   = wp_remote_retrieve_body( $response );
                $data   = json_decode( $body, true );

                if ( 200 === $status && empty( $data ) ) {
                    return $id;
                }
            }

            $attempt++;
        }

        // Fallback si unique non trouvé (peu probable avec 10 tentatives sur 900M+ de combinaisons)
        return substr( md5( microtime() ), 0, 5 );
    }

    /**
     * Synchronise les informations de livraison vers Supabase.
     *
     * @param int $order_id
     */
    public static function sync_order_to_supabase( int $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $supabase_url     = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_URL' ) : getenv( 'SUPABASE_URL' );
        $service_role_key = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_SERVICE_ROLE_KEY' ) : getenv( 'SUPABASE_SERVICE_ROLE_KEY' );

        if ( ! $supabase_url || ! $service_role_key ) {
            error_log( 'Supabase sync failed for order #' . $order_id . ': Credentials missing.' );
            return;
        }

        // Récupérer les infos de livraison (Shipping priority, fallback Billing)
        $prenom    = $order->get_shipping_first_name() ?: $order->get_billing_first_name();
        $nom       = $order->get_shipping_last_name() ?: $order->get_billing_last_name();
        $ville     = $order->get_shipping_city() ?: $order->get_billing_city();
        $email     = $order->get_billing_email();
        $telephone = $order->get_billing_phone();
        $societe   = $order->get_shipping_company() ?: $order->get_billing_company();

        error_log( 'Supabase sync started for order #' . $order_id . ' with ' . count( $order->get_items() ) . ' line items.' );

        // Parcourir TOUS les items de la commande
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $quantity = $item->get_quantity();

            for ( $i = 0; $i < $quantity; $i++ ) {
                $tag_id = self::generate_unique_tag_id();

                $payload = [
                    'id'              => $tag_id,
                    'owner_nom'       => $nom,
                    'owner_prenom'    => $prenom,
                    'owner_email'     => $email,
                    'owner_telephone' => $telephone,
                    'owner_ville'     => $ville,
                    'owner_societe'   => $societe,
                    'active'          => true,
                    'share_audio'     => true,
                    'share_pdf'       => true,
                    'share_link'      => true,
                    'share_travel'    => true,
                    'share_vcard'     => true,
                    'share_video'     => true,
                    'share_calendly'  => true,
                    'order_id'        => (string)$order_id,
                ];

                $response = wp_remote_post(
                    $supabase_url . '/rest/v1/tags',
                    [
                        'headers' => [
                            'apikey'        => $service_role_key,
                            'Authorization' => 'Bearer ' . $service_role_key,
                            'Content-Type'  => 'application/json',
                            'Prefer'        => 'return=minimal',
                        ],
                        'body'    => json_encode( $payload ),
                    ]
                );

                if ( is_wp_error( $response ) ) {
                    error_log( 'Supabase sync error for order #' . $order_id . ': ' . $response->get_error_message() );
                } else {
                    $status = wp_remote_retrieve_response_code( $response );
                    if ( $status >= 300 ) {
                        error_log( 'Supabase sync error for order #' . $order_id . ': Status ' . $status . ' - ' . wp_remote_retrieve_body( $response ) );
                    } else {
                        error_log( 'Supabase sync success for order #' . $order_id . ': Tag ' . $tag_id . ' created.' );
                    }
                }
            }
        }
    }
}
