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
 * Sauf pour les outils de monitoring/CI (GitHub Actions).
 */
add_action('template_redirect', function () {
    $destination_externe = 'https://memo-tag.fr/';

    if (!function_exists('has_woo_share_access')) {
        return;
    }

    // 1. Détection des agents de monitoring ou de CI
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    // On définit une liste de mots-clés qui ne doivent pas être redirigés
    // 'GitHub-Hookshot' est souvent utilisé, ou tu peux ajouter 'curl'
    $is_ci_pipeline = (strpos($user_agent, 'GitHub') !== false || strpos($user_agent, 'curl') !== false);

    // 2. Si l'utilisateur n'a PAS l'accès ET que ce n'est pas le pipeline
    if (!has_woo_share_access() && WP_ENV !== 'development' && !$is_ci_pipeline) {
        wp_redirect($destination_externe);
        exit;
    }
});