<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});
/**
 * Redirige tous les visiteurs sans accès Woo Share vers un site externe.
 */
add_action('template_redirect', function () {
    // 1. On définit l'URL de destination externe
    $destination_externe = 'https://memo-tag.fr/';

    // 2. On vérifie si la fonction de ton plugin existe
    // Si elle n'existe pas, on ne fait rien pour éviter une erreur fatale
    if (!function_exists('has_woo_share_access')) {
        return;
    }

    // 3. Si l'utilisateur n'a PAS l'accès, on le redirige
    if (!has_woo_share_access()) {
        wp_redirect($destination_externe);
        exit;
    }
});