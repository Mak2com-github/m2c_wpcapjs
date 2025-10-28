# Intégration CapJS avec Ninja Forms

## 🎯 Vue d'ensemble

Ce plugin ajoute un champ personnalisé **CapJS Captcha** dans le constructeur de formulaires Ninja Forms, permettant de placer le captcha exactement où vous le souhaitez dans votre formulaire.

## 📦 Installation

1. Activez le plugin "CapJS Integration by Mak2com"
2. Configurez les paramètres CapJS (URL du serveur et Site Key) dans les réglages WordPress
3. Le champ "CapJS Captcha" sera automatiquement disponible dans Ninja Forms

## 🔧 Utilisation

### Ajouter le captcha à un formulaire

1. Ouvrez le constructeur de formulaire Ninja Forms
2. Dans la liste des champs, cherchez **"CapJS Captcha"** (section "Divers")
3. Glissez-déposez le champ où vous voulez qu'il apparaisse
4. Configurez les options du champ :
   - **Label** : Texte affiché au-dessus du captcha
   - **Thème** : Clair ou Sombre
5. Enregistrez le formulaire

### Fonctionnement

- Le captcha s'affiche automatiquement à l'endroit où vous avez placé le champ
- La soumission du formulaire est **bloquée** tant que l'utilisateur n'a pas validé le captcha
- Le token est automatiquement envoyé avec les données du formulaire
- La validation côté serveur se fait automatiquement

## 🔒 Validation

### Côté client (JavaScript)
- Le champ écoute l'événement `before:submit` de Ninja Forms
- Si le captcha n'est pas validé, la soumission est annulée
- Un message d'erreur s'affiche : "Veuillez valider le captcha avant de soumettre le formulaire."

### Côté serveur (PHP)
- Le filtre `ninja_forms_submit_data` vérifie la présence d'un champ CapJS
- Le token est extrait des données `extra` du formulaire
- Une requête est envoyée au serveur CapJS pour valider le token
- En cas d'échec, une erreur est ajoutée au formulaire

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

### Intégrer le vrai widget CapJS

Le code actuel utilise une checkbox simple pour le développement. Pour intégrer votre widget CapJS complet :

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

## 🐛 Débogage

Ouvrez la console du navigateur (F12) pour voir les logs :

```
[CapJS] Widget initialisé pour le champ 123
[CapJS] Token généré: capjs_token_abc123
[CapJS] Validation avant soumission: true capjs_token_abc123
[CapJS] Token ajouté à la soumission: capjs_token_abc123
```

Si le widget ne s'affiche pas :
1. Vérifiez que la Site Key est configurée dans les réglages
2. Vérifiez que Ninja Forms est bien installé et activé
3. Vérifiez la console pour les erreurs JavaScript
4. Videz le cache de WordPress

Si la validation échoue :
1. Vérifiez que l'URL du serveur CapJS est correcte
2. Vérifiez que le serveur CapJS est accessible
3. Regardez les logs de la console réseau (onglet Network)

## 📁 Structure des fichiers

```
m2c_wpcapjs/
├── includes/
│   └── ninja-forms/
│       ├── field-capjs.php              # Définition du champ
│       └── field-capjs-template.html    # Template underscore.js
├── assets/
│   └── js/
│       └── fields/
│           └── capjs-field.js           # Logique front-end du champ
└── includes/
    └── enqueue.php                       # Chargement des assets
```

## 🔄 Migration depuis l'ancien système

Si vous utilisiez l'ancien système d'injection automatique :

1. Les formulaires existants continueront de fonctionner
2. Pour les nouveaux formulaires, utilisez le champ personnalisé
3. L'ancien script `capjs-ninjaforms.js` a été désactivé (renommé en `.old`)

## ❓ Questions fréquentes

**Q : Puis-je avoir plusieurs captchas dans un même formulaire ?**
R : Non, un seul champ CapJS par formulaire est nécessaire et suffisant.

**Q : Le captcha fonctionne-t-il avec les champs conditionnels ?**
R : Oui, le champ CapJS est compatible avec Ninja Forms Conditionals.

**Q : Puis-je personnaliser le message d'erreur ?**
R : Oui, modifiez les chaînes dans `field-capjs.php` et `capjs-field.js`.

**Q : Le captcha fonctionne-t-il en AJAX ?**
R : Oui, Ninja Forms utilise AJAX par défaut et le champ CapJS est compatible.
