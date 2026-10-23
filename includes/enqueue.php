<?php
if (!defined('ABSPATH')) exit;

/**
 * Contraste de la case CapJS (RGAA 3.3), via sa variable CSS publique.
 * Le style est placé dans l'en-tête : les widgets peuvent être créés plus tard
 * par les formulaires FSE ou AJAX. Aucune requête CSS supplémentaire.
 */
add_action('wp_enqueue_scripts', function() {
    if (empty(get_option('m2c_capjs_server_url', '')) || empty(get_option('m2c_capjs_site_key'))) return;
    if (!class_exists('Ninja_Forms') && !class_exists('WPCF7') && !class_exists('WooCommerce')) return;

    wp_register_style('m2c-capjs-accessibility', false, array(), '1.0.0');
    wp_enqueue_style('m2c-capjs-accessibility');
    wp_add_inline_style(
        'm2c-capjs-accessibility',
        'cap-widget { --cap-checkbox-border: 1px solid #767676; }'
    );
});

/**
 * Charger le widget uniquement lorsqu'une intégration rend un champ protégé.
 * Les scripts restent en pied de page, y compris avec les modèles FSE.
 */
function m2c_capjs_enqueue_widget() {
    if (wp_script_is('capjs-widget', 'enqueued')) return;
    $server_url = esc_url_raw(get_option('m2c_capjs_server_url', ''));
    $site_key = get_option('m2c_capjs_site_key');
    $widget_strings = m2c_capjs_get_widget_strings();

    // Ne rien charger si le plugin n'est pas configuré
    if (empty($server_url) || empty($site_key)) return;

    // Charger le widget CapJS
    wp_enqueue_script(
        'capjs-widget',
        trailingslashit($server_url) . 'assets/widget.js',
        [],
        null,
        true
    );

    // Configuration globale
    wp_localize_script('capjs-widget', 'CapJS_Config', array(
        'siteKey' => $site_key,
        'serverUrl' => $server_url,
    ));

    // Script custom pour traduire le widget
    wp_enqueue_script(
        'capjs-custom',
        plugin_dir_url(dirname(__FILE__)) . 'assets/js/capjs-custom.js',
        array('capjs-widget'),
        '1.2.2',
        true
    );

    wp_localize_script('capjs-custom', 'capjsCustomConfig', $widget_strings);
}

/**
 * Enqueue le template client pour Ninja Forms
 */
