=== CapJS Integration by Mak2com ===
Contributors: mak2com
Requires at least: 6.0
Requires PHP: 8.0
Stable tag: 2.0.1

Intègre le captcha CapJS (auto-hébergé) sur les formulaires WordPress : Ninja Forms, Contact Form 7 et WooCommerce.

== Description ==

CapJS Integration relie vos formulaires à votre propre instance CapJS, sans service tiers ni suivi. Réglages dans Réglages > CapJS : licence, adresse du serveur CapJS, clé du site et clé secrète.

L'extension est fournie sous licence mak2com : la clé se génère sur licences.mak2com.fr et se saisit dans Réglages > CapJS. Elle donne droit au captcha et aux mises à jour automatiques. Fin de licence, ou absence de clé : le captcha est désactivé (il n'est plus affiché ni vérifié), les formulaires du site restent utilisables sans captcha, et la licence renouvelée le rétablit aussitôt. Développement et recette (.local, .test, dev., staging., preprod., recette.) fonctionnent sans clé.

Le serveur de licences reçoit la clé, l'adresse du site et les versions de l'extension, de WordPress et de PHP, au plus une fois par semaine. Pour ne jamais le contacter pour les mises à jour : define( 'M2C_CAPJS_DISABLE_UPDATES', true ); dans wp-config.php.

== Changelog ==

= 2.0.1 =
* Administration redessinée aux couleurs de mak2com (même charte que M2C User Guide et M2C Cookies Banner) : en-tête, cartes, pastille d'état de la licence, boutons et cases à cocher ; libellés reliés à leurs champs.
* Adresse du serveur CapJS préremplie : https://captcha.mak2com.fr (nouvelle instance de l'agence) ; sans effet sur un site où une adresse est déjà enregistrée.

= 2.0.0 =
* Licence mak2com : clé à activer dans Réglages > CapJS (générée sur licences.mak2com.fr), vérifiée hors ligne et confirmée chaque semaine.
* Mises à jour automatiques réservées aux sites sous licence valide.
* Fin de licence ou absence de clé : le captcha est désactivé (formulaires utilisables sans captcha), avec un avertissement dans l'administration ; une licence renouvelée le rétablit aussitôt.
* Site déjà configuré avant la 2.0.0 : délai de reprise de 30 jours pour saisir la clé, le captcha continue de fonctionner pendant ce délai.
* Dossier de l'extension conservé à la mise à jour, même installée depuis une archive GitHub.

= 1.5.0 =
* Mises à jour proposées automatiquement par M2C Licences, sans clé de licence (remplacé par la licence en 2.0.0).

= 1.4.2 =
* Langue du widget et libellés traduisibles avec WPML.
* Scripts chargés seulement sur les pages qui affichent un champ protégé.
* Contraste de la case à cocher (RGAA).
