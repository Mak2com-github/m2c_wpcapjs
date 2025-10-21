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
                               value="<?php echo esc_attr(get_option('m2c_capjs_site_key', 'https://cap.mak2com.fr')); ?>"
                               style="width:400px;" /></td>
                </tr>
                <tr>
                    <th scope="row">Clé secrète (secret key)</th>
                    <td><input type="text" name="m2c_capjs_secret_key"
                               value="<?php echo esc_attr(get_option('m2c_capjs_secret_key')); ?>"
                               style="width:400px;" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}