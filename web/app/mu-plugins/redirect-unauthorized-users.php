<?php
/**
 * Redirige tous les visiteurs sans accès Woo Share vers un site externe.
 * Sauf pour :
 * - La page "tarifs"
 * - Les outils de monitoring/CI (GitHub Actions / Curl)
 * - L'environnement de développement
 */
add_action('template_redirect', function () {
    $destination_externe = 'https://memo-tag.fr/';

    // 1. Ne rien faire si la fonction d'accès n'existe pas
    if (!function_exists('has_woo_share_access')) {
        return;
    }

    // 2. Vérifier si on est sur la page "tarifs" (slug, ID ou titre)
    if (is_page('tarifs')) {
        return;
    }

    // 3. Détection de l'environnement de dev (évite une Notice PHP si non définie)
    $is_dev_env = defined('WP_ENV') && WP_ENV === 'development';
    if ($is_dev_env) {
        return;
    }

    // 4. Détection des agents de monitoring ou de CI
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $is_ci_pipeline = (strpos($user_agent, 'GitHub') !== false || strpos($user_agent, 'curl') !== false);

    // 5. Redirection si l'utilisateur n'a pas accès et n'est pas un bot CI
    if (!has_woo_share_access() && !$is_ci_pipeline) {
        wp_redirect($destination_externe, 302);
        exit;
    }
});