/**
 * CapJS Integration pour Contact Form 7
 */

(function($) {
    'use strict';

    // Initialiser CapJS quand le DOM est prêt
    $(document).ready(function() {
        initCapJSWidgets();
    });

    // Réinitialiser après une soumission AJAX
    $(document).on('wpcf7mailsent', function() {
        initCapJSWidgets();
    });

    /**
     * Initialise tous les widgets CapJS dans la page
     */
    function initCapJSWidgets() {
        $('.capjs-widget').each(function() {
            var $widget = $(this);
            var $container = $widget.closest('.capjs-widget-container');
            var $form = $widget.closest('form');

            // Skip si déjà initialisé
            if ($widget.data('capjs-initialized')) {
                return;
            }

            $widget.data('capjs-initialized', true);

            // Utiliser le vrai widget CapJS
            initRealCapJSWidget($widget, $form);
        });
    }

    /**
     * Fonction d'initialisation du widget CapJS
     */
    function initRealCapJSWidget($widget, $form) {
        var siteKey = $widget.data('sitekey');
        var theme = $widget.data('theme') || 'light';
        var serverUrl = capjsCF7Config.serverUrl || '';

        if (!serverUrl || !siteKey) {
            console.error('[CapJS CF7] Configuration manquante');
            return;
        }

        // Construire l'API endpoint
        var apiEndpoint = serverUrl.replace(/\/$/, '') + '/' + siteKey + '/';

        // Créer l'élément cap-widget
        var widgetId = 'cap-widget-cf7-' + Math.random().toString(36).substr(2, 9);
        var $capWidget = $('<cap-widget></cap-widget>')
            .attr('id', widgetId)
            .attr('data-cap-api-endpoint', apiEndpoint)
            .attr('data-cap-label', 'Je suis un humain')
            .css({
                'display': 'block',
                'margin': '10px 0'
            });

        // Ajouter le widget au conteneur
        $widget.html($capWidget);

        // Mode debug (mettre à true pour activer les logs)
        var DEBUG = false;

        if (DEBUG) {
            console.log('[CapJS CF7] Widget initialisé avec endpoint:', apiEndpoint);
        }

        // Vérifier le token régulièrement
        var widgetElement = $capWidget[0];
        var checkTokenInterval = setInterval(function() {
            if (widgetElement && widgetElement.token) {
                $widget.data('capjs-token', widgetElement.token);
            } else {
                $widget.data('capjs-token', '');
            }
        }, 500);

        // Nettoyer l'interval si le widget est supprimé
        $widget.on('remove', function() {
            clearInterval(checkTokenInterval);
        });

        // Mettre à jour le token avant la soumission (événement CF7)
        document.addEventListener('wpcf7beforesubmit', function(event) {
            var token = widgetElement && widgetElement.token ? widgetElement.token : '';

            if (DEBUG) {
                console.log('[CapJS CF7] Avant soumission, token:', token);
            }

            // Ajouter le token dans le champ caché
            var $tokenField = $form.find('input[name="_wpcf7_capjs_token"]');
            if ($tokenField.length) {
                $tokenField.val(token);
                if (DEBUG) {
                    console.log('[CapJS CF7] Token ajouté au formulaire:', token);
                }
            }
        }, false);
    }

})(jQuery);
