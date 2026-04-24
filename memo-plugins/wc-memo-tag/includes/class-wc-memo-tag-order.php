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
        // Priorité 5 pour s'assurer que c'est fait AVANT l'envoi de l'email (priorité 10)
        add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'sync_order_to_supabase' ], 5, 2 );

        // Autocomplete commande
        add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'autocomplete_order' ], 20 );

        // Personnalisation de l'email
        add_filter( 'woocommerce_order_item_name',           [ __CLASS__, 'customize_order_item_name' ], 10, 3 );
        add_action( 'woocommerce_order_item_meta_end',       [ __CLASS__, 'display_tag_links_in_email' ], 10, 4 );
        add_action( 'woocommerce_email_after_order_table', [ __CLASS__, 'add_email_footer_text' ], 10, 3 );

        // Création du compte à la commande
        add_action( 'woocommerce_order_status_completed',           [ __CLASS__, 'handle_order_completion' ] );

        // Désactivation de l'email "Commande terminée" car redondant avec "Commande reçue"
        add_filter( 'woocommerce_email_enabled_customer_completed_order', '__return_false' );
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
    public static function sync_order_to_supabase( int $order_id, $order = null ) {
        if ( ! $order instanceof WC_Order ) {
            $order = wc_get_order( $order_id );
        }
        if ( ! $order ) {
            return;
        }

        $supabase_url     = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_URL' ) : getenv( 'SUPABASE_URL' );
        $service_role_key = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_SERVICE_ROLE_KEY' ) : getenv( 'SUPABASE_SERVICE_ROLE_KEY' );

        if ( ! $supabase_url || ! $service_role_key ) {
            error_log( 'Supabase sync failed for order #' . $order_id . ': Credentials missing.' );
            return;
        }

        // Récupérer les infos de livraison (Priorité Shipping, fallback Billing)
        $prenom    = $order->get_shipping_first_name() ?: $order->get_billing_first_name();
        $nom       = $order->get_shipping_last_name() ?: $order->get_billing_last_name();
        $ville     = $order->get_shipping_city() ?: $order->get_billing_city();
        $societe   = $order->get_shipping_company() ?: $order->get_billing_company();

        // Récupérer l'email (Priorité champs livraison custom, fallback Billing)
        $email = '';
        error_log( 'Supabase sync: Searching for shipping email in order #' . $order_id );
        
        foreach ( $order->get_meta_data() as $meta ) {
            $key = strtolower( $meta->key );
            // On cherche n'importe quelle clé qui contient 'shipping' ET 'email'
            if ( strpos( $key, 'shipping' ) !== false && strpos( $key, 'email' ) !== false ) {
                $val = is_array( $meta->value ) ? reset( $meta->value ) : $meta->value;
                if ( ! empty( $val ) ) {
                    $email = $val;
                    error_log( "Supabase sync: Found shipping email in meta '{$meta->key}': {$email}" );
                    break;
                }
            }
        }

        if ( empty( $email ) ) {
            $email = $order->get_billing_email();
            error_log( "Supabase sync: No shipping email found, using billing email: {$email}" );
        }

        $telephone = $order->get_shipping_phone();

        error_log( 'Supabase sync started for order #' . $order_id . ' with ' . count( $order->get_items() ) . ' line items.' );

        // Parcourir TOUS les items de la commande
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $quantity = $item->get_quantity();
            $tag_ids  = [];

            for ( $i = 0; $i < $quantity; $i++ ) {
                $tag_id = self::generate_unique_tag_id();
                $description = $item->get_meta( __( 'Description Memo Tag', 'wc-memo-tag' ) );

                $payload = [
                    'id'                => $tag_id,
                    'short_description' => $description,
                    'owner_nom'         => $nom,
                    'owner_prenom'      => $prenom,
                    'owner_email'       => $email,
                    'owner_telephone'   => $telephone,
                    'owner_ville'       => $ville,
                    'owner_societe'     => $societe,
                    'active'            => true,
                    'share_audio'       => true,
                    'share_pdf'         => true,
                    'share_link'        => true,
                    'share_travel'      => true,
                    'share_vcard'       => true,
                    'share_video'       => true,
                    'share_calendly'    => true,
                    'order_id'          => (string)$order_id,
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
                        $tag_ids[] = $tag_id;
                    }
                }
            }

            if ( ! empty( $tag_ids ) ) {
                $item->add_meta_data( '_memo_tag_ids', $tag_ids, true );
                $item->save();
            }
        }
    }
    /**
     * Passe automatiquement la commande à "terminée" après synchronisation.
     */
    public static function autocomplete_order( int $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order && $order->has_status( 'processing' ) ) {
            $order->update_status( 'completed', __( 'Autocomplete par Memo Tag.', 'wc-memo-tag' ) );
        }
    }

    /**
     * Remplace le nom du produit par la description courte dans l'email.
     */
    public static function customize_order_item_name( string $item_name, WC_Order_Item $item, bool $is_visible = true ): string {
        // On vérifie si on est dans un contexte d'email ou côté client
        // is_admin() peut être vrai si l'email est déclenché depuis l'admin, donc on check aussi WC_REDACT_ADMIN_EMAIL ou d'autres indices
        $is_email = did_action( 'woocommerce_email_header' ) || did_action( 'woocommerce_before_template_part' );
        
        if ( ! is_admin() || $is_email || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            $description = $item->get_meta( __( 'Description Memo Tag', 'wc-memo-tag' ) );
            if ( ! empty( $description ) ) {
                return '<strong>' . esc_html( $description ) . '</strong>';
            }
        }
        return $item_name;
    }

    /**
     * Affiche les liens vers les memo-tags sous chaque item dans l'email.
     */
    public static function display_tag_links_in_email( $item_id, $item, $order, $plain_text = false ) {
        if ( $plain_text ) {
            return;
        }

        $tag_ids = $item->get_meta( '_memo_tag_ids' );
        
        if ( empty( $tag_ids ) ) {
            return;
        }

        // Si c'est un ID unique (string), on le convertit en tableau pour la boucle
        if ( ! is_array( $tag_ids ) ) {
            $tag_ids = array( $tag_ids );
        }

        echo '<div class="memo-tag-links" style="margin-top: 10px; padding: 10px; border: 1px dashed #ccc; background: #fdfdfd;">';
        echo '<p style="margin: 0 0 5px 0; font-weight: bold; font-size: 0.9em;">' . esc_html__( 'Vos liens Memo-Tag :', 'wc-memo-tag' ) . '</p>';
        foreach ( $tag_ids as $tag_id ) {
            $link = 'https://memo-tag.fr/' . $tag_id;
            echo '<p style="margin: 0; font-family: monospace;">';
            echo '<a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a>';
            echo '</p>';
        }
        echo '</div>';
    }

    /**
     * Ajoute le texte de pied de page dans l'email.
     */
    public static function add_email_footer_text( $order, $sent_to_admin, $plain_text ) {
        if ( $sent_to_admin ) {
            return;
        }

        $text = __( 'Rendez vous à l\'adresse du Memo-Tag pour configurer votre contenu et télécharger le tag', 'wc-memo-tag' );
        
        if ( $plain_text ) {
            echo "\n" . esc_html( $text ) . "\n";
        } else {
            echo '<p style="margin-top: 20px; font-weight: bold;">' . esc_html( $text ) . '</p>';
        }
    }
    /**
	 * Handle WooCommerce Order Completion.
	 *
	 * Create account on order completion if no account.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    The ID of the completed order.
	 */
	public static function handle_order_completion( $order_id ) {
		error_log( 'OC4WP Debug: handle_order_completion called for order ' . $order_id );

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Handle User Creation 
		$user_id = $order->get_user_id();

		// Handle Guest Checkout
		if ( ! $user_id ) {
			error_log( 'OC4WP Debug: No user ID on order. Checking billing email.' );
			$email = $order->get_billing_email();

			if ( email_exists( $email ) ) {
				error_log( 'OC4WP Debug: User exists for email ' . $email . '. Linking order.' );
				$user = get_user_by( 'email', $email );
				$user_id = $user->ID;
			} else {
				error_log( 'OC4WP Debug: No user found. Creating new customer for ' . $email );
				$password = wp_generate_password();
				$user_id = wc_create_new_customer( $email, '', $password ); // Username, Email, Password
				if ( is_wp_error( $user_id ) ) {
					error_log( 'OC4WP Error: Failed to create user: ' . $user_id->get_error_message() );
					return;
				}
			}

			// Link order to user
			$order->set_customer_id( $user_id );
			$order->save();
			error_log( 'OC4WP Debug: Order linked to User ID ' . $user_id );
		}
	}
}
