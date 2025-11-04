<?php
/*
Plugin Name: CapJS Integration by Mak2com
Description: Intègre le captcha CapJS sur les formulaires WordPress (Ninja Forms, Contact Form 7, WooCommerce...).
Version: 1.2.1
Author: Mak2com
*/

if (!defined('ABSPATH')) exit;

// Define constants
define('M2C_CAPJS_DIR', plugin_dir_path(__FILE__));
define('M2C_CAPJS_URL', plugin_dir_url(__FILE__));

// Include files
require_once M2C_CAPJS_DIR . 'admin/settings-page.php';
require_once M2C_CAPJS_DIR . 'includes/enqueue.php';
require_once M2C_CAPJS_DIR . 'includes/inject-widget.php';
require_once M2C_CAPJS_DIR . 'includes/validate.php';

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
