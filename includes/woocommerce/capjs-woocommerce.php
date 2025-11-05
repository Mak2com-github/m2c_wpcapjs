<?php
/**
 * Intégration CapJS avec WooCommerce
 *
 * Ce fichier ajoute le support du captcha CapJS aux formulaires WooCommerce :
 * - Formulaire d'inscription
 * - Formulaire de connexion
 * - Formulaire de paiement (checkout)
 * - Formulaire d'avis produit
 */

if (!defined('ABSPATH')) exit;

// Vérifier que WooCommerce est bien chargé
if (!class_exists('WooCommerce')) {
    return;
}

/**
 * Classe principale pour l'intégration CapJS avec WooCommerce
 */
class M2C_WooCommerce_CapJS {

    private static $instance;

    public static function get_instance() {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialiser les hooks WordPress/WooCommerce
     */
    private function init_hooks() {
        // Ajouter le widget CapJS aux formulaires
        add_action('woocommerce_register_form', array($this, 'display_capjs_field'), 10);
        add_action('woocommerce_login_form', array($this, 'display_capjs_field'), 10);
        add_action('woocommerce_review_before_comment_form', array($this, 'display_capjs_field'), 10);
        add_action('woocommerce_after_checkout_billing_form', array($this, 'display_capjs_field'), 10);

        // Validation côté serveur
        add_filter('woocommerce_registration_errors', array($this, 'validate_capjs_registration'), 10, 3);
        add_filter('woocommerce_process_login_errors', array($this, 'validate_capjs_login'), 10, 3);
        add_filter('preprocess_comment', array($this, 'validate_capjs_review'), 10, 1);
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_capjs_checkout'), 10, 2);

        // Charger les scripts JavaScript
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 20);
    }

    /**
     * Vérifier si le service CapJS est actif
     */
    public function is_active() {
        $server_url = get_option('m2c_capjs_server_url');
        $site_key = get_option('m2c_capjs_site_key');
        return !empty($server_url) && !empty($site_key);
    }

    /**
     * Obtenir l'URL du serveur CapJS
     */
    public function get_server_url() {
        return get_option('m2c_capjs_server_url', '');
    }

    /**
     * Obtenir la clé publique (site key)
     */
    public function get_site_key() {
        return get_option('m2c_capjs_site_key', '');
    }

    /**
     * Obtenir la clé secrète
     */
    public function get_secret_key() {
        return get_option('m2c_capjs_secret_key', '');
    }

    /**
     * Afficher le widget CapJS dans les formulaires
     */
    public function display_capjs_field() {
        if (!$this->is_active()) {
            return;
        }

        $site_key = $this->get_site_key();
        $server_url = $this->get_server_url();

        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <div class="capjs-widget-container capjs-woocommerce">
                <cap-widget
                    id="cap-widget-wc-<?php echo esc_attr(uniqid()); ?>"
                    data-cap-api-endpoint="<?php echo esc_url(trailingslashit($server_url) . $site_key . '/'); ?>"
                    data-cap-label="Je suis un humain"
                    style="display:block;margin:10px 0;">
                </cap-widget>
                <input type="hidden" name="capjs_wc_token" class="capjs-wc-token" value="" />
            </div>
        </p>
        <?php
    }

