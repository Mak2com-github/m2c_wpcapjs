<?php
if (!defined('ABSPATH')) exit;

/**
 * Mises à jour depuis M2C Licences (https://licences.mak2com.fr), sans bibliothèque :
 * le format est celui de plugin-update-checker, servi par deux filtres WordPress et un cache.
 *
 * Les mises à jour sont réservées aux sites sous licence valide (includes/license.php) : le serveur reçoit
 * la clé de licence, l'adresse du site (hôte) et les versions de l'extension, de WordPress et de PHP ;
 * rien d'autre, jamais de réglage CapJS. Sans licence valide, aucun appel n'est fait.
 *
 * Une panne ou une absence de réponse du serveur ne change rien au fonctionnement du site.
 *
 * Réglages possibles dans wp-config.php :
 *   define('M2C_CAPJS_DISABLE_UPDATES', true);  // ne jamais contacter le serveur
 *   define('M2C_CAPJS_UPDATE_URL', 'https://…/updates/');  // autre serveur (essais)
 */

// Identifiant du produit sur M2C Licences (dossier de l'extension) et cache des métadonnées.
define('M2C_CAPJS_UPDATE_SLUG', 'm2c_wpcapjs');
define('M2C_CAPJS_UPDATE_TRANSIENT', 'm2c_capjs_update_info');

/**
 * Adresse du serveur de mises à jour.
 */
function m2c_capjs_update_url() {
    $url = defined('M2C_CAPJS_UPDATE_URL') ? (string) M2C_CAPJS_UPDATE_URL : 'https://licences.mak2com.fr/updates/';

    return (string) apply_filters('m2c_capjs_update_url', $url);
}

function m2c_capjs_updates_disabled() {
    return defined('M2C_CAPJS_DISABLE_UPDATES') && M2C_CAPJS_DISABLE_UPDATES;
}

/**
 * Hôte du site en minuscules, sans « www. » (comme M2C Licences le normalise).
 */
function m2c_capjs_update_host() {
    $host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));

    return (string) preg_replace('/^www\./', '', $host);
}

/**
 * Métadonnées de la dernière version publiée, ou null (mises à jour coupées, serveur injoignable ou réponse douteuse).
 * Le résultat est conservé 6 h (1 h après un échec) ; $force ignore le cache.
 */
function m2c_capjs_update_remote($force = false) {
    $update_url = m2c_capjs_update_url();
    if (m2c_capjs_updates_disabled() || '' === $update_url) return null;

    $license_key = m2c_capjs_license_key();
    if ('' === $license_key || !m2c_capjs_license_premium()) return null;

    $cached = get_site_transient(M2C_CAPJS_UPDATE_TRANSIENT);
    if (!$force && is_array($cached)) {
        return isset($cached['data']) ? $cached['data'] : null;
    }

    $response = wp_remote_get(
        add_query_arg(
            array(
                'action'  => 'get_metadata',
                'slug'    => M2C_CAPJS_UPDATE_SLUG,
                'license' => $license_key,
                'site'    => m2c_capjs_update_host(),
                'version' => M2C_CAPJS_VERSION,
                'wp'      => get_bloginfo('version'),
                'php'     => PHP_VERSION,
            ),
            $update_url
        ),
        array('timeout' => 8)
    );

    $data = null;
    if (!is_wp_error($response) && 200 === (int) wp_remote_retrieve_response_code($response)) {
        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        if (m2c_capjs_update_is_valid($json, $update_url)) {
            $data = $json;
        }
    }

    set_site_transient(M2C_CAPJS_UPDATE_TRANSIENT, array('data' => $data), $data ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS);

    return $data;
}

/**
 * Réponse exploitable : version plausible et archive hébergée par le serveur de mises à jour lui-même.
 */
function m2c_capjs_update_is_valid($json, $update_url) {
    if (!is_array($json) || empty($json['version']) || empty($json['download_url'])) return false;
    if (!is_string($json['version']) || !preg_match('/^\d+(\.\d+){1,3}([-+][0-9A-Za-z.-]+)?$/', $json['version'])) return false;

    $package = wp_parse_url((string) $json['download_url']);
    $server  = wp_parse_url($update_url);

    return !empty($package['host']) && !empty($server['host'])
        && !empty($package['scheme']) && in_array($package['scheme'], array('http', 'https'), true)
        && strtolower($package['host']) === strtolower($server['host']);
}

/**
 * Propose la mise à jour à WordPress ; l'élément reste dans « no_update » quand le site est à jour,
 * ce qui garde le bouton « Activer les mises à jour automatiques » de la page Extensions.
 */
