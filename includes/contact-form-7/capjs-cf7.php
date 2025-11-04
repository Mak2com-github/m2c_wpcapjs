<?php
/**
 * Intégration CapJS avec Contact Form 7
 *
 * Ce fichier enregistre CapJS comme service d'intégration dans Contact Form 7
 * et ajoute le support du tag [capjs] dans les formulaires
 */

if (!defined('ABSPATH')) exit;

// Vérifier que Contact Form 7 est bien chargé
if (!class_exists('WPCF7_Service')) {
    return;
}

// Charger la classe de service
require_once __DIR__ . '/service.php';

/**
 * Enregistrer le service CapJS dans Contact Form 7
 */
add_action('wpcf7_init', 'm2c_capjs_register_cf7_service', 10, 0);

function m2c_capjs_register_cf7_service() {
    $integration = WPCF7_Integration::get_instance();
    $integration->add_service('capjs', M2C_WPCF7_CAPJS::get_instance());
}

/**
 * Enregistrer le tag de formulaire [capjs]
 */
add_action('wpcf7_init', 'm2c_capjs_add_form_tag', 10, 0);

function m2c_capjs_add_form_tag() {
    $service = M2C_WPCF7_CAPJS::get_instance();

    if (!$service->is_active()) {
        return;
    }

    wpcf7_add_form_tag('capjs', 'm2c_capjs_form_tag_handler', array(
        'display-block' => true
    ));
}

/**
 * Génère le HTML du widget CapJS dans le formulaire
 */
function m2c_capjs_form_tag_handler($tag) {
    $service = M2C_WPCF7_CAPJS::get_instance();

    if (!$service->is_active()) {
        return '';
    }

    $site_key = $service->get_site_key();
    $theme = isset($tag->options) && in_array('dark', $tag->options) ? 'dark' : 'light';

    ob_start();
    ?>
    <div class="capjs-widget-container">
        <div class="capjs-widget"
             data-sitekey="<?php echo esc_attr($site_key); ?>"
             data-theme="<?php echo esc_attr($theme); ?>">
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Ajouter le champ caché pour le token CapJS
 */
add_filter('wpcf7_form_hidden_fields', 'm2c_capjs_add_hidden_fields', 100, 1);

function m2c_capjs_add_hidden_fields($fields) {
    $service = M2C_WPCF7_CAPJS::get_instance();

    if (!$service->is_active()) {
        return $fields;
    }

    return array_merge($fields, array(
        '_wpcf7_capjs_token' => '',
    ));
}

/**
 * Valider le token CapJS lors de la soumission
 */
add_filter('wpcf7_spam', 'm2c_capjs_verify_response', 9, 2);

function m2c_capjs_verify_response($spam, $submission) {
    if ($spam) {
        return $spam;
    }

    $service = M2C_WPCF7_CAPJS::get_instance();

    if (!$service->is_active()) {
        error_log('[CapJS CF7] Service non actif');
        return $spam;
    }

    // Vérifier si le formulaire contient le tag [capjs]
    $contact_form = $submission->get_contact_form();
    $form_content = $contact_form->prop('form');

    if (strpos($form_content, '[capjs') === false) {
        // Pas de tag CapJS dans ce formulaire, on ne valide pas
        error_log('[CapJS CF7] Pas de tag [capjs] dans le formulaire, validation ignorée');
        return $spam;
    }

    // Le widget CapJS envoie le token dans le champ 'cap-token'
    $token = isset($_POST['cap-token']) ? sanitize_text_field($_POST['cap-token']) : '';

    // Fallback sur notre champ caché si pas de cap-token
    if (empty($token)) {
        $token = isset($_POST['_wpcf7_capjs_token']) ? sanitize_text_field($_POST['_wpcf7_capjs_token']) : '';
    }

    if (empty($token)) {
        $spam = true;
        $submission->add_spam_log(array(
            'agent' => 'capjs',
            'reason' => __('Le token CapJS est vide.', 'capjs-integration'),
        ));
    } else {
        $verify_result = $service->verify($token);

        if ($verify_result) {
            // Humain vérifié
            $spam = false;
        } else {
            // Bot détecté
            $spam = true;
            $submission->add_spam_log(array(
                'agent' => 'capjs',
                'reason' => __('La validation CapJS a échoué.', 'capjs-integration'),
            ));
        }
    }

    return $spam;
}

/**
 * Charger les scripts JavaScript pour Contact Form 7
 */
add_action('wp_enqueue_scripts', 'm2c_capjs_cf7_enqueue_scripts', 20, 0);

function m2c_capjs_cf7_enqueue_scripts() {
    $service = M2C_WPCF7_CAPJS::get_instance();

    if (!$service->is_active()) {
        return;
    }

    // Enregistrer le script CapJS
    wp_enqueue_script(
        'capjs-cf7',
        M2C_CAPJS_URL . 'assets/js/capjs-cf7.js',
        array('jquery'),
        '1.0.0',
        true
    );

    // Passer la configuration au JavaScript
    wp_localize_script('capjs-cf7', 'capjsCF7Config', array(
        'serverUrl' => $service->get_server_url(),
        'siteKey' => $service->get_site_key(),
    ));
}

/**
 * Ajouter le générateur de tag dans l'éditeur CF7
 */
add_action('wpcf7_admin_init', 'm2c_capjs_add_tag_generator', 50, 0);

function m2c_capjs_add_tag_generator() {
    $service = M2C_WPCF7_CAPJS::get_instance();

    if (!$service->is_active()) {
        return;
    }

    $tag_generator = WPCF7_TagGenerator::get_instance();
    $tag_generator->add(
        'capjs',
        __('CapJS', 'capjs-integration'),
        'm2c_capjs_tag_generator_capjs',
        array('version' => '2')
    );
}

/**
 * Afficher le panneau du générateur de tag
 */
function m2c_capjs_tag_generator_capjs($contact_form, $args = '') {
    $args = wp_parse_args($args, array());
    ?>
    <div class="control-box">
        <fieldset>
            <legend><?php echo esc_html(__('Générer un tag CapJS', 'capjs-integration')); ?></legend>

            <p>
                <?php echo esc_html(__('Pour ajouter le captcha CapJS à votre formulaire, insérez le tag suivant :', 'capjs-integration')); ?>
            </p>

            <p>
                <code>[capjs]</code>
            </p>

            <p>
                <?php echo esc_html(__('Options disponibles :', 'capjs-integration')); ?>
            </p>

            <ul>
                <li><code>[capjs]</code> - <?php echo esc_html(__('Thème clair (par défaut)', 'capjs-integration')); ?></li>
                <li><code>[capjs dark]</code> - <?php echo esc_html(__('Thème sombre', 'capjs-integration')); ?></li>
            </ul>
        </fieldset>
    </div>

    <div class="insert-box">
        <input type="text" name="capjs" class="tag code" readonly="readonly" onfocus="this.select()" value="[capjs]" />
        <div class="submitbox">
            <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insérer le tag', 'capjs-integration')); ?>" />
        </div>
    </div>
    <?php
}
