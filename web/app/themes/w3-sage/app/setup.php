<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Blade;
/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_filter('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    $dependencies = json_decode(Vite::content('editor.deps.json'));

    foreach ($dependencies as $dependency) {
        if (! wp_script_is($dependency)) {
            wp_enqueue_script($dependency);
        }
    }

    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});
// Register post meta fields
require_once __DIR__ . '/Fields/PostMeta.php';
require_once __DIR__ . '/Fields/PageMeta.php';

/**
 * Nettoyage du header WordPress pour le SEO et la performance.
 */
add_action('init', function () {
    // Retire les liens vers les flux RSS des articles et commentaires
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    
    // Retire le lien vers l'API REST (souvent inutile dans le head)
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    
    // Retire le support des Emojis (on utilise les natifs du navigateur maintenant)
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');

    // Retire les liens RSD (Remote Site Shutdown) et WLW Manifest (Windows Live Writer)
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');

    // Retire la version de WordPress (Sécurité + légèreté)
    remove_action('wp_head', 'wp_generator');

    // Retire les liens vers l'article suivant/précédent
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
});

/**
 * Charge les styles des blocs uniquement si nécessaire.
 */
add_filter('should_load_separate_core_block_assets', '__return_true');
/**
 * Woocommerce styles only for woocommerce
 */
add_action('wp_enqueue_scripts', function () {
    if ( function_exists('is_woocommerce') ) {
        if (!is_woocommerce() && !is_cart() && !is_checkout()) {
            wp_dequeue_style('woocommerce-layout');
            wp_dequeue_style('woocommerce-general');
            wp_dequeue_style('woocommerce-smallscreen');
            wp_dequeue_style('wc-blocks-style');
            wp_dequeue_script('wc-add-to-cart');
            // Ajoutez d'autres handles vus dans PageSpeed
        }
    }
}, 99);
/**
 * Remove jQuery Migrate to improve performance
 */
add_action('wp_default_scripts', function ($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        
        if ($script->deps) {
            // On cherche 'jquery-migrate' dans les dépendances de jQuery et on le retire
            $script->deps = array_diff($script->deps, ['jquery-migrate']);
        }
    }
});
// Load language files
add_action('after_setup_theme', function () {
    load_textdomain( 'sage', get_template_directory() . '/resources/lang/' . determine_locale() . '.mo' );
});

add_theme_support('custom-logo');

//ajoutez ce filtre pour transformer la balise <link> de votre CSS principal en chargement asynchrone (non bloquant)
add_filter('style_loader_tag', function ($tag, $handle, $href) {
    // On cible le handle 'sage/app' ou 'sage/main' (vérifiez le nom dans votre setup.php)
    if ($handle === 'sage/app') {
        // On transforme le link en 'preload' avec un fallback 'stylesheet' après chargement
        return str_replace(
            "rel='stylesheet'", 
            "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", 
            $tag
        );
    }
    return $tag;
}, 10, 3);
// lire le fichier généré par Vite/Rollup et l'afficher directement dans le <head>.
add_action('wp_head', function () {
    if (is_admin()) return;

    // Déterminer quel fichier charger
    $filename = '';
    if (is_front_page() || is_home()) {
        $filename = 'index_critical.min.css';
    } elseif (is_single() || is_page()) {
        $filename = 'single_critical.min.css';
    }
    // 1. Chemin vers le fichier généré par rollup-plugin-critical
    // Adaptez le chemin selon votre config 'target' dans vite.config.js
    $critical_path = get_theme_file_path('public/build/{$filename}');

    if (file_exists($critical_path)) {
        echo '<style id="critical-path-css">' . file_get_contents($critical_path) . '</style>';
    }
}, 1); // Priorité 1 pour être placé avant tout le reste