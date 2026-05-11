<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_options_page(
        'CapJS Settings',
        'CapJS',
        'manage_options',
        'm2c-capjs',
        'm2c_capjs_settings_page'
    );
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

function m2c_capjs_settings_page() {
    ?>
    <div class="wrap">
        <h1>Paramètres CapJS</h1>
        <form method="post" action="options.php">
            <?php settings_fields('m2c_capjs_options'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">URL du serveur CapJS</th>
                    <td>
                        <input type="url"
                               name="m2c_capjs_server_url"
                               value="<?php echo esc_attr(get_option('m2c_capjs_server_url', 'https://cap.mak2com.fr')); ?>"
                               style="width:400px;" required />
                        <p class="description">Doit commencer par https://</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Clé du site (site key)</th>
                    <td><input type="text" name="m2c_capjs_site_key"
                               value="<?php echo esc_attr(get_option('m2c_capjs_site_key', '')); ?>"
                               style="width:400px;" /></td>
                </tr>
                <tr>
                    <th scope="row">Clé secrète (secret key)</th>
                    <td><input type="text" name="m2c_capjs_secret_key"
                               value="<?php echo esc_attr(get_option('m2c_capjs_secret_key')); ?>"
                               style="width:400px;" /></td>
                </tr>
            </table>

            <?php if (class_exists('WooCommerce')): ?>
            <h2>Protection WooCommerce</h2>
            <p class="description">
                Choisissez sur quels formulaires WooCommerce activer la protection CapJS.<br>
                <strong>Note :</strong> Si vous utilisez un checkout multi-étapes, il est recommandé de désactiver la protection sur le checkout pour éviter les problèmes de compatibilité.
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row">Formulaire de connexion</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="m2c_capjs_woocommerce_login"
                                   value="1"
                                   <?php checked(get_option('m2c_capjs_woocommerce_login', true), true); ?> />
                            Activer CapJS sur le formulaire de connexion
                        </label>
                        <p class="description">Recommandé pour la sécurité du compte.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Formulaire d'inscription</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="m2c_capjs_woocommerce_register"
                                   value="1"
                                   <?php checked(get_option('m2c_capjs_woocommerce_register', true), true); ?> />
                            Activer CapJS sur le formulaire d'inscription
                        </label>
                        <p class="description">Recommandé pour prévenir le spam et les inscriptions frauduleuses.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Formulaire de paiement (checkout)</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="m2c_capjs_woocommerce_checkout"
                                   value="1"
                                   <?php checked(get_option('m2c_capjs_woocommerce_checkout', false), true); ?> />
                            Activer CapJS sur le formulaire de paiement
                        </label>
                        <p class="description">
                            <strong>⚠️ Attention :</strong> Peut causer des problèmes avec les checkouts multi-étapes ou certains plugins de paiement.
                            Désactivé par défaut pour des raisons de compatibilité.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Avis produits</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="m2c_capjs_woocommerce_reviews"
                                   value="1"
                                   <?php checked(get_option('m2c_capjs_woocommerce_reviews', false), true); ?> />
                            Activer CapJS sur les avis produits
                        </label>
                        <p class="description">Protection optionnelle contre les faux avis.</p>
                    </td>
                </tr>
            </table>
            <?php endif; ?>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}