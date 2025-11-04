# 🧩 CapJS Integration Plugin for WordPress

**CapJS Integration** est un plugin WordPress développé par [Mak2com](https://mak2com.fr) pour intégrer le captcha open-source [CapJS](https://capjs.js.org) sur les sites WordPress sans dépendance à Google reCAPTCHA.

---

## 🚀 Fonctionnalités

- Intégration **native** du widget CapJS sur les formulaires WordPress
- **Support Ninja Forms** avec champ personnalisé glisser-déposer
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

## 🎯 Utilisation avec Ninja Forms

### Ajouter le captcha à un formulaire

1. Ouvrez le **constructeur de formulaire Ninja Forms**
2. Dans la liste des champs, cherchez **"CapJS Captcha"** (section "Divers")
3. **Glissez-déposez** le champ où vous voulez qu'il apparaisse dans votre formulaire
4. Configurez les options du champ :
   - **Label** : Texte affiché au-dessus du captcha
   - **Thème** : Clair ou Sombre
5. Enregistrez le formulaire

### Fonctionnement

- Le captcha s'affiche automatiquement à l'endroit où vous avez placé le champ
- La soumission du formulaire est **bloquée** tant que l'utilisateur n'a pas validé le captcha
- Le token est automatiquement envoyé avec les données du formulaire
- La validation côté serveur se fait automatiquement

---

## 🔒 Validation du captcha

### Côté client (JavaScript)

- Le champ écoute l'événement `before:submit` de Ninja Forms
- Si le captcha n'est pas validé, la soumission est annulée
- Un message d'erreur s'affiche : *"Veuillez valider le captcha avant de soumettre le formulaire."*

### Côté serveur (PHP)

- Le filtre `ninja_forms_submit_data` vérifie la présence d'un champ CapJS
- Le token est extrait des données `extra` du formulaire
- Une requête est envoyée au serveur CapJS pour valider le token
- En cas d'échec, une erreur est ajoutée au formulaire

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

### Intégrer votre widget CapJS personnalisé

Pour intégrer votre widget CapJS complet :

1. Assurez-vous que `widget.js` est chargé depuis votre serveur CapJS

2. Dans `widget.js`, exposez une fonction d'initialisation :

```javascript
window.CapJSWidget = {
    init: function(container) {
        // container est l'élément .capjs-widget
        // Initialisez votre widget ici

        // Quand le captcha est validé, stockez le token :
        container.dataset.capjsToken = 'le_token_généré';

        // Déclenchez un événement change si nécessaire
        container.dispatchEvent(new Event('capjs:validated'));
    }
};
```

3. Le champ appellera automatiquement `window.CapJSWidget.init()` lors du rendu

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
2. Vérifiez que **Ninja Forms** est bien installé et activé
3. Vérifiez la console pour les erreurs JavaScript
4. Videz le cache de WordPress

### La validation échoue

1. Vérifiez que l'**URL du serveur CapJS** est correcte
2. Vérifiez que le serveur CapJS est accessible
3. Vérifiez que la **Secret Key** est correcte
4. Regardez les logs de la console réseau (onglet Network / Réseau)

---

## 📁 Structure des fichiers

```
m2c_wpcapjs/
├── m2c-capjs.php                           # Fichier principal du plugin
├── includes/
│   ├── admin.php                           # Page d'administration
│   ├── enqueue.php                         # Chargement des assets
│   └── ninja-forms/
│       ├── field-capjs.php                 # Définition du champ Ninja Forms
│       └── field-capjs-template.html       # Template underscore.js
├── assets/
│   ├── css/
│   │   └── admin.css                       # Styles de l'admin
│   └── js/
│       └── fields/
│           └── capjs-field.js              # Logique front-end du champ
└── README.md                                # Ce fichier
```

---

## ❓ Questions fréquentes

**Q : Puis-je avoir plusieurs captchas dans un même formulaire ?**
R : Non, un seul champ CapJS par formulaire est nécessaire et suffisant.

**Q : Le captcha fonctionne-t-il avec les champs conditionnels de Ninja Forms ?**
R : Oui, le champ CapJS est compatible avec Ninja Forms Conditionals.

**Q : Puis-je personnaliser le message d'erreur ?**
R : Oui, modifiez les chaînes dans `field-capjs.php` et `capjs-field.js`.

**Q : Le captcha fonctionne-t-il en AJAX ?**
R : Oui, Ninja Forms utilise AJAX par défaut et le champ CapJS est totalement compatible.

**Q : Le plugin fonctionne-t-il avec d'autres constructeurs de formulaires ?**
R : Actuellement, seule l'intégration Ninja Forms est disponible. D'autres intégrations (Contact Form 7, WooCommerce) sont prévues.

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
