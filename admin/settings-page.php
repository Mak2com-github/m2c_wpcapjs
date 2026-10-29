<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_options_page(
        'Paramètres CapJS',
        'CapJS',
        'manage_options',
        'm2c-capjs',
        'm2c_capjs_settings_page'
    );
});

/**
 * Feuille de style de l'administration (charte mak2com), seulement sur l'écran Réglages > CapJS.
 */
add_action('admin_enqueue_scripts', function($hook) {
    if ('settings_page_m2c-capjs' !== $hook) return;
    wp_enqueue_style('m2c-capjs-admin', M2C_CAPJS_URL . 'assets/css/admin.css', array(), M2C_CAPJS_VERSION);
});

add_action('admin_init', function() {
    register_setting('m2c_capjs_options', 'm2c_capjs_server_url');
    register_setting('m2c_capjs_options', 'm2c_capjs_site_key');
    register_setting('m2c_capjs_options', 'm2c_capjs_secret_key');

    // Options WooCommerce
    register_setting('m2c_capjs_options', 'm2c_capjs_woocommerce_login', array('type' => 'boolean', 'default' => true));
    register_setting('m2c_capjs_options', 'm2c_capjs_woocommerce_register', array('type' => 'boolean', 'default' => true));
    register_setting('m2c_capjs_options', 'm2c_capjs_woocommerce_checkout', array('type' => 'boolean', 'default' => false));
    register_setting('m2c_capjs_options', 'm2c_capjs_woocommerce_reviews', array('type' => 'boolean', 'default' => false));
});

/**
 * En-tête de l'écran : marque, titre et sous-titre. Le repère « wp-header-end » place les messages de WordPress
 * sous l'en-tête et non à l'intérieur.
 */
function m2c_capjs_admin_header($title, $subtitle) {
    echo '<header class="m2c-capjs-hero"><div><span class="m2c-capjs-brand">mak<span>2</span>com <small>· capjs</small></span>'
        . '<p class="m2c-capjs-eyebrow">Captcha auto-hébergé</p>'
        . '<h1>' . esc_html($title) . '</h1><p>' . esc_html($subtitle) . '</p></div>'
        . '<span class="m2c-capjs-hero-mark" aria-hidden="true">M<span>2</span>C</span></header>'
        . '<hr class="wp-header-end">';
}

/**
 * Case à cocher d'une option WooCommerce : libellé, aide et état enregistré.
 */
function m2c_capjs_option_row($name, $default, $label, $help) {
    $id = 'm2c-capjs-' . str_replace('_', '-', $name);
    ?>
    <label class="m2c-capjs-option" for="<?php echo esc_attr($id); ?>">
        <input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="1" <?php checked(get_option($name, $default), true); ?> />
        <span>
            <strong><?php echo esc_html($label); ?></strong>
            <small><?php echo esc_html($help); ?></small>
        </span>
    </label>
    <?php
}

function m2c_capjs_settings_page() {
    ?>
    <div class="wrap m2c-capjs">
        <?php m2c_capjs_admin_header('Paramètres CapJS', 'Protégez vos formulaires avec votre propre serveur de captcha.'); ?>

        <?php m2c_capjs_license_section(); ?>

        <form method="post" action="options.php">
            <?php settings_fields('m2c_capjs_options'); ?>

            <section class="m2c-capjs-card" aria-labelledby="m2c-capjs-server-title">
                <p class="m2c-capjs-eyebrow">Connexion</p>
                <h2 id="m2c-capjs-server-title">Serveur CapJS</h2>
                <p class="m2c-capjs-lead">Adresse de votre instance CapJS et paire de clés créée pour ce site dans son tableau de bord. Chaque site a ses propres clés.</p>

                <div class="m2c-capjs-field">
                    <label for="m2c-capjs-server-url">URL du serveur CapJS</label>
                    <input type="url" id="m2c-capjs-server-url" name="m2c_capjs_server_url"
                           value="<?php echo esc_attr(get_option('m2c_capjs_server_url', 'https://captcha.mak2com.fr')); ?>"
                           aria-describedby="m2c-capjs-server-url-help" spellcheck="false" required />
                    <p class="m2c-capjs-help" id="m2c-capjs-server-url-help">Doit commencer par https://</p>
                </div>
                <div class="m2c-capjs-field">
                    <label for="m2c-capjs-site-key">Clé du site (site key)</label>
                    <input type="text" id="m2c-capjs-site-key" name="m2c_capjs_site_key"
                           value="<?php echo esc_attr(get_option('m2c_capjs_site_key', '')); ?>"
                           autocomplete="off" spellcheck="false" />
                </div>
                <div class="m2c-capjs-field">
                    <label for="m2c-capjs-secret-key">Clé secrète (secret key)</label>
                    <input type="text" id="m2c-capjs-secret-key" name="m2c_capjs_secret_key"
                           value="<?php echo esc_attr(get_option('m2c_capjs_secret_key')); ?>"
                           autocomplete="off" spellcheck="false" />
                </div>
            </section>

            <?php if (class_exists('WooCommerce')): ?>
            <section class="m2c-capjs-card" aria-labelledby="m2c-capjs-woo-title">
                <p class="m2c-capjs-eyebrow">Boutique</p>
                <h2 id="m2c-capjs-woo-title">Protection WooCommerce</h2>
                <p class="m2c-capjs-lead">Choisissez sur quels formulaires WooCommerce activer la protection CapJS.</p>
                <div class="m2c-capjs-callout m2c-capjs-callout--warning" role="note">
                    <p><strong>Note :</strong> avec un checkout multi-étapes, il est recommandé de désactiver la protection sur le checkout pour éviter les problèmes de compatibilité.</p>
                </div>

                <fieldset class="m2c-capjs-options">
                    <legend class="screen-reader-text">Formulaires WooCommerce protégés</legend>
                    <?php
                    m2c_capjs_option_row('m2c_capjs_woocommerce_login', true, 'Formulaire de connexion', 'Recommandé pour la sécurité du compte.');
                    m2c_capjs_option_row('m2c_capjs_woocommerce_register', true, 'Formulaire d’inscription', 'Recommandé pour prévenir le spam et les inscriptions frauduleuses.');
                    m2c_capjs_option_row('m2c_capjs_woocommerce_checkout', false, 'Formulaire de paiement (checkout)', 'Attention : peut causer des problèmes avec les checkouts multi-étapes ou certains plugins de paiement. Désactivé par défaut pour des raisons de compatibilité.');
                    m2c_capjs_option_row('m2c_capjs_woocommerce_reviews', false, 'Avis produits', 'Protection optionnelle contre les faux avis.');
                    ?>
                </fieldset>
            </section>
            <?php endif; ?>

            <div class="m2c-capjs-save">
                <?php submit_button('Enregistrer les réglages', 'primary', 'submit', false); ?>
            </div>
        </form>
    </div>
    <?php
}
