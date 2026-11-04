<?php
if (!defined('ABSPATH')) exit;

/**
 * Formulaires codés en dur (modèles du thème, blocs HTML, constructeurs de pages) : le shortcode [capjs_widget] affiche
 * le widget et m2c_capjs_verify_request() vérifie le jeton dans le traitement du formulaire.
 *
 * Même règle que les autres intégrations : sans configuration ou sans licence valide, le captcha est désactivé, donc
 * le widget n'est pas affiché ET la vérification réussit (les formulaires restent utilisables sans captcha).
 */

/**
 * Le captcha est-il actif sur ce site (serveur et clé du site renseignés, licence valide) ?
 */
function m2c_capjs_is_active() {
    return !empty(get_option('m2c_capjs_server_url', '')) && !empty(get_option('m2c_capjs_site_key', '')) && m2c_capjs_license_runs();
}

/**
 * Code HTML du widget (vide si le captcha est désactivé) ; charge les scripts en pied de page.
 *
 * @param string $id Identifiant HTML du widget (facultatif : un identifiant unique est généré).
 */
function m2c_capjs_render_widget($id = '') {
    if (!m2c_capjs_is_active()) return '';

    m2c_capjs_enqueue_widget();
    $strings = m2c_capjs_get_widget_strings();
    $id = '' !== $id ? sanitize_html_class($id) : '';
    if ('' === $id) $id = 'cap-widget-' . wp_unique_id();

    return sprintf(
        '<cap-widget id="%s" data-cap-api-endpoint="%s" data-cap-lang="%s" data-cap-i18n-initial-state="%s" data-cap-i18n-solved-label="%s" data-cap-i18n-error-label="%s" style="display:block;margin:10px 0;"></cap-widget>',
        esc_attr($id),
        esc_url(trailingslashit(get_option('m2c_capjs_server_url', '')) . get_option('m2c_capjs_site_key', '') . '/'),
        esc_attr($strings['language']),
        esc_attr($strings['humanLabel']),
        esc_attr($strings['verificationComplete']),
        esc_attr($strings['verificationFailed'])
    );
}

/**
 * Shortcode [capjs_widget] ou [capjs_widget id="mon-widget"] : à placer DANS le formulaire. Le widget y ajoute lui-même
 * le champ caché « cap-token » à la validation.
 */
add_shortcode('capjs_widget', function($atts = array()) {
    $atts = shortcode_atts(array('id' => ''), $atts, 'capjs_widget');
    return m2c_capjs_render_widget((string) $atts['id']);
});

/**
 * Vérifier un jeton auprès du serveur CapJS (POST {serveur}/{clé du site}/siteverify). Faux en cas de jeton vide,
 * d'erreur réseau ou de réponse inattendue. Un jeton ne se vérifie qu'une fois.
 */
function m2c_capjs_verify_token($token) {
    if (!is_string($token) || '' === $token || !m2c_capjs_is_active()) return false;

    $endpoint = trailingslashit(get_option('m2c_capjs_server_url', '')) . get_option('m2c_capjs_site_key', '') . '/siteverify';
    $response = wp_remote_post(esc_url_raw($endpoint), array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => wp_json_encode(array(
            'secret' => get_option('m2c_capjs_secret_key', ''),
            'response' => $token,
        )),
        'timeout' => 10,
    ));

    if (is_wp_error($response)) {
        error_log('[CapJS] Erreur de connexion au serveur CapJS: ' . $response->get_error_message());
        return false;
    }
    if (200 !== wp_remote_retrieve_response_code($response)) {
        error_log('[CapJS] Erreur serveur CapJS (HTTP ' . wp_remote_retrieve_response_code($response) . ')');
        return false;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return is_array($data) && isset($data['success']) && true === $data['success'];
}

/**
 * À appeler dans le traitement d'un formulaire codé en dur, avant toute action :
 *
 *     if (!m2c_capjs_verify_request()) { // afficher « Veuillez valider le captcha. » et s'arrêter }
 *
 * Vrai si le captcha est désactivé (non configuré ou sans licence valide : le widget n'était pas affiché) ou si le jeton
 * du champ POST « cap-token » (ou $field) est valide. Le nonce du formulaire reste à la charge de l'appelant.
 */
function m2c_capjs_verify_request($field = 'cap-token') {
    if (!m2c_capjs_is_active()) return true;

    $token = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    return m2c_capjs_verify_token($token);
}
