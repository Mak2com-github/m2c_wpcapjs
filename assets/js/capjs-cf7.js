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
     * Widget de développement (checkbox simple)
     * À remplacer par le vrai widget CapJS
     */
    function createDevelopmentWidget($widget, $form) {
        var siteKey = $widget.data('sitekey');
        var theme = $widget.data('theme') || 'light';

        // Créer une checkbox simple pour le dev
        var $checkbox = $('<label style="display: flex; align-items: center; padding: 15px; border: 1px solid #ccc; border-radius: 4px; background: ' + (theme === 'dark' ? '#333' : '#f9f9f9') + '; color: ' + (theme === 'dark' ? '#fff' : '#000') + ';">' +
            '<input type="checkbox" style="margin-right: 10px;" /> ' +
            'Je ne suis pas un robot (CapJS - Dev Mode)' +
            '</label>');

        $widget.html($checkbox);

        var $checkboxInput = $checkbox.find('input[type="checkbox"]');

        // Quand la checkbox est cochée, générer un token
        $checkboxInput.on('change', function() {
            if ($(this).is(':checked')) {
                var token = 'capjs_dev_token_' + Math.random().toString(36).substring(2);
                $widget.data('capjs-token', token);
                console.log('[CapJS CF7] Token généré:', token);
            } else {
                $widget.data('capjs-token', '');
                console.log('[CapJS CF7] Token effacé');
            }
        });

        // Intercepter la soumission du formulaire
        $form.on('submit', function(e) {
            var token = $widget.data('capjs-token') || '';

            if (!token) {
                e.preventDefault();
                alert('Veuillez cocher la case "Je ne suis pas un robot" avant de soumettre le formulaire.');
                return false;
            }

            // Ajouter le token dans le champ caché
            var $tokenField = $form.find('input[name="_wpcf7_capjs_token"]');
            if ($tokenField.length) {
                $tokenField.val(token);
                console.log('[CapJS CF7] Token ajouté à la soumission:', token);
            }
        });

        console.log('[CapJS CF7] Widget initialisé (mode dev)');
    }

    /**
     * Fonction d'initialisation du vrai widget CapJS
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
