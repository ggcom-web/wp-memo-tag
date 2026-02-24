<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class App extends Composer
{
    protected static $views = [
        '*',
    ];

    public function with(): array
    {
        // Récupérer l'ID de l'auteur du post actuel
        $author_id = get_post_field('post_author', get_the_ID());
        
        $url = get_the_author_meta('user_url', $author_id);

        $raw_phone = get_the_author_meta('billing_phone', get_post_field('post_author'));
        $avatar = get_avatar($author_id, $size = '60', $default = '', $alt = '', $args = array( 'class' => 'h-10 w-10 rounded-full' ) );
        return [
            'site_name'     => $this->siteName(),
            'author_url'    => $url ?: '',
            'author_phone' => [
                'display' => $this->format_french_phone($raw_phone),
                'link'    => preg_replace('/[^0-9+]/', '', $raw_phone), // Garde le +33 si présent pour le lien tel:
            ],
            'author_domain' => $this->getCleanDomain($url),
            'author_avatar' => $avatar,
        ];
    }

    /**
     * Nettoyage du nom de domaine
     */
    private function getCleanDomain($url)
    {
        if (!$url) return '';

        // Ajout du protocole si absent pour parse_url
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        return str_replace('www.', '', $host ?? '');
    }

    public function siteName(): string
    {
        return get_bloginfo('name', 'display');
    }
    /**
     * Nettoie et formate un numéro français pour l'affichage.
     * Gère +33, (0), les fixes (01-05) et les mobiles (06-07).
     */
    function format_french_phone($phone) {
        // 1. Nettoyage complet : on ne garde que les chiffres et le signe +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        // 2. Gestion de l'international +33
        // Si ça commence par +33, on remplace par un 0
        if (str_starts_with($cleaned, '+33')) {
            $cleaned = '0' . substr($cleaned, 3);
        }

        // 3. Correction du fameux +33(0) qui devient "00" après nettoyage
        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 1);
        }

        // 4. On s'assure qu'on a bien un numéro à 10 chiffres commençant par 0
        // Si l'utilisateur a saisi "285...", on rajoute le 0
        if (strlen($cleaned) === 9 && !str_starts_with($cleaned, '0')) {
            $cleaned = '0' . $cleaned;
        }

        // 5. Découpage par blocs de 2 pour la lisibilité (0X XX XX XX XX)
        return implode(' ', str_split($cleaned, 2));
    }
}