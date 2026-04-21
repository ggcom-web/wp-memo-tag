=== WooCommerce Memo Tag ===
Contributors: memotag
Tags: woocommerce, product-type, memo-tag
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 8.x
Stable tag: 1.0.0
License: GPLv2 or later

Ajoute un type de produit "Memo Tag" à WooCommerce.

== Description ==

Ce plugin ajoute un type de produit personnalisé **Memo Tag** à WooCommerce.

=== Fonctionnalités ===

* Nouveau type de produit **Memo Tag** sélectionnable dans l'éditeur produit
* **Description obligatoire** demandée au client sur la fiche produit avant ajout au panier (validation côté client ET serveur)
* Option **adresse du détenteur** : si activée sur le produit, le client peut saisir une adresse différente de son adresse de facturation lors du checkout
* Stockage de toutes les données dans la commande et affichage dans l'admin
* Affichage dans les emails de confirmation de commande

== Installation ==

1. Téléversez le dossier `wc-memo-tag` dans `/wp-content/plugins/`
2. Activez le plugin depuis le menu **Extensions**
3. Dans WooCommerce > Produits, créez un nouveau produit et choisissez le type **Memo Tag**

== Structure des fichiers ==

```
wc-memo-tag/
├── wc-memo-tag.php                          # Fichier principal
├── includes/
│   ├── class-wc-product-memo-tag.php        # Classe produit (étend WC_Product)
│   ├── class-wc-memo-tag-product-type.php   # Enregistrement type + onglet admin
│   ├── class-wc-memo-tag-checkout.php       # Champs client (description + adresse)
│   ├── class-wc-memo-tag-order.php          # Sauvegarde dans la commande
│   └── class-wc-memo-tag-assets.php         # CSS / JS
├── assets/
│   ├── css/
│   │   ├── frontend.css
│   │   └── admin.css
│   └── js/
│       ├── frontend.js
│       └── admin.js
└── readme.txt
```

== Changelog ==

= 1.0.0 =
* Version initiale
