# 🧩 CapJS Integration Plugin for WordPress

**CapJS Integration** est un plugin WordPress développé par [Mak2com](https://mak2com.fr) pour intégrer le captcha open-source [CapJS](https://capjs.js.org) sur les sites WordPress, WooCommerce ou PrestaShop sans dépendance à Google reCAPTCHA.

---

## 🚀 Fonctionnalités

- Intégration **native** du widget CapJS sur les formulaires WordPress (Ninja Forms, Contact Form 7, WooCommerce…)
- Page d’administration pour définir les clés CapJS (`site key` et `secret key`)
- Injection automatique du captcha sur les formulaires publics
- Validation serveur du `cap-token` via ton instance CapJS self-hosted
- Code léger, sans tracking, 100 % open-source

---

## ⚙️ Installation

### 1️⃣ Pré-requis
- WordPress ≥ 6.0
- PHP ≥ 8.0
- Une instance **CapJS** accessible (ex. `https://cap.mak2com.fr`)
- Ton instance doit être fonctionnelle avant l’installation du plugin

### 2️⃣ Installation du plugin
Téléverse ou clone le plugin dans le dossier :
```bash
/wp-content/plugins/m2c-capjs

