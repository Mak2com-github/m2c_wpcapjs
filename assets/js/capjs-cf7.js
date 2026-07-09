/**
 * CapJS Integration pour Contact Form 7
 */

(function($) {
    'use strict';

    var DEBUG = false;

    function debugLog(message, data) {
        if (DEBUG) {
            console.log('[CapJS CF7] ' + message, data || '');
        }
    }

    /**
     * Initialise tous les widgets CapJS dans la page
     */
    function initCapJSWidgets() {
        $('.capjs-widget').each(function() {
            var $widget = $(this);

            // Skip si déjà initialisé
            if ($widget.data('capjs-initialized')) {
                return;
            }

            $widget.data('capjs-initialized', true);
            createCapWidget($widget);
        });
    }

    /**
     * Crée (ou recrée) l'élément cap-widget dans son conteneur
     */
    function createCapWidget($widget) {
        var siteKey = $widget.data('sitekey');
        var serverUrl = capjsCF7Config.serverUrl || '';

        if (!serverUrl || !siteKey) {
            console.error('[CapJS CF7] Configuration manquante');
            return;
        }

        // Construire l'API endpoint
        var apiEndpoint = serverUrl.replace(/\/$/, '') + '/' + siteKey + '/';

        var widgetId = 'cap-widget-cf7-' + Math.random().toString(36).slice(2, 11);
        var $capWidget = $('<cap-widget></cap-widget>')
            .attr('id', widgetId)
            .attr('data-cap-api-endpoint', apiEndpoint)
            .attr('data-cap-label', 'Je suis un humain')
            .css({
                'display': 'block',
                'margin': '10px 0'
            });

        $widget.html($capWidget);
        debugLog('Widget initialisé avec endpoint:', apiEndpoint);
    }

    /**
     * Réinitialise le widget d'un formulaire pour obtenir un nouveau token.
     * Les tokens CapJS sont à usage unique : après toute tentative de
     * soumission, le token courant est consommé côté serveur.
     */
    function resetCapJSWidget($wrap) {
        $wrap.find('.capjs-widget').each(function() {
            var $widget = $(this);
            var widgetElement = $widget.find('cap-widget')[0];
            if (!widgetElement) return;

            if (typeof widgetElement.reset === 'function') {
                widgetElement.reset();
            } else {
                createCapWidget($widget);
            }
        });

        $wrap.find('input[name="_wpcf7_capjs_token"]').val('');
        debugLog('Widget réinitialisé');
    }

    $(document).ready(function() {
        initCapJSWidgets();
    });

    // Copie le token dans le champ caché juste avant la soumission.
    // Écouteur global unique : l'événement CF7 remonte sur le wrapper .wpcf7.
    document.addEventListener('wpcf7beforesubmit', function(event) {
        var $wrap = $(event.target);
        var widgetElement = $wrap.find('cap-widget')[0];
        if (!widgetElement) return;

        var token = widgetElement.token || '';
        $wrap.find('input[name="_wpcf7_capjs_token"]').val(token);
        debugLog('Avant soumission, token:', token);
    }, false);

    // Après une tentative aboutie côté serveur (envoyée, refusée comme spam
    // ou échec d'envoi), le token a été consommé : il faut le régénérer pour
    // permettre une nouvelle soumission sans erreur "Captcha invalide".
    ['wpcf7mailsent', 'wpcf7mailfailed', 'wpcf7spam'].forEach(function(eventName) {
        document.addEventListener(eventName, function(event) {
            resetCapJSWidget($(event.target));
        }, false);
    });

})(jQuery);
