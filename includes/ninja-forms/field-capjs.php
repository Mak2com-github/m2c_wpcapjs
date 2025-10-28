<?php
/**
 * Champ personnalisé CapJS pour Ninja Forms
 */

if (!defined('ABSPATH')) exit;

/**
 * Classe du champ CapJS
 */
class NF_Fields_CapJS extends NF_Abstracts_Field {

    protected $_name = 'capjs';
    protected $_type = 'capjs';
    protected $_section = 'misc';
    protected $_icon = 'shield';
    protected $_templates = 'capjs';
    protected $_test_value = '';
    protected $_settings = array('label', 'classes');

    public function __construct() {
        parent::__construct();

        $this->_nicename = esc_html__('CapJS Captcha', 'ninja-forms');

        // Paramètre de visibilité du label
        $this->_settings['label_visibility'] = array(
            'name'    => 'label_visibility',
            'type'    => 'select',
            'label'   => esc_html__('Label Visibility', 'ninja-forms'),
            'options' => array(
                array(
                    'label' => esc_html__('Visible', 'ninja-forms'),
                    'value' => 'visible',
                ),
                array(
                    'label' => esc_html__('Hidden', 'ninja-forms'),
                    'value' => 'invisible',
                ),
            ),
            'width'   => 'one-half',
            'group'   => 'primary',
            'value'   => 'invisible',
            'help'    => esc_html__('Choose whether to show or hide the field label for the CapJS widget.', 'ninja-forms'),
        );

        // Paramètre de thème
        $this->_settings['theme'] = array(
            'name'    => 'theme',
            'type'    => 'select',
            'label'   => esc_html__('Theme', 'ninja-forms'),
            'options' => array(
                array(
                    'label' => esc_html__('Light', 'ninja-forms'),
                    'value' => 'light',
                ),
                array(
                    'label' => esc_html__('Dark', 'ninja-forms'),
                    'value' => 'dark',
                ),
            ),
            'width'   => 'one-half',
            'group'   => 'display',
            'value'   => 'light',
            'help'    => esc_html__('Select the theme for the CapJS widget.', 'ninja-forms'),
        );

        add_filter('nf_sub_hidden_field_types', array($this, 'hide_field_type'));
    }

    /**
     * Localiser les paramètres pour le front-end
     */
    public function localize_settings($settings, $form) {
        $settings['site_key'] = get_option('m2c_capjs_site_key', '');
        $settings['server_url'] = get_option('m2c_capjs_server_url', 'https://cap.mak2com.fr');

        // Si label invisible, masquer le label
        if ('invisible' === $settings['label_visibility']) {
            $settings['label'] = '';
        }

        return $settings;
    }

    /**
     * Validation du captcha
     */
    public function validate($field, $data) {
        // Vérifier la présence de la valeur (token)
        if (empty($field['value'])) {
            return esc_html__('Veuillez vérifier que vous êtes humain, puis soumettez à nouveau.', 'ninja-forms');
        }

        $token = sanitize_text_field($field['value']);
        $server_url = esc_url_raw(get_option('m2c_capjs_server_url', 'https://cap.mak2com.fr'));
        $site_key = get_option('m2c_capjs_site_key');
        $secret_key = get_option('m2c_capjs_secret_key');

        // Valider le token auprès du serveur CapJS
        // Endpoint : https://<instance_url>/<site_key>/siteverify
        $response = wp_remote_post(
            trailingslashit($server_url) . $site_key . '/siteverify',
            array(
                'headers' => array('Content-Type' => 'application/json'),
                'body'    => wp_json_encode(array(
                    'secret' => $secret_key,
                    'response' => $token
                )),
                'timeout' => 10,
            )
        );

        if (is_wp_error($response)) {
            return esc_html__('Erreur de vérification. Veuillez réessayer.', 'ninja-forms');
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (empty($result['success'])) {
            return esc_html__('Échec de la validation du captcha. Veuillez réessayer.', 'ninja-forms');
        }

        return false; // No error
    }

    /**
     * Masquer ce champ des soumissions
     */
    public function hide_field_type($field_types) {
        $field_types[] = $this->_name;
        return $field_types;
    }
}

/**
 * Enregistrer le champ personnalisé
 * Priorité 99 pour s'assurer qu'il est chargé après tous les champs par défaut
 */
add_filter('ninja_forms_register_fields', function($fields) {
    if (!isset($fields['capjs'])) {
        $fields['capjs'] = new NF_Fields_CapJS();
    }
    return $fields;
}, 99);