add_action('nf_display_enqueue_scripts', function() {
    $site_key = get_option('m2c_capjs_site_key');
    $widget_strings = m2c_capjs_get_widget_strings();
    ?>
    <!-- Template CapJS pour Ninja Forms -->
    <script id="tmpl-nf-field-capjs" type="text/template">
        <div class="nf-field-element">
            <# if (data.label) { #>
                <div class="nf-field-label">
                    <label for="nf-field-{{{ data.id }}}" class="{{{ data.renderLabelClasses }}}">
                        {{{ data.label }}}
                        <# if (data.required) { #>
                            <span class="ninja-forms-req-symbol">*</span>
                        <# } #>
                    </label>
                </div>
            <# } #>

            <div class="nf-field capjs-field">
                <div class="capjs-widget-container"
                     id="capjs-widget-{{{ data.id }}}"
                     data-field-id="{{{ data.id }}}"
                     data-sitekey="<?php echo esc_attr($site_key); ?>"
                     data-theme="{{{ data.theme || 'light' }}}">
                    <!-- Widget CapJS réel -->
                    <cap-widget
                        id="cap-widget-{{{ data.id }}}"
                        data-cap-api-endpoint="<?php echo esc_url(trailingslashit(get_option('m2c_capjs_server_url', '')) . $site_key . '/'); ?>"
                        data-cap-lang="<?php echo esc_attr($widget_strings['language']); ?>"
                        data-cap-i18n-initial-state="<?php echo esc_attr($widget_strings['humanLabel']); ?>"
                        data-cap-i18n-solved-label="<?php echo esc_attr($widget_strings['verificationComplete']); ?>"
                        data-cap-i18n-error-label="<?php echo esc_attr($widget_strings['verificationFailed']); ?>"
                        style="display:block;margin:10px 0;">
                    </cap-widget>
                </div>
            </div>

            <# if (data.errors && data.errors.length > 0) { #>
                <div class="nf-error-wrap nf-error" style="margin-top:10px;">
                    <div class="nf-error-msg nf-error-field-errors">
                        <# data.errors.each(function(error) { #>
                            <div class="nf-error-field-error" style="color:#d9534f;font-weight:bold;padding:8px;background:#f9d6d5;border-left:3px solid #d9534f;">
                                {{{ error.get('msg') }}}
                            </div>
                        <# }); #>
                    </div>
                </div>
            <# } #>

            <# if (data.description) { #>
                <div class="nf-field-description">{{{ data.description }}}</div>
            <# } #>
        </div>
    </script>

    <!-- Script inline pour gérer le champ CapJS -->
    <script>
    jQuery(document).ready(function($) {
        // Vérifier que Backbone Radio est disponible
        if (typeof Backbone === 'undefined' || typeof Backbone.Radio === 'undefined') {
            return;
        }

        var nfRadio = Backbone.Radio;

        // Attendre que le formulaire soit chargé
        $(document).on('nfFormReady', function(e, layoutView) {
            // Trouver tous les champs CapJS dans le formulaire
            var capjsFields = layoutView.model.get('fields').where({type: 'capjs'});

            if (capjsFields.length === 0) {
                return;
            }

            // Pour chaque champ CapJS
            _.each(capjsFields, function(fieldModel) {
                var fieldID = fieldModel.get('id');

                // Écouter les changements du modèle directement
                fieldModel.on('change:value', function(model, value) {
                    if (value && value !== '') {
                        // Effacer toutes les erreurs pour ce champ quand le captcha est validé
                        var errors = fieldModel.get('errors');
                        var errorsToRemove = errors.models.slice();

                        _.each(errorsToRemove, function(error) {
                            var errorId = error.get('id');
                            nfRadio.channel('fields').request('remove:error', fieldID, errorId);
                        });
                    }
                });

                // Attendre que le DOM soit rendu et que le widget CapJS soit disponible
                setTimeout(function() {
                    var container = $('[data-field-id="' + fieldID + '"]');
                    if (container.length === 0) return;

                    var widgetElement = container.find('cap-widget')[0];
                    if (!widgetElement) return;

                    // Surveiller la propriété token du widget
                    var checkToken = function() {
                        if (widgetElement.token) {
                            fieldModel.set('value', widgetElement.token);
                        } else {
                            fieldModel.set('value', '');
                        }
                    };

                    // Vérifier le token toutes les 500ms
                    setInterval(checkToken, 500);
                }, 500);
            });

            // Écouter la validation des champs CapJS avant soumission
            _.each(capjsFields, function(fieldModel) {
                var fieldID = fieldModel.get('id');

                // Écouter l'événement de validation pour ce champ spécifique
                nfRadio.channel('submit').on('validate:field', function(validateFieldModel) {
                    // Vérifier si c'est notre champ CapJS
                    if (validateFieldModel.get('id') !== fieldID || validateFieldModel.get('type') !== 'capjs') {
                        return;
                    }

                    var value = validateFieldModel.get('value');

                    if (!value || value === '') {
                        var errorMsg = 'Veuillez valider le captcha avant de soumettre le formulaire.';
                        nfRadio.channel('fields').request('add:error', fieldID, 'capjs-validation', errorMsg);
                    } else {
                        // Supprimer toutes les erreurs du champ si validé
                        var errors = validateFieldModel.get('errors');
                        var errorsToRemove = errors.models.slice();
                        _.each(errorsToRemove, function(error) {
                            nfRadio.channel('fields').request('remove:error', fieldID, error.get('id'));
                        });
                    }
                });
            });

        });
    });
    </script>
    <?php
}, 10);
