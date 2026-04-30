<?php
/**
 * Gestion de l'espace Mon Compte WooCommerce
 *
 * @package WC_Memo_Tag
 */

defined( 'ABSPATH' ) || exit;

class WC_Memo_Tag_Account {

    public static function init() {
        // Ajouter le menu
        add_filter( 'woocommerce_account_menu_items', [ __CLASS__, 'add_menu_item' ] );
        
        // Enregistrer l'endpoint
        add_action( 'init', [ __CLASS__, 'add_endpoints' ] );
        
        // Ajouter le contenu
        add_action( 'woocommerce_account_mes-memo-tags_endpoint', [ __CLASS__, 'endpoint_content_mes_tags' ] );
        add_action( 'woocommerce_account_tags-offerts_endpoint', [ __CLASS__, 'endpoint_content_tags_offerts' ] );

        // AJAX update description
        add_action( 'wp_ajax_update_memo_tag_description', [ __CLASS__, 'update_description_ajax' ] );
    }

    public static function add_menu_item( $items ) {
        // Insérer avant le lien de déconnexion si possible
        $new_items = [];
        foreach ( $items as $key => $value ) {
            if ( 'customer-logout' === $key ) {
                $new_items['mes-memo-tags'] = __( 'Mes Memo Tags', 'wc-memo-tag' );
                $new_items['tags-offerts']  = __( 'Tags Offerts', 'wc-memo-tag' );
            }
            $new_items[ $key ] = $value;
        }
        
        // Au cas où customer-logout n'existe pas
        if ( ! isset( $new_items['mes-memo-tags'] ) ) {
            $new_items['mes-memo-tags'] = __( 'Mes Memo Tags', 'wc-memo-tag' );
            $new_items['tags-offerts']  = __( 'Tags Offerts', 'wc-memo-tag' );
        }
        
        return $new_items;
    }

