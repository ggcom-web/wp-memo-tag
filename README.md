# w3atelier

**Laboratoire créatif pour vos projets WordPress et web sur mesure**

Ce dépôt contient le code source d’un blog/portfolio basé sur **WordPress avec Bedrock**, configuré pour suivre les bonnes pratiques de développement modernes (structure projet, gestion via Composer, environnement `.env`, CI/CD possible).

> ⚠️ Ce projet n’inclut pas encore le thème Sage. Le front-end utilise pour le moment un thème WordPress standard.

---

## 🛠 Prérequis

- PHP >= 8.0  
- Composer >= 2.0  
- MySQL / MariaDB  
- Git  
- Serveur local (Wamp, MAMP, Local, Valet…)

---

## 📁 Structure du projet

```
.
├── .env.example      # Modèle des variables d’environnement
├── composer.json     # Dépendances PHP / WordPress
├── config/           # Configuration Bedrock
├── web/              # Racine WordPress (DocumentRoot)
│   ├── wp            # Core WordPress
│   ├── app           # Plugins, mu-plugins, themes
│   │   └── themes
│   └── uploads       # Médias (ignoré par Git)
├── vendor/           # Dépendances PHP installées via Composer
```

---

## ⚡ Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/<ton-username>/w3atelier.git
cd w3atelier
```

---

### 2. Installer les dépendances PHP

```bash
composer install
```

---

### 3. Créer le fichier `.env`

Copie le fichier d’exemple :

```bash
cp .env.example .env
```

Puis configure tes variables locales :

```env
DB_NAME=nom_de_ta_base
DB_USER=ton_user
DB_PASSWORD=ton_mdp
DB_HOST=localhost

WP_ENV=development
WP_HOME=http://w3.gg
WP_SITEURL=${WP_HOME}/wp
```

> ⚠️ Ne jamais versionner ton fichier `.env` réel sur GitHub.

---

### 4. Créer la base de données

```sql
CREATE DATABASE nom_de_ta_base;
```

---

### 5. Installer WordPress avec WP-CLI

Depuis le dossier `web/` :

```bash
cd web/

wp core install \
  --url="http://w3.gg" \
  --title="w3atelier" \
  --admin_user="admin" \
  --admin_password="motdepasse" \
  --admin_email="ton.email@example.com"

// Install and activate the French language.

wp language core install fr_FR --activate
```

---

## 🚀 Démarrage local

- Configure ton VirtualHost pour pointer vers :

```
.../w3atelier/web
```

- Accès au site :

```
http://w3.gg
```

- Accès à l’administration :

```
http://w3.gg/wp/wp-admin
```

---

## Intégration du thème **Sage**

```

cd web/app/themes

composer create-project roots/sage memo-sage

cd memo-sage

npm install

npm run build

wp theme activate memo-sage

```
## 📦 Déploiement / CI/CD

Le projet est prêt pour un workflow moderne :

- `.env.example` versionné comme modèle
- `/web/app/themes/` et `/web/app/plugins/` versionnés
- `/vendor/` installé automatiquement via Composer en CI

Les secrets doivent être stockés dans GitHub Actions (Secrets).

---

## ⚠️ Bonnes pratiques Git

Ne jamais versionner :

- `.env`
- `/vendor/`
- `/web/app/uploads/`
- `/node_modules/` (lorsque Sage sera ajouté)

---

## ✅ À venir

- Build front moderne (SCSS, Vite)
- CI/CD complet avec déploiement automatique

---

## 🖼 Architecture simplifiée

```
w3atelier/
├── config/            # Config Bedrock
├── vendor/            # Dépendances PHP (Composer)
├── web/               # DocumentRoot serveur
│   ├── wp/            # WordPress core
│   ├── app/
│   │   ├── themes/    # Thèmes
│   │   ├── plugins/   # Plugins
│   │   └── mu-plugins # Must-use plugins
│   └── uploads/       # Médias
├── .env.example       # Modèle environnement
├── composer.json
└── README.md
```
1. Variables de Connexion (Serveur)

Ces variables permettent au script de savoir où envoyer les fichiers.

    SFTP_HOST : L'adresse de ton serveur (ex: sftp.mon-serveur.com).

    SFTP_PORT : Le port SSH (généralement 22 ou un port spécifique chez o2switch).

    SFTP_USER : Ton nom d'utilisateur SSH/cPanel.

2. Variables d'API (Whitelist o2switch)

Indispensables pour que le runner Bitbucket puisse franchir le pare-feu.

    CPANEL_SERVER : L'hôte de ton interface cPanel (ex: nodexx.o2switch.net).

    CPANEL_LOGIN : Ton identifiant de connexion cPanel.

    CPANEL_TOKEN : Le jeton d'API (API Token) que tu as généré dans ton interface o2switch.

3. Variables de Chemins (Projet)

Celles-ci évitent de modifier le code si tu changes de structure de dossier.

    THEME_DIR : Le chemin relatif de ton thème dans le repo (ex: web/app/themes/mon-theme).

    SFTP_TARGET_DIR : Le chemin absolu sur le serveur où se trouve la racine du projet (ex: /home/user/public_html).
---
