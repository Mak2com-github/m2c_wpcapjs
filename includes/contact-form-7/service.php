<?php
/**
 * Classe de service CapJS pour Contact Form 7
 *
 * Cette classe étend WPCF7_Service et apparaît dans l'onglet "Intégration" de CF7
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('WPCF7_Service')) {
    return;
}

class M2C_WPCF7_CAPJS extends WPCF7_Service {

    private static $instance;

    public static function get_instance() {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function get_title() {
        return 'CapJS';
    }

    public function is_active() {
        $server_url = get_option('m2c_capjs_server_url');
        $site_key = get_option('m2c_capjs_site_key');
        return !empty($server_url) && !empty($site_key);
    }

    public function get_categories() {
        return array('spam_protection');
    }

    public function icon() {
    }

    public function link() {
        echo wp_kses_data(wpcf7_link(
            'https://capjs.js.org',
            'capjs.js.org'
        ));
    }

    public function get_server_url() {
        return get_option('m2c_capjs_server_url', '');
    }

    public function get_site_key() {
        return get_option('m2c_capjs_site_key', '');
    }

    public function get_secret_key() {
        return get_option('m2c_capjs_secret_key', '');
    }

    public function verify($token) {
        $is_human = false;

        if (empty($token) || !$this->is_active()) {
            return $is_human;
        }

        $server_url = $this->get_server_url();
        $site_key = $this->get_site_key();
        $secret_key = $this->get_secret_key();

        // Selon la doc officielle CapJS : /<site_key>/siteverify
        $endpoint = trailingslashit($server_url) . $site_key . '/siteverify';

        // Format selon la doc : {"secret": "<key_secret>", "response": "<captcha_token>"}
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
            // Logger uniquement les erreurs
            error_log('[CapJS] Erreur de connexion au serveur CapJS: ' . $response->get_error_message());
            return $is_human;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if (200 !== $response_code) {
            // Logger uniquement les erreurs
            error_log('[CapJS] Erreur serveur CapJS (HTTP ' . $response_code . ')');
            return $is_human;
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        $is_human = isset($response_data['success']) && $response_data['success'] === true;

        if ($submission = WPCF7_Submission::get_instance()) {
            $submission->push('capjs', array(
                'response' => $response_data,
            ));
        }

        return $is_human;
    }

    protected function menu_page_url($args = '') {
        $args = wp_parse_args($args, array());
        $url = menu_page_url('wpcf7-integration', false);
        $url = add_query_arg(array('service' => 'capjs'), $url);

        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    public function load($action = '') {
        // Si on essaie d'accéder à la page de setup, on ne fait rien
        // La configuration se fait via les réglages du plugin
        // Cette méthode est requise par WPCF7_Service mais n'a pas besoin de traitement spécial
    }

    public function display($action = '') {
        ?>
        <p>
            <?php echo esc_html(__('CapJS est un captcha open-source sans dépendance à Google. Il protège vos formulaires contre le spam et les soumissions automatisées.', 'capjs-integration')); ?>
        </p>

        <p>
            <strong>
                <?php echo wp_kses_data(wpcf7_link(
                    'https://capjs.js.org',
                    'CapJS Documentation'
                )); ?>
            </strong>
        </p>

        <?php if ($this->is_active()): ?>
            <p class="dashicons-before dashicons-yes">
                <?php echo esc_html(__('CapJS est actif sur ce site.', 'capjs-integration')); ?>
            </p>
        <?php endif; ?>

        <p>
            <?php echo esc_html(__('Pour configurer CapJS, utilisez la page de réglages du plugin CapJS Integration.', 'capjs-integration')); ?>
        </p>

        <p>
            <a href="<?php echo esc_url(admin_url('options-general.php?page=m2c-capjs')); ?>" class="button">
                <?php echo esc_html(__('Configurer CapJS', 'capjs-integration')); ?>
            </a>
        </p>

        <?php if ($this->is_active()): ?>
            <h3><?php echo esc_html(__('Informations de configuration', 'capjs-integration')); ?></h3>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php echo esc_html(__('URL du serveur', 'capjs-integration')); ?></th>
                        <td><code><?php echo esc_html($this->get_server_url()); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html(__('Site Key', 'capjs-integration')); ?></th>
                        <td><code><?php echo esc_html($this->get_site_key()); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html(__('Secret Key', 'capjs-integration')); ?></th>
                        <td><code><?php echo esc_html(str_repeat('*', 20)); ?></code></td>
                    </tr>
                </tbody>
            </table>

            <h3><?php echo esc_html(__('Utilisation', 'capjs-integration')); ?></h3>
            <p>
                <?php echo esc_html(__('Pour ajouter CapJS à vos formulaires Contact Form 7, ajoutez le tag suivant dans votre formulaire :', 'capjs-integration')); ?>
            </p>
            <p>
                <code>[capjs]</code>
            </p>
            <p>
                <?php echo esc_html(__('Le captcha sera automatiquement affiché et validé lors de la soumission du formulaire.', 'capjs-integration')); ?>
            </p>
        <?php endif; ?>
        <?php
    }
}