    public static function add_endpoints() {
        add_rewrite_endpoint( 'mes-memo-tags', EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( 'tags-offerts', EP_ROOT | EP_PAGES );
    }

    public static function endpoint_content_mes_tags() {
        self::render_endpoint_content( 'mes_tags' );
    }

    public static function endpoint_content_tags_offerts() {
        self::render_endpoint_content( 'tags_offerts' );
    }

    private static function render_endpoint_content( $type ) {
        $user = wp_get_current_user();
        if ( ! $user || ! $user->user_email ) {
            echo '<p>' . esc_html__( 'Vous devez être connecté.', 'wc-memo-tag' ) . '</p>';
            return;
        }

        $supabase_url     = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_URL' ) : getenv( 'SUPABASE_URL' );
        $service_role_key = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_SERVICE_ROLE_KEY' ) : getenv( 'SUPABASE_SERVICE_ROLE_KEY' );

        if ( ! $supabase_url || ! $service_role_key ) {
            echo '<p>' . esc_html__( 'Erreur de configuration Supabase.', 'wc-memo-tag' ) . '</p>';
            return;
        }

        if ( $type === 'mes_tags' ) {
            $query_args = [
                'owner_email' => 'eq.' . urlencode( $user->user_email ),
            ];
            $empty_message = __( 'Vous n\'avez aucun Memo Tag pour le moment.', 'wc-memo-tag' );
            $title = __( 'Mes Memo Tags', 'wc-memo-tag' );
        } else {
            $customer_orders = wc_get_orders( [
                'customer' => get_current_user_id(),
                'limit'    => -1,
                'return'   => 'ids',
            ] );

            if ( empty( $customer_orders ) ) {
                echo '<p>' . esc_html__( 'Vous n\'avez aucun Memo Tag offert pour le moment.', 'wc-memo-tag' ) . '</p>';
                return;
            }

            $order_ids_list = implode( ',', $customer_orders );

            $query_args = [
                'order_id'    => 'in.(' . urlencode( $order_ids_list ) . ')',
                'owner_email' => 'neq.' . urlencode( $user->user_email ),
            ];
            $empty_message = __( 'Vous n\'avez aucun Memo Tag offert pour le moment.', 'wc-memo-tag' );
            $title = __( 'Tags Offerts', 'wc-memo-tag' );
        }

        // Récupérer les tags de l'utilisateur
        $response = wp_remote_get(
            add_query_arg( $query_args, $supabase_url . '/rest/v1/tags' ),
            [
                'headers' => [
                    'apikey'        => $service_role_key,
                    'Authorization' => 'Bearer ' . $service_role_key,
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            echo '<p>' . esc_html__( 'Erreur lors de la récupération de vos Memo Tags.', 'wc-memo-tag' ) . '</p>';
            return;
        }

        $status = wp_remote_retrieve_response_code( $response );
        if ( $status !== 200 ) {
            echo '<p>' . esc_html__( 'Erreur serveur lors de la récupération.', 'wc-memo-tag' ) . '</p>';
            return;
        }

        $tags = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $tags ) ) {
            echo '<p>' . esc_html( $empty_message ) . '</p>';
            return;
        }

        // Récupérer les edit_tokens pour ces tags
        $tag_ids = array_column( $tags, 'id' );
        $tag_ids_list = implode( ',', array_map( function( $id ) { return '"' . $id . '"'; }, $tag_ids ) );
        
        $tokens_response = wp_remote_get(
            add_query_arg( 'tag_id', 'in.(' . urlencode( $tag_ids_list ) . ')', $supabase_url . '/rest/v1/edit_tokens' ),
            [
                'headers' => [
                    'apikey'        => $service_role_key,
                    'Authorization' => 'Bearer ' . $service_role_key,
                ],
            ]
        );

        $tokens_by_tag = [];
        if ( ! is_wp_error( $tokens_response ) && wp_remote_retrieve_response_code( $tokens_response ) === 200 ) {
            $tokens = json_decode( wp_remote_retrieve_body( $tokens_response ), true );
            if ( ! empty( $tokens ) ) {
                foreach ( $tokens as $token ) {
                    $tokens_by_tag[ $token['tag_id'] ] = $token['token'];
                }
            }
        }

        echo '<h2>' . esc_html( $title ) . '</h2>';
        echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">';
        echo '<thead><tr>';
        echo '<th class="woocommerce-orders-table__header">' . esc_html__( 'ID', 'wc-memo-tag' ) . '</th>';
        echo '<th class="woocommerce-orders-table__header">' . esc_html__( 'Description', 'wc-memo-tag' ) . '</th>';
        echo '<th class="woocommerce-orders-table__header">' . esc_html__( 'Création', 'wc-memo-tag' ) . '</th>';
        echo '<th class="woocommerce-orders-table__header">' . esc_html__( 'Dernière modification', 'wc-memo-tag' ) . '</th>';
        echo '<th class="woocommerce-orders-table__header">' . esc_html__( 'Actions', 'wc-memo-tag' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $tags as $tag ) {
            $created_at = isset($tag['created_at']) && $tag['created_at'] ? date_i18n( get_option( 'date_format' ), strtotime( $tag['created_at'] ) ) : '-';
            $updated_at = isset($tag['updated_at']) && $tag['updated_at'] ? date_i18n( get_option( 'date_format' ), strtotime( $tag['updated_at'] ) ) : '-';

            $view_link = 'https://memo-tag.fr/' . esc_attr( $tag['id'] );
            
            if ( isset( $tokens_by_tag[ $tag['id'] ] ) ) {
                $edit_link = 'https://memo-tag.fr/edit/' . esc_attr( $tokens_by_tag[ $tag['id'] ] ) . '/form';
            } else {
                $edit_link = $view_link; // Lien de base s'il n'est pas encore configuré
            }

            echo '<tr class="woocommerce-orders-table__row">';
            echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'ID', 'wc-memo-tag' ) . '"><strong>' . esc_html( $tag['id'] ) . '</strong></td>';
            
            $desc = esc_html( $tag['short_description'] ?? '' );
            echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'Description', 'wc-memo-tag' ) . '">';
            echo '<div class="memo-tag-desc-container" data-tag-id="' . esc_attr( $tag['id'] ) . '" style="display:flex; align-items:center; gap:8px;">';
            echo '  <span class="memo-tag-desc-text">' . $desc . '</span>';
            echo '  <button type="button" class="memo-tag-edit-btn" style="background:none;border:none;box-shadow:none;color:#aaa;cursor:pointer;padding:0;" title="' . esc_attr__( 'Modifier', 'wc-memo-tag' ) . '">&#9998;</button>';
            echo '  <div class="memo-tag-edit-form" style="display:none; align-items:center; gap:5px;">';
            echo '      <input type="text" class="memo-tag-desc-input" value="' . esc_attr( $tag['short_description'] ?? '' ) . '" style="width: 100%; max-width: 200px; padding: 2px 5px;" />';
            echo '      <button type="button" class="memo-tag-save-btn" style="background:none;border:none;box-shadow:none;color:green;cursor:pointer;padding:0;font-size:1.2em;" title="' . esc_attr__( 'Valider', 'wc-memo-tag' ) . '">&#10003;</button>';
            echo '      <button type="button" class="memo-tag-cancel-btn" style="background:none;border:none;box-shadow:none;color:red;cursor:pointer;padding:0;font-size:1.2em;" title="' . esc_attr__( 'Annuler', 'wc-memo-tag' ) . '">&#10005;</button>';
            echo '  </div>';
            echo '  <span class="memo-tag-desc-loader" style="display:none; font-size:0.8em; color:#666;">...</span>';
            echo '</div>';
            echo '</td>';

            echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'Création', 'wc-memo-tag' ) . '">' . esc_html( $created_at ) . '</td>';
            echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'Dernière modification', 'wc-memo-tag' ) . '">' . esc_html( $updated_at ) . '</td>';
            echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'Actions', 'wc-memo-tag' ) . '">';
            echo '<a href="' . esc_url( $view_link ) . '" class="woocommerce-button button view" target="_blank" style="margin-right: 5px;">' . esc_html__( 'Voir', 'wc-memo-tag' ) . '</a>';
            echo '<a href="' . esc_url( $edit_link ) . '" class="woocommerce-button button edit" target="_blank">' . esc_html__( 'Modifier', 'wc-memo-tag' ) . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Ajouter le script pour l'édition en ligne
        ?>
        <script>
        if (typeof window.memoTagEditInitialized === 'undefined') {
            window.memoTagEditInitialized = true;
            document.addEventListener('DOMContentLoaded', function() {
                const containers = document.querySelectorAll('.memo-tag-desc-container');
                containers.forEach(container => {
                    const textSpan = container.querySelector('.memo-tag-desc-text');
                    const editBtn = container.querySelector('.memo-tag-edit-btn');
                    const editForm = container.querySelector('.memo-tag-edit-form');
                    const inputField = container.querySelector('.memo-tag-desc-input');
                    const saveBtn = container.querySelector('.memo-tag-save-btn');
                    const cancelBtn = container.querySelector('.memo-tag-cancel-btn');
                    const loader = container.querySelector('.memo-tag-desc-loader');
                    const tagId = container.getAttribute('data-tag-id');

                    editBtn.addEventListener('click', () => {
                        textSpan.style.display = 'none';
                        editBtn.style.display = 'none';
                        editForm.style.display = 'flex';
                        inputField.focus();
                    });

                    cancelBtn.addEventListener('click', () => {
                        editForm.style.display = 'none';
                        textSpan.style.display = 'inline';
                        editBtn.style.display = 'inline';
                        inputField.value = textSpan.innerText;
                    });

                    saveBtn.addEventListener('click', () => {
                        const newDesc = inputField.value.trim();
                        editForm.style.display = 'none';
                        loader.style.display = 'inline';

                        const formData = new URLSearchParams();
                        formData.append('action', 'update_memo_tag_description');
                        formData.append('tag_id', tagId);
                        formData.append('description', newDesc);
                        formData.append('nonce', '<?php echo esc_js( wp_create_nonce( 'update_memo_tag_desc' ) ); ?>');

                        fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            loader.style.display = 'none';
                            if (data.success) {
                                textSpan.innerText = newDesc;
                            } else {
                                alert(data.data || 'Erreur lors de la mise à jour.');
                                inputField.value = textSpan.innerText;
                            }
                            textSpan.style.display = 'inline';
                            editBtn.style.display = 'inline';
                        })
                        .catch(err => {
                            loader.style.display = 'none';
                            alert('Erreur réseau.');
                            inputField.value = textSpan.innerText;
                            textSpan.style.display = 'inline';
                            editBtn.style.display = 'inline';
                        });
                    });
                });
            });
        }
        </script>
        <?php
    }

    public static function update_description_ajax() {
        check_ajax_referer( 'update_memo_tag_desc', 'nonce' );

        $user = wp_get_current_user();
        if ( ! $user || ! $user->user_email ) {
            wp_send_json_error( 'Non autorisé.' );
        }

        $tag_id = isset( $_POST['tag_id'] ) ? sanitize_text_field( $_POST['tag_id'] ) : '';
        $description = isset( $_POST['description'] ) ? sanitize_text_field( $_POST['description'] ) : '';

        if ( empty( $tag_id ) ) {
            wp_send_json_error( 'ID manquant.' );
        }

        $supabase_url     = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_URL' ) : getenv( 'SUPABASE_URL' );
        $service_role_key = class_exists( 'Roots\WPConfig\Config' ) ? Roots\WPConfig\Config::get( 'SUPABASE_SERVICE_ROLE_KEY' ) : getenv( 'SUPABASE_SERVICE_ROLE_KEY' );

        if ( ! $supabase_url || ! $service_role_key ) {
            wp_send_json_error( 'Erreur de configuration Supabase.' );
        }

        // Vérifier que le tag appartient bien à l'utilisateur (propriétaire ou acheteur)
        $check_response = wp_remote_get(
            add_query_arg( [
                'id' => 'eq.' . $tag_id,
                'select' => 'owner_email,order_id'
            ], $supabase_url . '/rest/v1/tags' ),
            [
                'headers' => [
                    'apikey'        => $service_role_key,
                    'Authorization' => 'Bearer ' . $service_role_key,
                ],
            ]
        );

        if ( is_wp_error( $check_response ) || wp_remote_retrieve_response_code( $check_response ) !== 200 ) {
            wp_send_json_error( 'Erreur lors de la vérification.' );
        }

        $tags = json_decode( wp_remote_retrieve_body( $check_response ), true );
        if ( empty( $tags ) ) {
            wp_send_json_error( 'Tag introuvable.' );
        }

        $tag = $tags[0];
        $is_owner = ( $tag['owner_email'] === $user->user_email );
        $is_buyer = false;

        if ( ! empty( $tag['order_id'] ) ) {
            $order = wc_get_order( $tag['order_id'] );
            if ( $order && $order->get_customer_id() == get_current_user_id() ) {
                $is_buyer = true;
            }
        }

        if ( ! $is_owner && ! $is_buyer ) {
            wp_send_json_error( 'Non autorisé.' );
        }

        // On met à jour
        $payload = [
            'short_description' => $description,
        ];

        $update_response = wp_remote_request(
            add_query_arg( 'id', 'eq.' . $tag_id, $supabase_url . '/rest/v1/tags' ),
            [
                'method'  => 'PATCH',
                'headers' => [
                    'apikey'        => $service_role_key,
                    'Authorization' => 'Bearer ' . $service_role_key,
                    'Content-Type'  => 'application/json',
                    'Prefer'        => 'return=minimal',
                ],
                'body'    => json_encode( $payload ),
            ]
        );

        if ( is_wp_error( $update_response ) || wp_remote_retrieve_response_code( $update_response ) >= 300 ) {
            wp_send_json_error( 'Erreur lors de la mise à jour.' );
        }

        wp_send_json_success( 'Mise à jour réussie.' );
    }
}
