<?php
/*
Plugin Name: CapJS Integration by Mak2com
Description: Intègre le captcha CapJS sur les formulaires WordPress (Ninja Forms, Contact Form 7, WooCommerce...).
Version: 1.5.0
Author: Mak2com
Author URI: https://mak2com.fr
Text Domain: capjs-integration
*/

if (!defined('ABSPATH')) exit;

// Define constants
define('M2C_CAPJS_VERSION', '1.5.0');
define('M2C_CAPJS_FILE', __FILE__);
define('M2C_CAPJS_DIR', plugin_dir_path(__FILE__));
define('M2C_CAPJS_URL', plugin_dir_url(__FILE__));

/**
 * Enregistrer un libellé CapJS dans WPML et le retourner dans la langue active.
 * Sans WPML, les hooks conservent le libellé français historique.
 */
function m2c_capjs_translate_single_string($name, $default) {
    do_action('wpml_register_single_string', 'capjs-integration', $name, $default);

    return (string) apply_filters(
        'wpml_translate_single_string',
        $default,
        'capjs-integration',
        $name
    );
}

/**
 * Retourner le code de langue à deux lettres attendu par le composant CapJS.
 */
function m2c_capjs_get_widget_language() {
    $language = apply_filters('wpml_current_language', null);

    if (!is_string($language) || '' === $language) {
        $language = determine_locale();
    }

    $language = strtolower(str_replace('_', '-', $language));

    return preg_match('/^[a-z]{2}/', $language) ? substr($language, 0, 2) : 'fr';
}

/**
 * Retourner les libellés du composant CapJS destinés aux visiteurs.
 */
function m2c_capjs_get_widget_strings() {
    return array(
        'language' => m2c_capjs_get_widget_language(),
        'humanLabel' => m2c_capjs_translate_single_string('widget_human_label', 'Je suis un humain'),
        'verificationComplete' => m2c_capjs_translate_single_string('verification_complete', 'Vérification terminée'),
        'verificationFailed' => m2c_capjs_translate_single_string('verification_failed', 'Échec de la vérification'),
    );
}

// Rendre les chaînes disponibles dans WPML dès un rechargement de l'administration,
// sans imposer une première visite sur un formulaire public.
add_action('init', 'm2c_capjs_get_widget_strings', 20);

// Include files
require_once M2C_CAPJS_DIR . 'admin/settings-page.php';
require_once M2C_CAPJS_DIR . 'includes/enqueue.php';
require_once M2C_CAPJS_DIR . 'includes/updater.php';

/**
 * Migration des paramètres lors des mises à jour
 */
add_action('plugins_loaded', function() {
    $current_version = get_option('m2c_capjs_version', '0');
    $new_version = '1.4.1';

    // Migration pour la v1.4.0 : options WooCommerce granulaires
    if (version_compare($current_version, '1.4.0', '<')) {
        // Vérifier si CapJS est déjà configuré
        $server_url = get_option('m2c_capjs_server_url');
        $site_key = get_option('m2c_capjs_site_key');
        $is_configured = !empty($server_url) && !empty($site_key);

        // Si CapJS était déjà configuré, maintenir le comportement existant
        // en activant login et register (ils étaient actifs avant)
        if ($is_configured) {
            // Activer login et register si pas déjà définis
            if (get_option('m2c_capjs_woocommerce_login') === false) {
                update_option('m2c_capjs_woocommerce_login', true);
            }
            if (get_option('m2c_capjs_woocommerce_register') === false) {
                update_option('m2c_capjs_woocommerce_register', true);
            }
        }

        // Checkout et reviews restent désactivés par défaut (valeurs par défaut)
        // Pas besoin de les définir explicitement

        // Mettre à jour la version
        update_option('m2c_capjs_version', $new_version);
    }
}, 5); // Priorité 5 pour s'exécuter avant les autres hooks plugins_loaded

/**
 * Charger l'intégration Ninja Forms au bon moment
 */
add_action('plugins_loaded', function() {
    if (class_exists('Ninja_Forms')) {
        require_once M2C_CAPJS_DIR . 'includes/ninja-forms/field-capjs.php';
    }
}, 20); // Priorité 20 pour s'assurer que Ninja Forms est bien chargé

/**
 * Charger l'intégration Contact Form 7 au bon moment
 */
add_action('plugins_loaded', function() {
    if (class_exists('WPCF7')) {
        require_once M2C_CAPJS_DIR . 'includes/contact-form-7/capjs-cf7.php';
    }
}, 20); // Priorité 20 pour s'assurer que Contact Form 7 est bien chargé

/**
 * Charger l'intégration WooCommerce au bon moment
 */
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        require_once M2C_CAPJS_DIR . 'includes/woocommerce/capjs-woocommerce.php';
    }
}, 20); // Priorité 20 pour s'assurer que WooCommerce est bien chargé

/**
 * Vider le cache Ninja Forms lors de l'activation du plugin
 */
register_activation_hook(__FILE__, function() {
    if (class_exists('Ninja_Forms')) {
        // Vider le cache des champs
        delete_transient('nf_form_fields');
        delete_option('ninja_forms_field_cache');
        delete_option('ninja_forms_optin_reported');

        // Forcer Ninja Forms à recharger les champs
        if (function_exists('Ninja_Forms')) {
            $cache_all = Ninja_Forms()->get_setting('cache_all', false);
            if ($cache_all) {
                Ninja_Forms()->update_setting('cache_all', false);
                Ninja_Forms()->update_setting('cache_all', true);
            }
        }
    }
});