    /**
     * Valider le token CapJS auprès du serveur
     */
    private function verify_token($token) {
        if (empty($token) || !$this->is_active()) {
            return false;
        }

        $server_url = $this->get_server_url();
        $site_key = $this->get_site_key();
        $secret_key = $this->get_secret_key();

        // Endpoint de vérification : /<site_key>/siteverify
        $endpoint = trailingslashit($server_url) . $site_key . '/siteverify';

        // Préparer la requête
        $request = array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array(
                'secret' => $secret_key,
                'response' => $token
            )),
            'timeout' => 10,
        );

        $response = wp_remote_post(esc_url_raw($endpoint), $request);

        if (is_wp_error($response)) {
            error_log('[CapJS WooCommerce] Erreur de connexion: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if (200 !== $response_code) {
            error_log('[CapJS WooCommerce] Erreur serveur (HTTP ' . $response_code . ')');
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        return isset($response_data['success']) && $response_data['success'] === true;
    }

    /**
     * Valider le captcha lors de l'inscription
     */
    public function validate_capjs_registration($errors, $username, $email) {
        if (!$this->is_active()) {
            return $errors;
        }

        $token = isset($_POST['capjs_wc_token']) ? sanitize_text_field($_POST['capjs_wc_token']) : '';

        if (empty($token)) {
            $errors->add('capjs_error', __('Veuillez valider le captcha avant de vous inscrire.', 'capjs-integration'));
            return $errors;
        }

        if (!$this->verify_token($token)) {
            $errors->add('capjs_error', __('La validation du captcha a échoué. Veuillez réessayer.', 'capjs-integration'));
        }

        return $errors;
    }

    /**
     * Valider le captcha lors de la connexion
     */
    public function validate_capjs_login($validation_error, $username, $password) {
        if (!$this->is_active()) {
            return $validation_error;
        }

        $token = isset($_POST['capjs_wc_token']) ? sanitize_text_field($_POST['capjs_wc_token']) : '';

        if (empty($token)) {
            return new WP_Error('capjs_error', __('Veuillez valider le captcha avant de vous connecter.', 'capjs-integration'));
        }

        if (!$this->verify_token($token)) {
            return new WP_Error('capjs_error', __('La validation du captcha a échoué. Veuillez réessayer.', 'capjs-integration'));
        }

        return $validation_error;
    }

    /**
     * Valider le captcha lors de la soumission d'un avis produit
     */
    public function validate_capjs_review($commentdata) {
        // Ne valider que pour les avis produits WooCommerce
        if (!isset($_POST['comment_post_ID']) || get_post_type($_POST['comment_post_ID']) !== 'product') {
            return $commentdata;
        }

        if (!$this->is_active()) {
            return $commentdata;
        }

        $token = isset($_POST['capjs_wc_token']) ? sanitize_text_field($_POST['capjs_wc_token']) : '';

        if (empty($token)) {
            wp_die(__('Veuillez valider le captcha avant de soumettre votre avis.', 'capjs-integration'));
        }

        if (!$this->verify_token($token)) {
            wp_die(__('La validation du captcha a échoué. Veuillez réessayer.', 'capjs-integration'));
        }

        return $commentdata;
    }

    /**
     * Valider le captcha lors du paiement (checkout)
     */
    public function validate_capjs_checkout($data, $errors) {
        if (!$this->is_active()) {
            return;
        }

        $token = isset($_POST['capjs_wc_token']) ? sanitize_text_field($_POST['capjs_wc_token']) : '';

        if (empty($token)) {
            $errors->add('capjs_error', __('Veuillez valider le captcha avant de finaliser votre commande.', 'capjs-integration'));
            return;
        }

        if (!$this->verify_token($token)) {
            $errors->add('capjs_error', __('La validation du captcha a échoué. Veuillez réessayer.', 'capjs-integration'));
        }
    }

    /**
     * Charger les scripts JavaScript pour WooCommerce
     */
    public function enqueue_scripts() {
        if (!$this->is_active()) {
            return;
        }

        // Charger uniquement sur les pages WooCommerce pertinentes
        if (!is_account_page() && !is_checkout() && !is_product()) {
            return;
        }

        wp_enqueue_script(
            'capjs-woocommerce',
            M2C_CAPJS_URL . 'assets/js/capjs-woocommerce.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('capjs-woocommerce', 'capjsWCConfig', array(
            'serverUrl' => $this->get_server_url(),
            'siteKey' => $this->get_site_key(),
        ));
    }
}

// Initialiser l'intégration
M2C_WooCommerce_CapJS::get_instance();
