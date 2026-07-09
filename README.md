# 🧩 CapJS Integration Plugin for WordPress

**CapJS Integration** est un plugin WordPress développé par [Mak2com](https://mak2com.fr) pour intégrer le captcha open-source [CapJS](https://capjs.js.org) sur les sites WordPress sans dépendance à Google reCAPTCHA.

---

## 🚀 Fonctionnalités

- Intégration **native** du widget CapJS sur les formulaires WordPress
- **Support Ninja Forms** avec champ personnalisé glisser-déposer
- **Support Contact Form 7** avec validation automatique
- **Support WooCommerce** pour protéger les formulaires d'inscription, connexion, checkout et avis produits
- Page d'administration pour configurer les clés CapJS (`site key` et `secret key`)
- Validation serveur du `cap-token` via votre instance CapJS self-hosted
- Code léger, sans tracking, 100 % open-source
- Compatible avec les formulaires AJAX

---

## ⚙️ Installation

### 1️⃣ Pré-requis

- WordPress ≥ 6.0
- PHP ≥ 8.0
- Une instance **CapJS** accessible (ex. `https://capjs.domaine.com`)
- Votre instance CapJS doit être fonctionnelle avant l'installation du plugin

### 2️⃣ Installation du plugin

1. Téléversez ou clonez le plugin dans le dossier :
   ```bash
   /wp-content/plugins/m2c-capjs
   ```

2. Activez le plugin depuis l'interface d'administration WordPress

3. Allez dans **Réglages → CapJS Integration**

4. Configurez vos paramètres :
   - **URL du serveur CapJS** : L'URL de votre instance CapJS (ex. `https://capjs.domaine.com`)
   - **Site Key** : Votre clé publique CapJS
   - **Secret Key** : Votre clé secrète CapJS

---

## 🎯 Utilisation

### Avec Ninja Forms

#### Ajouter le captcha à un formulaire

1. Ouvrez le **constructeur de formulaire Ninja Forms**
2. Dans la liste des champs, cherchez **"CapJS Captcha"** (section "Divers")
3. **Glissez-déposez** le champ où vous voulez qu'il apparaisse dans votre formulaire
4. Configurez les options du champ :
   - **Label** : Texte affiché au-dessus du captcha
   - **Thème** : Clair ou Sombre
5. Enregistrez le formulaire

#### Fonctionnement

- Le captcha s'affiche automatiquement à l'endroit où vous avez placé le champ
- La soumission du formulaire est **bloquée** tant que l'utilisateur n'a pas validé le captcha
- Le token est automatiquement envoyé avec les données du formulaire
- La validation côté serveur se fait automatiquement

---

### Avec Contact Form 7

#### Ajouter le captcha à un formulaire

1. Ouvrez le **formulaire Contact Form 7** que vous souhaitez protéger
2. Dans l'éditeur de formulaire, ajoutez le shortcode :
   ```
   [capjs]
   ```
3. Placez-le où vous voulez qu'il apparaisse (généralement avant le bouton de soumission)
4. Enregistrez le formulaire

#### Options du shortcode

Le shortcode `[capjs]` supporte une option de thème :

- `[capjs]` - Thème clair (par défaut)
- `[capjs dark]` - Thème sombre

#### Fonctionnement

- Le captcha s'affiche automatiquement à l'emplacement du shortcode
- La soumission du formulaire est **bloquée** tant que l'utilisateur n'a pas validé le captcha
- Le token est automatiquement envoyé avec les données du formulaire
- La validation côté serveur se fait automatiquement
- En cas d'échec, un message d'erreur s'affiche : *"La validation du captcha a échoué. Veuillez réessayer."*

---

### Avec WooCommerce

#### Formulaires protégés

Lorsque WooCommerce est installé et activé, CapJS peut protéger les formulaires suivants :

1. **Formulaire d'inscription** (`/my-account/register`) - **Activé par défaut**
2. **Formulaire de connexion** (`/my-account/login`) - **Activé par défaut**
3. **Formulaire de paiement/checkout** (`/checkout`) - **Désactivé par défaut**
4. **Formulaire d'avis produit** (sur les pages produits) - **Désactivé par défaut**

#### Configuration WooCommerce

Depuis la version **1.4.0**, vous pouvez activer/désactiver sélectivement la protection sur chaque formulaire WooCommerce.

Allez dans **Réglages → CapJS** et configurez la section **"Protection WooCommerce"** :

- ✅ **Formulaire de connexion** : Recommandé pour la sécurité des comptes utilisateurs
- ✅ **Formulaire d'inscription** : Recommandé pour prévenir le spam et les inscriptions frauduleuses
- ⚠️ **Formulaire de paiement (checkout)** : Désactivé par défaut pour éviter les problèmes de compatibilité avec les checkouts multi-étapes
- ℹ️ **Avis produits** : Protection optionnelle contre les faux avis

#### ⚠️ Compatibilité checkout multi-étapes

Si vous utilisez un plugin de checkout multi-étapes (ex: WooCommerce Multistep Checkout, Checkout Manager, etc.), il est **fortement recommandé** de **désactiver la protection sur le checkout**.

**Pourquoi ?**
Le widget CapJS peut ne pas persister entre les différentes étapes du checkout, ce qui empêche la validation finale de la commande.

**Solution :**
1. Allez dans **Réglages → CapJS**
2. Dans la section **Protection WooCommerce**, décochez **"Formulaire de paiement (checkout)"**
3. Enregistrez les modifications

Les formulaires de connexion et d'inscription resteront protégés, assurant la sécurité sans impacter le tunnel de paiement

#### Fonctionnement

- Le captcha s'affiche automatiquement avant le bouton de soumission
- La soumission du formulaire est **bloquée** tant que l'utilisateur n'a pas validé le captcha
- Le token est automatiquement envoyé avec les données du formulaire
- La validation côté serveur se fait automatiquement
- Compatible avec les mises à jour AJAX de WooCommerce (checkout dynamique)

#### Personnalisation

Pour personnaliser l'apparence du widget dans WooCommerce, ajoutez du CSS ciblant `.capjs-woocommerce` :

```css
.capjs-woocommerce cap-widget {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}
```

#### Test et validation

Pour tester l'intégration WooCommerce :

1. **Formulaire d'inscription** :
   - Aller sur `/my-account/` (déconnecté)
   - Vérifier que le widget s'affiche dans le formulaire d'inscription
   - Tenter de s'inscrire sans valider → Doit échouer avec message d'erreur
   - Valider le captcha et s'inscrire → Doit réussir

2. **Formulaire de connexion** :
   - Se déconnecter et aller sur `/my-account/`
   - Vérifier que le widget s'affiche
   - Tester la validation

3. **Formulaire de checkout** :
   - Ajouter un produit au panier et aller sur `/checkout/`
   - Vérifier que le widget s'affiche
   - Tester avec et sans validation du captcha
   - Vérifier la compatibilité avec les mises à jour AJAX

4. **Formulaire d'avis produit** :
   - Se connecter et aller sur une page produit
   - Vérifier que le widget s'affiche dans le formulaire d'avis
   - Tester la validation

---

## 🔒 Validation du captcha

### Côté client (JavaScript)

**Ninja Forms :**
- Le champ écoute l'événement `before:submit`
- Si le captcha n'est pas validé, la soumission est annulée
- Un message d'erreur s'affiche : *"Veuillez valider le captcha avant de soumettre le formulaire."*

**Contact Form 7 :**
- Le captcha écoute l'événement `wpcf7submit`
- Le token est automatiquement ajouté au formulaire avant la soumission
- En cas de validation échouée, le formulaire affiche l'erreur retournée par le serveur

**WooCommerce :**
- Le token est mis à jour régulièrement via un intervalle JavaScript
- Lors de la soumission, le token est vérifié avant l'envoi
- Compatible avec l'événement `checkout_place_order` pour le formulaire de paiement
- Gère automatiquement les mises à jour AJAX du checkout

### Côté serveur (PHP)

**Ninja Forms :**
- La méthode `validate()` du champ CapJS est appelée par Ninja Forms lors du traitement de la soumission
- Le token correspond à la valeur du champ (mise à jour par le JavaScript)
- Une requête est envoyée au serveur CapJS pour valider le token
- En cas d'échec, une erreur est ajoutée au formulaire

**Contact Form 7 :**
- Le filtre `wpcf7_spam` vérifie la présence du shortcode `[capjs]`
- Le token `cap-token` (ou le champ caché `_wpcf7_capjs_token`) est extrait des données POST
- Une requête est envoyée au serveur CapJS pour valider le token
- En cas d'échec, la soumission est marquée comme spam et bloquée

**WooCommerce :**
- Filtres de validation pour chaque type de formulaire :
  - `woocommerce_registration_errors` pour l'inscription
  - `woocommerce_process_login_errors` pour la connexion
  - `preprocess_comment` pour les avis produits
  - `woocommerce_after_checkout_validation` pour le checkout
- Le token `capjs_wc_token` est extrait des données POST
- Une requête est envoyée au serveur CapJS pour valider le token
- En cas d'échec, une erreur est ajoutée et la soumission est bloquée

---

## 🎨 Personnalisation

### Modifier le style du widget

Ajoutez du CSS personnalisé à votre thème :

```css
.capjs-widget-container {
    margin: 20px 0;
}

.capjs-widget {
    padding: 20px;
    border: 2px solid #0073aa;
    border-radius: 8px;
    background: #fff;
}
```

---

## 🐛 Débogage

### Logs dans la console

Ouvrez la console du navigateur (F12) pour voir les logs :

```
[CapJS] Widget initialisé pour le champ 123
[CapJS] Token généré: capjs_token_abc123
[CapJS] Validation avant soumission: true capjs_token_abc123
[CapJS] Token ajouté à la soumission: capjs_token_abc123
```

### Le widget ne s'affiche pas

1. Vérifiez que la **Site Key** est configurée dans les réglages
2. Vérifiez que **Ninja Forms**, **Contact Form 7** ou **WooCommerce** est bien installé et activé
3. Pour Contact Form 7, vérifiez que le shortcode `[capjs]` est présent dans le formulaire
4. Pour WooCommerce, vérifiez que vous êtes sur une page compatible (my-account, checkout, produit)
5. Vérifiez la console pour les erreurs JavaScript
6. Videz le cache de WordPress

### La validation échoue

1. Vérifiez que l'**URL du serveur CapJS** est correcte
2. Vérifiez que le serveur CapJS est accessible
3. Vérifiez que la **Secret Key** est correcte
4. Regardez les logs de la console réseau (onglet Network / Réseau)
5. Activez `WP_DEBUG` et vérifiez le fichier `wp-content/debug.log`

### Le widget disparaît après une mise à jour AJAX (WooCommerce)

1. Vérifiez que le script `capjs-woocommerce.js` est bien chargé
2. Ouvrez la console développeur et activez le mode debug en modifiant `capjs-woocommerce.js` (ligne 10 : `var DEBUG = true;`)
3. Vérifiez que les événements WooCommerce sont bien écoutés (`updated_checkout`, `wc_fragments_refreshed`)

### Le checkout WooCommerce est bloqué avec un checkout multi-étapes

**Symptômes :**
- Le widget CapJS s'affiche sur la première étape du checkout
- À l'étape finale, le formulaire est rejeté avec une erreur "Veuillez valider le captcha"
- Le widget n'est pas visible sur l'étape finale

**Solution :**
1. Allez dans **Réglages → CapJS**
2. Dans la section **Protection WooCommerce**, décochez **"Formulaire de paiement (checkout)"**
3. Enregistrez les modifications
4. Videz le cache WordPress si nécessaire
5. Testez une nouvelle commande

**Vérification :**
- Le widget ne doit plus apparaître sur le checkout
- La commande doit passer sans erreur de captcha
- Les formulaires de connexion/inscription restent protégés

**Note :** Si vous n'utilisez PAS de checkout multi-étapes et souhaitez protéger le checkout, vous pouvez réactiver cette option en toute sécurité.

---

## 📁 Structure des fichiers

```
m2c_wpcapjs/
├── m2c-wpcapjs.php                         # Fichier principal du plugin
├── admin/
│   └── settings-page.php                   # Page d'administration
├── includes/
│   ├── enqueue.php                         # Chargement des assets + template Ninja Forms
│   ├── ninja-forms/
│   │   └── field-capjs.php                 # Définition et validation du champ Ninja Forms
│   ├── contact-form-7/
│   │   ├── capjs-cf7.php                   # Intégration Contact Form 7
│   │   └── service.php                     # Service CapJS pour CF7
│   └── woocommerce/
│       └── capjs-woocommerce.php           # Intégration WooCommerce
├── assets/
│   └── js/
│       ├── capjs-custom.js                 # Traduction du widget
│       ├── capjs-cf7.js                    # Logique Contact Form 7
│       └── capjs-woocommerce.js            # Logique WooCommerce
└── README.md                                # Ce fichier
```

---

## ❓ Questions fréquentes

**Q : Puis-je avoir plusieurs captchas dans un même formulaire ?**
R : Non, un seul captcha CapJS par formulaire est nécessaire et suffisant.

**Q : Le captcha fonctionne-t-il avec les champs conditionnels de Ninja Forms ?**
R : Oui, le champ CapJS est compatible avec Ninja Forms Conditionals.

**Q : Puis-je personnaliser l'apparence du captcha dans Contact Form 7 ?**
R : Oui, utilisez les options `theme` et `label` dans le shortcode, ou ajoutez du CSS personnalisé ciblant `.capjs-widget-container`.

**Q : Puis-je personnaliser l'apparence du captcha dans WooCommerce ?**
R : Oui, ajoutez du CSS personnalisé ciblant `.capjs-woocommerce cap-widget` dans votre thème.

**Q : Puis-je personnaliser le message d'erreur ?**
R : Oui, modifiez les chaînes dans les fichiers d'intégration :
- Ninja Forms : `field-capjs.php` et le script inline dans `enqueue.php`
- Contact Form 7 : `capjs-cf7.php`
- WooCommerce : `capjs-woocommerce.php`

**Q : Le captcha fonctionne-t-il en AJAX ?**
R : Oui, Ninja Forms et WooCommerce utilisent AJAX et le plugin CapJS est totalement compatible avec ces systèmes.

**Q : Puis-je désactiver CapJS sur certains formulaires WooCommerce ?**
R : Oui ! Depuis la version 1.4.0, vous pouvez activer/désactiver sélectivement chaque formulaire WooCommerce dans **Réglages → CapJS → Protection WooCommerce**. Cette fonctionnalité est particulièrement utile pour les sites utilisant des checkouts multi-étapes.

**Q : Pourquoi la protection checkout est-elle désactivée par défaut ?**
R : Les checkouts multi-étapes et certains plugins de paiement peuvent avoir des problèmes de compatibilité avec le widget CapJS. Pour éviter de bloquer les commandes par défaut, cette protection est optionnelle. Les formulaires de connexion et d'inscription restent protégés par défaut pour assurer la sécurité.

**Q : Le plugin fonctionne-t-il avec d'autres constructeurs de formulaires ?**
R : Actuellement, le plugin supporte **Ninja Forms**, **Contact Form 7** et **WooCommerce**. D'autres intégrations (Gravity Forms, Elementor Forms) sont prévues.

**Q : Dois-je héberger moi-même CapJS ?**
R : Oui, ce plugin nécessite une instance CapJS self-hosted accessible via HTTPS.

---

## 🔄 Mises à jour et support

- **Documentation CapJS** : [capjs.js.org](https://capjs.js.org)
- **Support** : [Mak2com](https://mak2com.fr)
- **GitHub** : Issues et contributions bienvenues

---

## 📝 Licence

Ce plugin est open-source et distribué sous licence MIT.

**Développé avec ❤️ par [Mak2com](https://mak2com.fr)**