function m2c_capjs_update_inject($transient) {
    if (!is_object($transient)) return $transient;

    $data = m2c_capjs_update_remote();
    if (!$data) return $transient;

    $plugin = plugin_basename(M2C_CAPJS_FILE);
    $item   = (object) array(
        'id'           => $plugin,
        'slug'         => M2C_CAPJS_UPDATE_SLUG,
        'plugin'       => $plugin,
        'new_version'  => (string) $data['version'],
        'url'          => isset($data['homepage']) ? (string) $data['homepage'] : 'https://mak2com.fr',
        'package'      => (string) $data['download_url'],
        'requires'     => isset($data['requires']) ? (string) $data['requires'] : '',
        'tested'       => isset($data['tested']) ? (string) $data['tested'] : '',
        'requires_php' => isset($data['requires_php']) ? (string) $data['requires_php'] : '',
        'icons'        => array(),
    );

    if (!isset($transient->response) || !is_array($transient->response)) $transient->response = array();
    if (!isset($transient->no_update) || !is_array($transient->no_update)) $transient->no_update = array();

    if (version_compare((string) $data['version'], M2C_CAPJS_VERSION, '>')) {
        $transient->response[$plugin] = $item;
        unset($transient->no_update[$plugin]);
    } else {
        $transient->no_update[$plugin] = $item;
        unset($transient->response[$plugin]);
    }

    return $transient;
}

/**
 * Fenêtre « Voir les détails de la version » de la page Extensions.
 */
function m2c_capjs_update_details($result, $action, $args) {
    if ('plugin_information' !== $action || !isset($args->slug) || M2C_CAPJS_UPDATE_SLUG !== $args->slug) return $result;

    $data = m2c_capjs_update_remote();
    if (!$data) return $result;

    return (object) array(
        'name'          => isset($data['name']) ? (string) $data['name'] : 'CapJS Integration by Mak2com',
        'slug'          => M2C_CAPJS_UPDATE_SLUG,
        'version'       => (string) $data['version'],
        'author'        => '<a href="https://mak2com.fr">mak2com</a>',
        'homepage'      => isset($data['homepage']) ? (string) $data['homepage'] : 'https://mak2com.fr',
        'requires'      => isset($data['requires']) ? (string) $data['requires'] : '',
        'tested'        => isset($data['tested']) ? (string) $data['tested'] : '',
        'requires_php'  => isset($data['requires_php']) ? (string) $data['requires_php'] : '',
        'last_updated'  => isset($data['last_updated']) ? (string) $data['last_updated'] : '',
        'download_link' => (string) $data['download_url'],
        'sections'      => array_map('wp_kses_post', isset($data['sections']) && is_array($data['sections']) ? $data['sections'] : array()),
    );
}

/**
 * L'archive contient le dossier « m2c_wpcapjs/ » ; il est renommé comme le dossier réellement installé
 * (ex. « m2c_wpcapjs-main » après un téléchargement GitHub) pour que la mise à jour remplace l'extension
 * au lieu d'en installer une seconde copie.
 */
function m2c_capjs_update_fix_source($source, $remote_source, $upgrader, $hook_extra = array()) {
    global $wp_filesystem;

    $plugin = plugin_basename(M2C_CAPJS_FILE);
    if (empty($hook_extra['plugin']) || $plugin !== $hook_extra['plugin']) return $source;

    $folder = dirname($plugin);
    if ('.' === $folder || '' === $folder) return $source;

    $wanted = trailingslashit($remote_source) . $folder . '/';
    if (trailingslashit($source) === $wanted) return $source;

    if (!$wp_filesystem || !$wp_filesystem->move($source, $wanted, true)) {
        return new WP_Error('m2c_capjs_update_folder', __('Impossible de renommer le dossier de la mise à jour de CapJS Integration.', 'capjs-integration'));
    }

    return $wanted;
}

function m2c_capjs_update_clear_cache() {
    delete_site_transient(M2C_CAPJS_UPDATE_TRANSIENT);
}

/**
 * « Vérifier à nouveau » de WordPress (Tableau de bord > Mises à jour) : ignorer le cache de 6 h.
 */
function m2c_capjs_update_force_check() {
    if (isset($_GET['force-check'])) m2c_capjs_update_clear_cache(); // phpcs:ignore WordPress.Security.NonceVerification
}

if (!m2c_capjs_updates_disabled()) {
    add_filter('pre_set_site_transient_update_plugins', 'm2c_capjs_update_inject');
    add_filter('plugins_api', 'm2c_capjs_update_details', 10, 3);
    add_filter('upgrader_source_selection', 'm2c_capjs_update_fix_source', 10, 4);
    add_action('upgrader_process_complete', 'm2c_capjs_update_clear_cache', 10, 0);
    add_action('load-update-core.php', 'm2c_capjs_update_force_check');
}
